<?php

namespace App\Services;

use DateTime;
use Illuminate\Support\Facades\Log;
use Soneso\StellarSDK\AbstractTransaction;
use Soneso\StellarSDK\Account;
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;
use Soneso\StellarSDK\PaymentOperationBuilder;
use Soneso\StellarSDK\Responses\Operations\PaymentOperationResponse;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\TimeBounds;
use Soneso\StellarSDK\TransactionBuilder;
use Soneso\StellarSDK\TransactionBuilderAccount;

class StellarService
{
    private StellarSDK $sdk;
    private StellarConfigurationService $configuration;
    private XlmAmount $amounts;

    public function __construct(StellarConfigurationService $configuration, XlmAmount $amounts)
    {
        $this->configuration = $configuration;
        $this->amounts = $amounts;
        $this->sdk = $configuration->sdk();
    }

    public function isValidPublicKey(string $publicKey): bool
    {
        try {
            KeyPair::fromAccountId($publicKey);
            return str_starts_with($publicKey, 'G') && strlen($publicKey) === 56;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Build the core Yolixa MVP transaction: a real direct XLM payment from fan to creator.
     * The app never handles secret keys; Freighter/Rabet signs this XDR in the browser.
     */
    public function buildTipXdr(string $sender, string $destination, string $assetCode, string $amount): array
    {
        try {
            $normalizedAmount = $this->normalizeXlmAmount($amount);
            $validation = $this->validateTipInputs($sender, $destination, $assetCode, $normalizedAmount);
            if (!$validation['success']) {
                return $validation;
            }

            try {
                $senderAccount = $this->sdk->requestAccount($sender);
            } catch (\Throwable) {
                return ['success' => false, 'message' => 'Sender wallet is not funded on the configured Stellar network.'];
            }

            try {
                $destinationAccount = $this->sdk->requestAccount($destination);
            } catch (\Throwable) {
                return ['success' => false, 'message' => 'Creator wallet is not funded on the configured Stellar network.'];
            }

            $asset = $this->assetForCode($assetCode);
            if (!$asset) {
                return ['success' => false, 'message' => 'Only native XLM tipping is enabled in the current MVP.'];
            }

            $senderBalance = $this->accountAssetBalance($senderAccount, 'XLM', null);
            if ($senderBalance && $this->compareDecimalStrings($senderBalance->getBalance(), $normalizedAmount) < 0) {
                return ['success' => false, 'message' => 'Sender wallet does not have enough XLM for this tip and network fees.'];
            }

            $amounts = $this->calculateSplitAmounts($normalizedAmount);

            return $this->buildTipXdrFromAccount($senderAccount, $sender, $destination, null, $assetCode, $amounts);
        } catch (\Throwable $e) {
            Log::channel('stellar')->error('Tip XDR build failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to build Stellar transaction.'];
        }
    }

    public function buildTipXdrFromAccount(
        TransactionBuilderAccount $senderAccount,
        string $sender,
        string $destination,
        ?string $platformWallet,
        string $assetCode,
        array $amounts
    ): array {
        $asset = $this->assetForCode($assetCode);
        if (!$asset) {
            return ['success' => false, 'message' => 'Only native XLM tipping is enabled in the current MVP.'];
        }

        $creatorPayment = (new PaymentOperationBuilder($destination, $asset, $amounts['creator_payout_amount']))
            ->setSourceAccount($sender)
            ->build();

        $transaction = (new TransactionBuilder($senderAccount))
            ->addOperation($creatorPayment)
            ->setTimeBounds(new TimeBounds(new DateTime('@0'), new DateTime('@'.(time() + 300))))
            ->build();

        return [
            'success' => true,
            'xdr' => $transaction->toEnvelopeXdrBase64(),
            'network' => $this->configuration->networkLabel(),
            'network_passphrase' => $this->configuration->passphrase(),
            'gross_amount' => $amounts['gross_amount'],
            'platform_fee' => $amounts['platform_fee'],
            'creator_payout_amount' => $amounts['creator_payout_amount'],
            'asset' => $assetCode,
            'asset_issuer' => null,
        ];
    }

    public function submitTransaction(string $signedXdr): array
    {
        try {
            $transaction = AbstractTransaction::fromEnvelopeBase64XdrString($signedXdr);
            $response = $this->sdk->submitTransaction($transaction);

            if ($response->isSuccessful()) {
                return [
                    'success' => true,
                    'hash' => $response->getHash(),
                ];
            }

            return ['success' => false, 'message' => 'Horizon rejected the transaction.'];
        } catch (HorizonRequestException $e) {
            $message = $this->formatHorizonError($e);
            Log::channel('stellar')->warning('Horizon submit failed: ' . $message);
            return ['success' => false, 'message' => $message];
        } catch (\Throwable $e) {
            Log::channel('stellar')->error('Transaction submit failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Could not submit transaction to the configured Stellar network.'];
        }
    }

    public function verifyTransaction(
        string $txHash,
        string $expectedReceiver,
        string $expectedAmount,
        string $expectedAssetCode,
        ?string $expectedSender = null
    ): array {
        try {
            $amounts = $this->calculateSplitAmounts($this->normalizeXlmAmount($expectedAmount));

            $txResponse = $this->sdk->requestTransaction($txHash);
            if (!$txResponse->isSuccessful()) {
                return ['success' => false, 'message' => 'Transaction was not successful on Stellar.'];
            }

            if ($expectedSender !== null && $txResponse->getSourceAccount() !== $expectedSender) {
                return ['success' => false, 'message' => 'Transaction source account does not match the connected supporter wallet.'];
            }

            $opsResponse = $this->sdk->operations()->forTransaction($txHash)->execute();
            $verification = $this->verifyPaymentOperations(
                $opsResponse->getOperations()->toArray(),
                $expectedReceiver,
                $amounts['creator_payout_amount'],
                $expectedAssetCode,
                $expectedSender
            );

            if (!$verification['success']) {
                return $verification;
            }

            return [
                'success' => true,
                'sender_wallet' => $verification['sender_wallet'],
                'receiver_wallet' => $expectedReceiver,
                'network_fee' => $this->amounts->atomicToDecimal((string) ($txResponse->getFeeCharged() ?? '0')),
                'ledger' => $txResponse->getLedger(),
                'created_at' => $txResponse->getCreatedAt(),
                'platform_fee' => '0.0000000',
                'creator_payout_amount' => $amounts['creator_payout_amount'],
                'asset_issuer' => null,
            ];
        } catch (HorizonRequestException $e) {
            Log::channel('security')->warning('Horizon verify failed: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getStatusCode() === 404 ? 'Transaction hash was not found on the configured Stellar network.' : 'Could not verify transaction on Horizon.'];
        } catch (\Throwable $e) {
            Log::channel('security')->error('Transaction verify failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Internal error while verifying transaction.'];
        }
    }

    public function verifyPaymentOperations(
        array $operations,
        string $expectedReceiver,
        string $expectedCreatorAmount,
        string $expectedAssetCode,
        ?string $expectedSender
    ): array {
        $creatorMatched = false;
        $senderWallet = null;

        foreach ($operations as $op) {
            if (!$op instanceof PaymentOperationResponse) {
                continue;
            }

            if (!$this->operationAssetMatches($op, $expectedAssetCode, null)) {
                continue;
            }

            if ($expectedSender !== null && $op->getFrom() !== $expectedSender) {
                continue;
            }

            if ($op->getTo() === $expectedReceiver && $this->amountsEqual($op->getAmount(), $expectedCreatorAmount)) {
                $creatorMatched = true;
                $senderWallet = $op->getFrom();
            }
        }

        if (!$creatorMatched) {
            return ['success' => false, 'message' => 'Transaction hash exists, but the XLM payment does not match this tip.'];
        }

        return ['success' => true, 'sender_wallet' => $senderWallet];
    }

    public function calculateSplitAmounts(string $amount): array
    {
        $grossAtomic = $this->amounts->decimalToAtomic($amount);

        return [
            'gross_amount' => $this->amounts->atomicToDecimal($grossAtomic),
            'platform_fee' => '0.0000000',
            'creator_payout_amount' => $this->amounts->atomicToDecimal($grossAtomic),
        ];
    }

    public function supportedTipAssets(): array
    {
        return ['XLM'];
    }

    public function assetIssuer(string $assetCode): ?string
    {
        return $assetCode === 'XLM' ? null : config("yolixa.assets.{$assetCode}.issuer");
    }

    private function validateTipInputs(string $sender, string $destination, string $assetCode, string $amount): array
    {
        if (!$this->isValidPublicKey($sender)) {
            return ['success' => false, 'message' => 'Invalid sender wallet.'];
        }

        if (!$this->isValidPublicKey($destination)) {
            return ['success' => false, 'message' => 'Invalid creator wallet.'];
        }

        if ($sender === $destination) {
            return ['success' => false, 'message' => 'Self tip blocked.'];
        }

        if (!in_array($assetCode, $this->supportedTipAssets(), true)) {
            return ['success' => false, 'message' => 'Unsupported asset or missing asset issuer config.'];
        }

        try {
            $atomic = $this->amounts->decimalToAtomic($amount);
            $this->amounts->validateWithinConfiguredLimits($atomic);
        } catch (\InvalidArgumentException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }

        return ['success' => true];
    }

    private function assetForCode(string $assetCode): ?Asset
    {
        if ($assetCode === 'XLM') {
            return Asset::native();
        }

        return null;
    }

    private function accountAssetBalance($account, string $assetCode, ?string $issuer)
    {
        foreach ($account->getBalances() as $balance) {
            if ($assetCode === 'XLM' && $balance->getAssetType() === Asset::TYPE_NATIVE) {
                return $balance;
            }

            if ($balance->getAssetCode() === $assetCode && $balance->getAssetIssuer() === $issuer) {
                return $balance;
            }
        }

        return null;
    }

    private function operationAssetMatches(PaymentOperationResponse $op, string $assetCode, ?string $issuer): bool
    {
        $asset = $op->getAsset();
        if ($assetCode === 'XLM') {
            return $asset->getType() === Asset::TYPE_NATIVE;
        }

        return false;
    }

    private function amountsEqual(string $actual, string $expected): bool
    {
        return $this->amounts->decimalToAtomic($actual) === $this->amounts->decimalToAtomic($expected);
    }

    private function normalizeXlmAmount(string $amount): string
    {
        return $this->amounts->atomicToDecimal($this->amounts->decimalToAtomic($amount));
    }

    private function compareDecimalStrings(string $left, string $right): int
    {
        return $this->amounts->compareAtomic(
            $this->amounts->decimalToAtomic($left),
            $this->amounts->decimalToAtomic($right)
        );
    }

    private function formatHorizonError(HorizonRequestException $e): string
    {
        $horizon = $e->getHorizonErrorResponse();
        if (!$horizon || !$horizon->getExtras()) {
            return 'Horizon error: ' . $e->getMessage();
        }

        $extras = $horizon->getExtras();
        $txCode = method_exists($extras, 'getResultCodesTransaction') ? $extras->getResultCodesTransaction() : null;
        $opCodes = method_exists($extras, 'getResultCodesOperation') ? $extras->getResultCodesOperation() : [];

        if ($txCode || $opCodes) {
            return trim('Horizon rejected transaction ' . ($txCode ? "({$txCode})" : '') . (!empty($opCodes) ? ': ' . implode(', ', $opCodes) : ''));
        }

        return 'Horizon rejected the transaction.';
    }
}
