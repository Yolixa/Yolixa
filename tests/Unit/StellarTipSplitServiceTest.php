<?php

namespace Tests\Unit;

use App\Services\StellarConfigurationService;
use App\Services\StellarService;
use phpseclib3\Math\BigInteger;
use Soneso\StellarSDK\AbstractTransaction;
use Soneso\StellarSDK\Account;
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\PaymentOperation;
use Tests\TestCase;

class StellarTipSplitServiceTest extends TestCase
{
    public function test_xlm_tip_xdr_builds_creator_and_platform_fee_operations(): void
    {
        config([
            'yolixa.fee_percentage' => 0.015,
            'yolixa.network' => 'testnet',
            'yolixa.stellar_passphrases.testnet' => StellarConfigurationService::TESTNET_PASSPHRASE,
        ]);

        $sender = KeyPair::random()->getAccountId();
        $creator = KeyPair::random()->getAccountId();
        $platform = KeyPair::random()->getAccountId();
        $service = app(StellarService::class);
        $account = Account::fromAccountId($sender, new BigInteger(1));

        $result = $service->buildTipXdrFromAccount(
            $account,
            $sender,
            $creator,
            $platform,
            'XLM',
            $service->calculateSplitAmounts('10')
        );

        $this->assertTrue($result['success']);
        $this->assertSame('0.1500000', $result['platform_fee']);
        $this->assertSame('9.8500000', $result['creator_payout_amount']);

        $tx = AbstractTransaction::fromEnvelopeBase64XdrString($result['xdr']);
        $operations = $tx->getOperations();

        $this->assertCount(2, $operations);
        $this->assertInstanceOf(PaymentOperation::class, $operations[0]);
        $this->assertInstanceOf(PaymentOperation::class, $operations[1]);
        $this->assertSame($creator, $operations[0]->getDestination()->getAccountId());
        $this->assertSame('9.8500000', $operations[0]->getAmount());
        $this->assertSame($platform, $operations[1]->getDestination()->getAccountId());
        $this->assertSame('0.1500000', $operations[1]->getAmount());
        $this->assertSame(Asset::TYPE_NATIVE, $operations[0]->getAsset()->getType());
    }

    public function test_usdc_is_not_supported_without_issuer_config(): void
    {
        config(['yolixa.assets.USDC.issuer' => null, 'yolixa.assets.USDC.enabled' => false]);

        $this->assertNotContains('USDC', app(StellarService::class)->supportedTipAssets());
    }

    public function test_usdc_tip_xdr_builds_with_configured_issuer(): void
    {
        $sender = KeyPair::random()->getAccountId();
        $creator = KeyPair::random()->getAccountId();
        $platform = KeyPair::random()->getAccountId();
        $issuer = KeyPair::random()->getAccountId();

        config([
            'yolixa.assets.USDC.issuer' => $issuer,
            'yolixa.assets.USDC.enabled' => true,
            'yolixa.fee_percentage' => 0.015,
        ]);

        $service = app(StellarService::class);
        $result = $service->buildTipXdrFromAccount(
            Account::fromAccountId($sender, new BigInteger(1)),
            $sender,
            $creator,
            $platform,
            'USDC',
            $service->calculateSplitAmounts('25')
        );

        $this->assertTrue($result['success']);
        $tx = AbstractTransaction::fromEnvelopeBase64XdrString($result['xdr']);
        $asset = $tx->getOperations()[0]->getAsset();

        $this->assertSame('USDC', $asset->getCode());
        $this->assertSame($issuer, $asset->getIssuer());
        $this->assertSame('24.6250000', $tx->getOperations()[0]->getAmount());
        $this->assertSame('0.3750000', $tx->getOperations()[1]->getAmount());
    }
}
