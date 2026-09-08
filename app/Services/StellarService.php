<?php

namespace App\Services;

use DateTime;
use Illuminate\Support\Facades\Log;
use Soneso\StellarSDK\AbstractTransaction;
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;
use Soneso\StellarSDK\PaymentOperationBuilder;
use Soneso\StellarSDK\Responses\Operations\PaymentOperationResponse;
use Soneso\StellarSDK\Responses\Account\AccountResponse;
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
     * The app never handles secret keys; Freighter signs this XDR in the browser for the current MVP.
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

            $feeStroops = $this->currentBaseFeeStroops();
            $reserveStroops = $this->currentBaseReserveStroops();
            $preflight = $this->assessNativeXlmSpendability(
                $senderAccount,
                $this->amounts->decimalToAtomic($normalizedAmount),
                1,
                $reserveStroops['base_reserve_stroops'],
                $feeStroops['base_fee_stroops']
            );

            if (!$preflight['success']) {
                return $preflight;
            }

            $amounts = $this->calculateSplitAmounts($normalizedAmount);

            return $this->buildTipXdrFromAccount($senderAccount, $sender, $destination, null, $assetCode, $amounts, $feeStroops['base_fee_stroops']);
        } catch (\Throwable $e) {
            Log::channel('stellar')->error('tip_xdr_build_failed', ['exception' => $e::class]);
            return ['success' => false, 'message' => 'Failed to build Stellar transaction.'];
        }
    }

    public function buildTipXdrFromAccount(
        TransactionBuilderAccount $senderAccount,
        string $sender,
        string $destination,
        ?string $platformWallet,
        string $assetCode,
        array $amounts,
        ?int $baseFeeStroops = null
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
            ->setMaxOperationFee(max($baseFeeStroops ?? AbstractTransaction::MIN_BASE_FEE, AbstractTransaction::MIN_BASE_FEE))
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
            Log::channel('stellar')->warning('horizon_submit_failed', [
                'status' => $e->getStatusCode(),
                'result' => $message,
            ]);
            return ['success' => false, 'message' => $message];
        } catch (\Throwable $e) {
            Log::channel('stellar')->error('transaction_submit_failed', ['exception' => $e::class]);
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
            $txHash = strtolower(trim($txHash));
            if (!preg_match('/^[a-f0-9]{64}$/', $txHash)) {
                return ['success' => false, 'message' => 'Invalid transaction hash.'];
            }

            if (!in_array($expectedAssetCode, $this->supportedTipAssets(), true)) {
                return ['success' => false, 'message' => 'Only native XLM tipping is enabled in the current MVP.'];
            }

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
            Log::channel('security')->warning('horizon_verify_failed', [
                'tx_hash' => $txHash,
                'status' => $e->getStatusCode(),
            ]);
            return ['success' => false, 'message' => $e->getStatusCode() === 404 ? 'Transaction hash was not found on the configured Stellar network.' : 'Could not verify transaction on Horizon.'];
        } catch (\Throwable $e) {
            Log::channel('security')->error('transaction_verify_failed', [
                'tx_hash' => $txHash,
                'exception' => $e::class,
            ]);
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

    public function assessNativeXlmSpendability(
        AccountResponse $account,
        string $tipAmountAtomic,
        int $operationCount = 1,
        ?int $baseReserveStroops = null,
        ?int $baseFeeStroops = null
    ): array {
        $baseReserveStroops = max($baseReserveStroops ?? 5_000_000, 0);
        $baseFeeStroops = max($baseFeeStroops ?? AbstractTransaction::MIN_BASE_FEE, AbstractTransaction::MIN_BASE_FEE);
        $operationCount = max($operationCount, 1);

        $nativeBalance = $this->accountAssetBalance($account, 'XLM', null);
        if (!$nativeBalance) {
            return ['success' => false, 'message' => 'Sender wallet has no native XLM balance on the configured Stellar network.'];
        }

        $balanceAtomic = $this->decimalToAtomicAllowZero($nativeBalance->getBalance());
        $sellingLiabilitiesAtomic = $nativeBalance->getSellingLiabilities()
            ? $this->decimalToAtomicAllowZero($nativeBalance->getSellingLiabilities())
            : '0';

        $reserveEntries = max(
            0,
            2
            + $this->safeAccountInteger($account, 'getSubentryCount')
            + $this->safeAccountInteger($account, 'getNumSponsoring')
            - $this->safeAccountInteger($account, 'getNumSponsored')
        );

        $minimumBalanceAtomic = (string) ($reserveEntries * $baseReserveStroops);
        $feeAtomic = (string) ($operationCount * $baseFeeStroops);
        $lockedAtomic = $this->amounts->addAtomic($minimumBalanceAtomic, $sellingLiabilitiesAtomic);
        $spendableAtomic = $this->amounts->compareAtomic($balanceAtomic, $lockedAtomic) > 0
            ? $this->amounts->subtractAtomic($balanceAtomic, $lockedAtomic)
            : '0';
        $requiredAtomic = $this->amounts->addAtomic($tipAmountAtomic, $feeAtomic);

        if ($this->amounts->compareAtomic($spendableAtomic, $requiredAtomic) < 0) {
            return [
                'success' => false,
                'message' => 'Sender wallet does not have enough spendable XLM for this tip, network fee, and Stellar minimum reserve.',
                'spendable_xlm' => $this->amounts->atomicToDecimal($spendableAtomic),
                'required_xlm' => $this->amounts->atomicToDecimal($requiredAtomic),
                'minimum_balance_xlm' => $this->amounts->atomicToDecimal($minimumBalanceAtomic),
                'network_fee_xlm' => $this->amounts->atomicToDecimal($feeAtomic),
            ];
        }

        return [
            'success' => true,
            'spendable_xlm' => $this->amounts->atomicToDecimal($spendableAtomic),
            'required_xlm' => $this->amounts->atomicToDecimal($requiredAtomic),
            'minimum_balance_xlm' => $this->amounts->atomicToDecimal($minimumBalanceAtomic),
            'network_fee_xlm' => $this->amounts->atomicToDecimal($feeAtomic),
        ];
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

    private function currentBaseReserveStroops(): array
    {
        try {
            $ledgers = $this->sdk->ledgers()->order('desc')->limit(1)->execute()->getLedgers()->toArray();
            $ledger = $ledgers[0] ?? null;

            if ($ledger && method_exists($ledger, 'getBaseReserveInStroops')) {
                return ['base_reserve_stroops' => max((int) $ledger->getBaseReserveInStroops(), 0), 'source' => 'horizon_latest_ledger'];
            }
        } catch (\Throwable $e) {
            Log::channel('stellar')->warning('base_reserve_lookup_failed', ['exception' => $e::class]);
        }

        return ['base_reserve_stroops' => 5_000_000, 'source' => 'fallback_stellar_current_default'];
    }

    private function currentBaseFeeStroops(): array
    {
        try {
            $feeStats = $this->sdk->requestFeeStats();
            return [
                'base_fee_stroops' => max((int) $feeStats->getLastLedgerBaseFee(), AbstractTransaction::MIN_BASE_FEE),
                'source' => 'horizon_fee_stats',
            ];
        } catch (\Throwable $e) {
            Log::channel('stellar')->warning('base_fee_lookup_failed', ['exception' => $e::class]);
        }

        return ['base_fee_stroops' => AbstractTransaction::MIN_BASE_FEE, 'source' => 'sdk_min_base_fee'];
    }

    private function safeAccountInteger(AccountResponse $account, string $method): int
    {
        try {
            return method_exists($account, $method) ? max((int) $account->{$method}(), 0) : 0;
        } catch (\Throwable) {
            return 0;
        }
    }

    private function decimalToAtomicAllowZero(string $amount): string
    {
        $amount = trim($amount);
        if (preg_match('/^0(?:\.0{1,7})?$/', $amount)) {
            return '0';
        }

        return $this->amounts->decimalToAtomic($amount);
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

        $codes = array_filter(array_merge($txCode ? [$txCode] : [], $opCodes ?: []));

        foreach ($codes as $code) {
            $message = match ($code) {
                'tx_insufficient_balance', 'op_underfunded' => 'Insufficient spendable XLM after Stellar reserves and fees.',
                'op_no_destination' => 'Destination account is not funded on the configured Stellar network.',
                'tx_bad_seq', 'tx_bad_auth', 'tx_bad_auth_extra' => str_starts_with($code, 'tx_bad_seq')
                    ? 'Bad sequence number. Rebuild the transaction and try again.'
                    : 'Transaction authentication failed. Reconnect the expected wallet and sign again.',
                'tx_too_early' => 'Transaction is too early for its time bounds.',
                'tx_too_late' => 'Transaction expired before submission. Rebuild the transaction and sign again.',
                'tx_malformed' => 'Malformed transaction XDR.',
                default => null,
            };

            if ($message) {
                return $message;
            }
        }

        if ($codes) {
            return trim('Horizon rejected transaction ' . ($txCode ? "({$txCode})" : '') . (!empty($opCodes) ? ': ' . implode(', ', $opCodes) : ''));
        }

        return 'Horizon rejected the transaction.';
    }
}
