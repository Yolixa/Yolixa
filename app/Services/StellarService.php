<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Soneso\StellarSDK\AbstractTransaction;
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;
use Soneso\StellarSDK\PaymentOperationBuilder;
use Soneso\StellarSDK\Responses\Operations\PaymentOperationResponse;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\TransactionBuilder;

class StellarService
{
    private StellarSDK $sdk;

    public function __construct()
    {
        $this->sdk = StellarSDK::getTestNetInstance();
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
            if (!$this->isValidPublicKey($sender) || !$this->isValidPublicKey($destination)) {
                return ['success' => false, 'message' => 'Invalid Stellar public key.'];
            }

            if ($sender === $destination) {
                return ['success' => false, 'message' => 'You cannot tip yourself.'];
            }

            if ($assetCode !== 'XLM') {
                return ['success' => false, 'message' => 'The current MVP supports direct XLM testnet tips only.'];
            }

            if ($amount <= 0) {
                return ['success' => false, 'message' => 'Tip amount must be greater than zero.'];
            }

            try {
                $senderAccount = $this->sdk->requestAccount($sender);
            } catch (\Throwable) {
                return ['success' => false, 'message' => 'Sender wallet is not funded on the Stellar testnet. Fund it with Friendbot first.'];
            }

            try {
                $this->sdk->requestAccount($destination);
            } catch (\Throwable) {
                return ['success' => false, 'message' => 'Creator wallet is not funded on the Stellar testnet.'];
            }

            $amountString = number_format($amount, 7, '.', '');
            $payment = (new PaymentOperationBuilder($destination, Asset::native(), $amountString))->build();

            $transaction = (new TransactionBuilder($senderAccount))
                ->addOperation($payment)
                ->build();

            return [
                'success' => true,
                'xdr' => $transaction->toEnvelopeXdrBase64(),
                'network' => 'TESTNET',
                'network_passphrase' => config('yolixa.stellar_passphrase'),
            ];
        } catch (\Throwable $e) {
            Log::channel('stellar')->error('Tip XDR build failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to build Stellar transaction.'];
        }
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
            return ['success' => false, 'message' => 'Could not submit transaction to Stellar testnet.'];
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
            if ($expectedAssetCode !== 'XLM') {
                return ['success' => false, 'message' => 'Only XLM tips are supported in the current MVP.'];
            }

            $txResponse = $this->sdk->requestTransaction($txHash);
            if (!$txResponse->isSuccessful()) {
                return ['success' => false, 'message' => 'Transaction was not successful on Stellar.'];
            }

            $opsResponse = $this->sdk->operations()->forTransaction($txHash)->execute();

            foreach ($opsResponse->getOperations() as $op) {
                if (!$op instanceof PaymentOperationResponse || $op->getAsset()->getType() !== Asset::TYPE_NATIVE) {
                    continue;
                }

                $matchesReceiver = $op->getTo() === $expectedReceiver;
                $matchesAmount = abs(((float) $op->getAmount()) - $expectedAmount) < 0.0000001;
                $matchesSender = $expectedSender === null || $op->getFrom() === $expectedSender;

                if ($matchesReceiver && $matchesAmount && $matchesSender) {
                    return [
                        'success' => true,
                        'sender_wallet' => $op->getFrom(),
                        'receiver_wallet' => $op->getTo(),
                        'network_fee' => ((float) $txResponse->getFeeCharged()) / 10000000,
                        'ledger' => $txResponse->getLedger(),
                        'created_at' => $txResponse->getCreatedAt(),
                    ];
                }
            }

            return ['success' => false, 'message' => 'Transaction hash exists, but amount, sender, receiver, or asset does not match this tip.'];
        } catch (HorizonRequestException $e) {
            Log::channel('security')->warning('Horizon verify failed: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getStatusCode() === 404 ? 'Transaction hash was not found on Stellar testnet.' : 'Could not verify transaction on Horizon.'];
        } catch (\Throwable $e) {
            Log::channel('security')->error('Transaction verify failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Internal error while verifying transaction.'];
        }
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
