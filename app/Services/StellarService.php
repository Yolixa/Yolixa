<?php

namespace App\Services;

use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\TransactionBuilder;
use Soneso\StellarSDK\PaymentOperationBuilder;
use Soneso\StellarSDK\ChangeTrustOperationBuilder;
use Soneso\StellarSDK\Network;
use Illuminate\Support\Facades\Log;

class StellarService
{
    private $sdk;
    private $network;

    public function __construct()
    {
        $this->sdk = StellarSDK::getTestNetInstance();
        $this->network = Network::testnet();
    }

    /**
     * Build an XDR transaction for sending a tip.
     */
    public function buildTipXdr(string $sender, string $destination, string $assetCode, float $totalAmount, float $platformFeeAmount): array
    {
        try {
            // Check if sender actually exists and is funded on the Testnet
            try {
                $senderAccount = $this->sdk->requestAccount($sender);
            } catch (\Throwable $e) {
                return ['success' => false, 'message' => 'Sender wallet is not funded on the Testnet. Please fund it using Friendbot.'];
            }

            // Check if destination actually exists
            try {
                $this->sdk->requestAccount($destination);
            } catch (\Throwable $e) {
                return ['success' => false, 'message' => 'Creator wallet is not funded on the Testnet. It must be funded first.'];
            }
            
            if ($assetCode === 'YLX') {
                $asset = Asset::createNonNativeAsset(env('YLX_ASSET_CODE', 'YLX'), env('YLX_ISSUER_PUBLIC'));
            } else {
                $asset = Asset::native();
            }

            $creatorAmountFloat = $totalAmount - $platformFeeAmount;

            // Security: FORMATTING: Soneso SDK requires precise string numeric formatting (max 7 decimals)
            $platformFeeStr = number_format($platformFeeAmount, 7, '.', '');
            $creatorAmountStr = number_format($creatorAmountFloat, 7, '.', '');

            $txBuilder = new TransactionBuilder($senderAccount);
            
            // In Option A, the User pays the full tip amount to the Platform Collection Wallet.
            $platformCollection = config('yolixa.platform_collection_wallet');
            if (empty($platformCollection)) {
                return ['success' => false, 'message' => 'Platform collection wallet is not configured.'];
            }

            try {
                // Check if platform collection wallet is funded
                $this->sdk->requestAccount($platformCollection);
            } catch (\Throwable $e) {
                return ['success' => false, 'message' => 'Platform collection wallet is not funded on the Testnet.'];
            }

            $totalAmountStr = number_format($totalAmount, 7, '.', '');
            $paymentToPlatform = (new PaymentOperationBuilder($platformCollection, $asset, $totalAmountStr))->build();
            $txBuilder->addOperation($paymentToPlatform);

            $transaction = $txBuilder->build();
            $xdr = $transaction->toEnvelopeXdrBase64();

            return ['success' => true, 'xdr' => $xdr];

        } catch (\Throwable $e) {
            Log::channel('stellar')->error('Tip XDR Build Error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to build transaction: ' . $e->getMessage()];
        }
    }

    /**
     * Submit a signed XDR to the network.
     */
    public function submitTransaction(string $signedXdr): array
    {
        try {
            $transaction = \Soneso\StellarSDK\AbstractTransaction::fromEnvelopeBase64XdrString($signedXdr);
            $response = $this->sdk->submitTransaction($transaction);
            
            if ($response->isSuccessful()) {
                return [
                    'success' => true,
                    'hash'    => $response->getHash(),
                ];
            }

            return ['success' => false, 'message' => 'Transaction failed but no exception thrown.'];

        } catch (\Soneso\StellarSDK\Exceptions\HorizonRequestException $e) {
            $errorMsg = 'Network error: ' . $e->getMessage();
            $horizonErr = $e->getHorizonErrorResponse();
            
            if ($horizonErr && $horizonErr->getExtras()) {
                $extras = $horizonErr->getExtras();
                $txCode = method_exists($extras, 'getResultCodesTransaction') ? $extras->getResultCodesTransaction() : 'Unknown';
                $opCodes = method_exists($extras, 'getResultCodesOperation') ? $extras->getResultCodesOperation() : [];
                
                $errorMsg = 'Horizon Rejected (Tx: ' . $txCode . ')';
                if (!empty($opCodes)) {
                     $errorMsg .= ' | Ops: ' . implode(', ', $opCodes);
                }
            }
            
            Log::channel('stellar')->error("Horizon Fail: " . $errorMsg);
            return ['success' => false, 'message' => $errorMsg];
        } catch (\Throwable $e) {
            Log::channel('stellar')->error("Submission Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Network error: ' . $e->getMessage()];
        }
    }

    /**
     * Submit a server-signed payout transaction to the creator.
     */
    public function payoutCreatorInYlx(string $creatorPubKey, float $amount): array
    {
        try {
            $distSecret = config('yolixa.platform_distribution_seed');
            if (empty($distSecret)) {
                return ['success' => false, 'message' => 'Platform distribution seed is not configured.'];
            }

            $sourceKeyPair = \Soneso\StellarSDK\Crypto\KeyPair::fromSeed($distSecret);
            $sourceAccount = $this->sdk->requestAccount($sourceKeyPair->getAccountId());

            // Ensure destination has trustline
            try {
                $destAccount = $this->sdk->requestAccount($creatorPubKey);
                $hasTrustline = false;
                foreach ($destAccount->getBalances() as $balance) {
                    if ($balance->getAssetCode() === env('YLX_ASSET_CODE', 'YLX')) {
                        $hasTrustline = true;
                        break;
                    }
                }
                if (!$hasTrustline) {
                     return ['success' => false, 'message' => 'Creator does not have a YLX trustline.'];
                }
            } catch (\Throwable $e) {
                return ['success' => false, 'message' => 'Creator wallet is not funded on the network.'];
            }

            $asset = Asset::createNonNativeAsset(env('YLX_ASSET_CODE', 'YLX'), env('YLX_ISSUER_PUBLIC'));
            $amountStr = number_format($amount, 7, '.', '');

            $txBuilder = new TransactionBuilder($sourceAccount);
            $paymentOp = (new PaymentOperationBuilder($creatorPubKey, $asset, $amountStr))->build();
            $txBuilder->addOperation($paymentOp);
            
            $transaction = $txBuilder->build();
            $transaction->sign($sourceKeyPair, $this->network);

            $response = $this->sdk->submitTransaction($transaction);
            
            if ($response->isSuccessful()) {
                return [
                    'success' => true,
                    'hash'    => $response->getHash(),
                ];
            }

            return ['success' => false, 'message' => 'Payout transaction failed.'];

        } catch (\Throwable $e) {
            Log::channel('stellar')->error("Payout Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to process payout: ' . $e->getMessage()];
        }
    }

    /**
     * Build an XDR transaction for adding a trustline.
     */
    public function buildTrustlineXdr(string $sender): array
    {
        try {
            $senderAccount = $this->sdk->requestAccount($sender);
            $assetCode = env('YLX_ASSET_CODE', 'YLX');
            $issuerPublicKey = env('YLX_ISSUER_PUBLIC');
            
            if (empty($issuerPublicKey)) {
                return ['success' => false, 'message' => 'YLX Issuer public key is not configured.'];
            }

            $asset = Asset::createNonNativeAsset($assetCode, $issuerPublicKey);
            $txBuilder = new TransactionBuilder($senderAccount);
            
            $changeTrustOp = (new ChangeTrustOperationBuilder($asset))->build();
            $txBuilder->addOperation($changeTrustOp);
            
            $transaction = $txBuilder->build();
            $xdr = $transaction->toEnvelopeXdrBase64();

            return ['success' => true, 'xdr' => $xdr];

        } catch (\Throwable $e) {
            Log::channel('stellar')->error("Trustline XDR Build Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to build trustline transaction: ' . $e->getMessage()];
        }
    }

    /**
     * Verify a transaction hash exists on the network and matches our expected values.
     */
    public function verifyTransaction(string $txHash, string $expectedReceiver, float $expectedAmount, string $expectedAssetCode): array
    {
        try {
            $txResponse = $this->sdk->requestTransaction($txHash);
            
            if (!$txResponse->isSuccessful()) {
                return ['success' => false, 'message' => 'Transaction was not successful on the ledger.'];
            }

            // Next we must request the operations for this transaction to verify the transfer
            $opsResponse = $this->sdk->operations()->forTransaction($txHash)->execute();
            
            $foundMatch = false;
            foreach ($opsResponse->getOperations() as $op) {
                if ($op instanceof \Soneso\StellarSDK\Responses\Operations\PaymentOperationResponse) {
                    $opAmount = (float) $op->getAmount();
                    $opReceiver = $op->getTo();
                    
                    // Simple logic for native vs credit assets
                    if ($op->getAssetType() === 'native' && $expectedAssetCode === 'XLM') {
                        if ($opReceiver === $expectedReceiver && abs($opAmount - $expectedAmount) < 0.00001) {
                            $foundMatch = true;
                            break;
                        }
                    } else if ($op->getAssetCode() === $expectedAssetCode) {
                         // Check exact match (ignoring precision errors)
                         if ($opReceiver === $expectedReceiver && abs($opAmount - $expectedAmount) < 0.00001) {
                            $foundMatch = true;
                            break;
                        }
                    }
                }
            }

            if ($foundMatch) {
                return ['success' => true];
            } else {
                return ['success' => false, 'message' => 'Transaction hash exists but details (amount/receiver/asset) do not match.'];
            }

        } catch (\Soneso\StellarSDK\Exceptions\HorizonRequestException $e) {
            if ($e->getStatusCode() === 404) {
                 return ['success' => false, 'message' => 'Transaction hash not found on the network.'];
            }
            Log::channel('security')->warning("Verify TX Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error verifying transaction against Horizon.'];
        } catch (\Throwable $e) {
            Log::channel('security')->error("Verify TX Fatal: " . $e->getMessage());
            return ['success' => false, 'message' => 'Internal error during verification: ' . $e->getMessage()];
        }
    }
}
