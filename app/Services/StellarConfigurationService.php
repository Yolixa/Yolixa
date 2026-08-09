<?php

namespace App\Services;

use InvalidArgumentException;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\StellarSDK;

class StellarConfigurationService
{
    public const TESTNET_PASSPHRASE = 'Test SDF Network ; September 2015';
    public const PUBLIC_PASSPHRASE = 'Public Global Stellar Network ; September 2015';

    public function sdk(): StellarSDK
    {
        $this->validate();

        return new StellarSDK(rtrim($this->horizonUrl(), '/'));
    }

    public function networkName(): string
    {
        return strtolower((string) config('yolixa.network', config('yolixa.stellar_network', 'testnet')));
    }

    public function networkLabel(): string
    {
        return strtoupper($this->networkName());
    }

    public function passphrase(): string
    {
        return (string) config("yolixa.stellar_passphrases.{$this->networkName()}", config('yolixa.stellar_passphrase'));
    }

    public function horizonUrl(): string
    {
        return (string) config("yolixa.stellar_horizon_urls.{$this->networkName()}", config('yolixa.stellar_horizon'));
    }

    public function explorerTxUrl(?string $hash = null): string
    {
        $template = (string) config("yolixa.explorer_tx_urls.{$this->networkName()}", '');

        return $hash ? str_replace('{hash}', $hash, $template) : $template;
    }

    public function explorerAccountUrl(?string $account = null): string
    {
        $template = (string) config("yolixa.explorer_account_urls.{$this->networkName()}", '');

        return $account ? str_replace('{account}', $account, $template) : $template;
    }

    public function validate(): void
    {
        $network = $this->networkName();
        $horizon = $this->horizonUrl();
        $passphrase = $this->passphrase();

        if (!in_array($network, ['testnet', 'mainnet'], true)) {
            throw new InvalidArgumentException('Unsupported Stellar network. Use testnet or mainnet.');
        }

        if (!filter_var($horizon, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException('STELLAR_HORIZON must be a valid URL.');
        }

        if ($network === 'testnet' && $passphrase !== self::TESTNET_PASSPHRASE) {
            throw new InvalidArgumentException('Testnet mode requires the Stellar testnet network passphrase.');
        }

        if ($network === 'mainnet') {
            if (!config('yolixa.features.mainnet')) {
                throw new InvalidArgumentException('Mainnet is disabled. Set YOLIXA_FEATURE_MAINNET=true after completing the mainnet checklist.');
            }

            if ($passphrase !== self::PUBLIC_PASSPHRASE) {
                throw new InvalidArgumentException('Mainnet mode requires the public Stellar network passphrase.');
            }

            $platformKey = config('yolixa.platform_public_key');
            if (!$this->isValidPublicKey($platformKey)) {
                throw new InvalidArgumentException('Mainnet mode requires YOLIXA_PLATFORM_PUBLIC_KEY to be a valid Stellar public key.');
            }
        }

        $fee = (float) config('yolixa.fee_percentage', 0);
        if ($fee < 0 || $fee >= 1) {
            throw new InvalidArgumentException('YOLIXA_FEE_PERCENTAGE must be between 0 and 1.');
        }

        $min = (float) config('yolixa.min_payment_amount');
        $max = (float) config('yolixa.max_payment_amount');
        if ($min <= 0 || $max < $min) {
            throw new InvalidArgumentException('Payment amount limits are invalid.');
        }
    }

    public function isValidPublicKey(?string $publicKey): bool
    {
        if (!is_string($publicKey)) {
            return false;
        }

        try {
            KeyPair::fromAccountId($publicKey);

            return str_starts_with($publicKey, 'G') && strlen($publicKey) === 56;
        } catch (\Throwable) {
            return false;
        }
    }
}
