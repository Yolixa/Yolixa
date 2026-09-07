<?php

namespace Tests\Unit;

use App\Services\StellarConfigurationService;
use InvalidArgumentException;
use Soneso\StellarSDK\Crypto\KeyPair;
use Tests\TestCase;

class StellarConfigurationServiceTest extends TestCase
{
    public function test_testnet_defaults_are_valid(): void
    {
        config([
            'yolixa.stellar_network' => 'testnet',
            'yolixa.network' => 'testnet',
            'yolixa.stellar_horizon' => 'https://horizon-testnet.stellar.org',
            'yolixa.stellar_passphrase' => StellarConfigurationService::TESTNET_PASSPHRASE,
            'yolixa.fee_percentage' => 0.015,
            'yolixa.min_payment_amount' => '0.0000001',
            'yolixa.max_payment_amount' => '1000',
        ]);

        app(StellarConfigurationService::class)->validate();

        $this->assertTrue(true);
    }

    public function test_mainnet_fails_closed_without_feature_flag(): void
    {
        config([
            'yolixa.stellar_network' => 'mainnet',
            'yolixa.network' => 'mainnet',
            'yolixa.stellar_horizon' => 'https://horizon.stellar.org',
            'yolixa.stellar_passphrase' => StellarConfigurationService::PUBLIC_PASSPHRASE,
            'yolixa.features.mainnet' => false,
            'yolixa.fee_percentage' => 0.015,
            'yolixa.min_payment_amount' => '0.0000001',
            'yolixa.max_payment_amount' => '1000',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Mainnet is disabled');

        app(StellarConfigurationService::class)->validate();
    }

    public function test_mainnet_requires_platform_public_key(): void
    {
        config([
            'yolixa.stellar_network' => 'mainnet',
            'yolixa.network' => 'mainnet',
            'yolixa.stellar_horizon' => 'https://horizon.stellar.org',
            'yolixa.stellar_passphrase' => StellarConfigurationService::PUBLIC_PASSPHRASE,
            'yolixa.features.mainnet' => true,
            'yolixa.platform_public_key' => null,
            'yolixa.fee_percentage' => 0.015,
            'yolixa.min_payment_amount' => '0.0000001',
            'yolixa.max_payment_amount' => '1000',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('YOLIXA_PLATFORM_PUBLIC_KEY');

        app(StellarConfigurationService::class)->validate();
    }

    public function test_public_key_validation_uses_stellar_key_format(): void
    {
        $service = app(StellarConfigurationService::class);
        $publicKey = KeyPair::random()->getAccountId();

        $this->assertTrue($service->isValidPublicKey($publicKey));
        $this->assertFalse($service->isValidPublicKey('not-a-stellar-key'));
    }
}
