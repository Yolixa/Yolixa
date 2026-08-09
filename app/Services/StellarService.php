<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Soneso\StellarSDK\AbstractTransaction;
use Soneso\StellarSDK\Account;
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\AssetTypeCreditAlphanum;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;
use Soneso\StellarSDK\PaymentOperationBuilder;
use Soneso\StellarSDK\Responses\Operations\PaymentOperationResponse;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\TransactionBuilder;
use Soneso\StellarSDK\TransactionBuilderAccount;

class StellarService
{
    private StellarSDK $sdk;
    private StellarConfigurationService $configuration;

    public function __construct(StellarConfigurationService $configuration)
    {
        $this->configuration = $configuration;
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
    public function buildTipXdr(string $sender, string $destination, string $assetCode, float $amount): array
    {
        try {
            $validation = $this->validateTipInputs($sender, $destination, $assetCode, (string) $amount);
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

            $platformWallet = config('yolixa.platform_public_key');
            if (!$this->isValidPublicKey($platformWallet)) {
                return ['success' => false, 'message' => 'Platform fee wallet is not configured. Set YOLIXA_PLATFORM_WALLET_PUBLIC before enabling tips.'];
            }

            try {
                $platformAccount = $this->sdk->requestAccount($platformWallet);
            } catch (\Throwable) {
                return ['success' => false, 'message' => 'Platform fee wallet is not funded on the configured Stellar network.'];
            }

            $asset = $this->assetForCode($assetCode);
            if (!$asset) {
                return ['success' => false, 'message' => 'Unsupported asset or missing asset issuer config.'];
            }

            if ($assetCode !== 'XLM') {
                $senderTrustline = $this->accountAssetBalance($senderAccount, $assetCode, $this->assetIssuer($assetCode));
                $creatorTrustline = $this->accountAssetBalance($destinationAccount, $assetCode, $this->assetIssuer($assetCode));
                $platformTrustline = $this->accountAssetBalance($platformAccount, $assetCode, $this->assetIssuer($assetCode));

                if (!$senderTrustline) {
                    return ['success' => false, 'code' => 'sender_missing_trustline', 'message' => "Sender wallet is missing a {$assetCode} trustline."];
                }

                if (!$creatorTrustline) {
                    return ['success' => false, 'code' => 'creator_missing_trustline', 'message' => "Creator wallet is missing a {$assetCode} trustline."];
                }

                if (!$platformTrustline) {
                    return ['success' => false, 'code' => 'platform_missing_trustline', 'message' => "Platform wallet is missing a {$assetCode} trustline."];
                }

                if ($this->compareDecimalStrings($senderTrustline->getBalance(), (string) $amount) < 0) {
                    return ['success' => false, 'message' => "Sender wallet does not have enough {$assetCode} balance."];
                }
            }

            $amounts = $this->calculateSplitAmounts((string) $amount);

            return $this->buildTipXdrFromAccount($senderAccount, $sender, $destination, $platformWallet, $assetCode, $amounts);
        } catch (\Throwable $e) {
            Log::channel('stellar')->error('Tip XDR build failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to build Stellar transaction.'];
        }
    }

    public function buildTipXdrFromAccount(
        TransactionBuilderAccount $senderAccount,
        string $sender,
        string $destination,
        string $platformWallet,
        string $assetCode,
        array $amounts
    ): array {
        $asset = $this->assetForCode($assetCode);
        if (!$asset) {
            return ['success' => false, 'message' => 'Unsupported asset or missing asset issuer config.'];
        }

        $creatorPayment = (new PaymentOperationBuilder($destination, $asset, $amounts['creator_payout_amount']))
            ->setSourceAccount($sender)
            ->build();
        $platformPayment = (new PaymentOperationBuilder($platformWallet, $asset, $amounts['platform_fee']))
            ->setSourceAccount($sender)
            ->build();

        $transaction = (new TransactionBuilder($senderAccount))
            ->addOperation($creatorPayment)
            ->addOperation($platformPayment)
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
            'asset_issuer' => $this->assetIssuer($assetCode),
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
        float $expectedAmount,
        string $expectedAssetCode,
        ?string $expectedSender = null
    ): array {
        try {
            $amounts = $this->calculateSplitAmounts((string) $expectedAmount);
            $assetIssuer = $this->assetIssuer($expectedAssetCode);
            $platformWallet = config('yolixa.platform_public_key');

            $txResponse = $this->sdk->requestTransaction($txHash);
            if (!$txResponse->isSuccessful()) {
                return ['success' => false, 'message' => 'Transaction was not successful on Stellar.'];
            }

            $opsResponse = $this->sdk->operations()->forTransaction($txHash)->execute();
            $verification = $this->verifyPaymentOperations(
                $opsResponse->getOperations()->toArray(),
                $expectedReceiver,
                $platformWallet,
                $amounts['creator_payout_amount'],
                $amounts['platform_fee'],
                $expectedAssetCode,
                $assetIssuer,
                $expectedSender
            );

            if (!$verification['success']) {
                return $verification;
            }

            return [
                'success' => true,
                'sender_wallet' => $verification['sender_wallet'],
                'receiver_wallet' => $expectedReceiver,
                'platform_wallet' => $platformWallet,
                'network_fee' => ((float) $txResponse->getFeeCharged()) / 10000000,
                'ledger' => $txResponse->getLedger(),
                'created_at' => $txResponse->getCreatedAt(),
                'platform_fee' => $amounts['platform_fee'],
                'creator_payout_amount' => $amounts['creator_payout_amount'],
                'asset_issuer' => $assetIssuer,
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
        ?string $platformWallet,
        string $expectedCreatorAmount,
        string $expectedPlatformFee,
        string $expectedAssetCode,
        ?string $expectedAssetIssuer,
        ?string $expectedSender
    ): array {
        if (!$this->isValidPublicKey($platformWallet)) {
            return ['success' => false, 'message' => 'Platform fee wallet is not configured.'];
        }

        $creatorMatched = false;
        $platformMatched = false;
        $senderWallet = null;

        foreach ($operations as $op) {
            if (!$op instanceof PaymentOperationResponse) {
                continue;
            }

            if (!$this->operationAssetMatches($op, $expectedAssetCode, $expectedAssetIssuer)) {
                continue;
            }

            if ($expectedSender !== null && $op->getFrom() !== $expectedSender) {
                continue;
            }

            if ($op->getTo() === $expectedReceiver && $this->amountsEqual($op->getAmount(), $expectedCreatorAmount)) {
                $creatorMatched = true;
                $senderWallet = $op->getFrom();
            }

            if ($op->getTo() === $platformWallet && $this->amountsEqual($op->getAmount(), $expectedPlatformFee)) {
                $platformMatched = true;
                $senderWallet = $op->getFrom();
            }
        }

        if (!$creatorMatched || !$platformMatched) {
            return ['success' => false, 'message' => 'Transaction hash exists, but required creator and platform fee payment operations do not match this tip.'];
        }

        return ['success' => true, 'sender_wallet' => $senderWallet];
    }

    public function calculateSplitAmounts(string $amount): array
    {
        $grossUnits = $this->decimalToUnits($amount);
        $feeRate = (float) config('yolixa.fee_percentage', 0.015);
        $feeUnits = (int) round($grossUnits * $feeRate);
        $creatorUnits = $grossUnits - $feeUnits;

        if ($feeUnits <= 0 && $feeRate > 0) {
            $feeUnits = 1;
            $creatorUnits = $grossUnits - 1;
        }

        return [
            'gross_amount' => $this->unitsToDecimal($grossUnits),
            'platform_fee' => $this->unitsToDecimal(max($feeUnits, 0)),
            'creator_payout_amount' => $this->unitsToDecimal(max($creatorUnits, 0)),
        ];
    }

    public function supportedTipAssets(): array
    {
        $assets = ['XLM'];
        $usdc = config('yolixa.assets.USDC');
        if (($usdc['enabled'] ?? false) && $this->isValidPublicKey($usdc['issuer'] ?? null)) {
            $assets[] = 'USDC';
        }

        return $assets;
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

        $units = $this->decimalToUnits($amount);
        if ($units <= 0) {
            return ['success' => false, 'message' => 'Amount too small.'];
        }

        $maxUnits = $this->decimalToUnits((string) config('yolixa.max_payment_amount', 1000));
        if ($units > $maxUnits) {
            return ['success' => false, 'message' => 'Amount too large.'];
        }

        return ['success' => true];
    }

    private function assetForCode(string $assetCode): ?Asset
    {
        if ($assetCode === 'XLM') {
            return Asset::native();
        }

        $issuer = $this->assetIssuer($assetCode);
        if (!$this->isValidPublicKey($issuer)) {
            return null;
        }

        return Asset::createNonNativeAsset($assetCode, $issuer);
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

        return $asset instanceof AssetTypeCreditAlphanum
            && $asset->getCode() === $assetCode
            && $asset->getIssuer() === $issuer;
    }

    private function amountsEqual(string $actual, string $expected): bool
    {
        return $this->decimalToUnits($actual) === $this->decimalToUnits($expected);
    }

    private function decimalToUnits(string $amount): int
    {
        $amount = trim($amount);
        if (!preg_match('/^\d+(\.\d+)?$/', $amount)) {
            return 0;
        }

        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');
        $fraction = substr(str_pad($fraction, 8, '0'), 0, 8);
        $rounded = (int) substr($fraction, 7, 1) >= 5 ? 1 : 0;
        $fraction7 = (int) substr($fraction, 0, 7);

        return ((int) $whole * 10000000) + $fraction7 + $rounded;
    }

    private function unitsToDecimal(int $units): string
    {
        $whole = intdiv($units, 10000000);
        $fraction = str_pad((string) ($units % 10000000), 7, '0', STR_PAD_LEFT);

        return "{$whole}.{$fraction}";
    }

    private function compareDecimalStrings(string $left, string $right): int
    {
        return $this->decimalToUnits($left) <=> $this->decimalToUnits($right);
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
