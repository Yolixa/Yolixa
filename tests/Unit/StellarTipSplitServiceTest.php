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
    public function test_xlm_tip_xdr_builds_single_direct_creator_payment(): void
    {
        config([
            'yolixa.fee_percentage' => 0,
            'yolixa.network' => 'testnet',
            'yolixa.stellar_passphrases.testnet' => StellarConfigurationService::TESTNET_PASSPHRASE,
        ]);

        $sender = KeyPair::random()->getAccountId();
        $creator = KeyPair::random()->getAccountId();
        $service = app(StellarService::class);
        $account = Account::fromAccountId($sender, new BigInteger(1));

        $result = $service->buildTipXdrFromAccount(
            $account,
            $sender,
            $creator,
            null,
            'XLM',
            $service->calculateSplitAmounts('10')
        );

        $this->assertTrue($result['success']);
        $this->assertSame('0.0000000', $result['platform_fee']);
        $this->assertSame('10.0000000', $result['creator_payout_amount']);

        $tx = AbstractTransaction::fromEnvelopeBase64XdrString($result['xdr']);
        $operations = $tx->getOperations();

        $this->assertCount(1, $operations);
        $this->assertInstanceOf(PaymentOperation::class, $operations[0]);
        $this->assertSame($creator, $operations[0]->getDestination()->getAccountId());
        $this->assertSame('10.0000000', $operations[0]->getAmount());
        $this->assertSame(Asset::TYPE_NATIVE, $operations[0]->getAsset()->getType());
    }

    public function test_usdc_is_not_supported_without_issuer_config(): void
    {
        config(['yolixa.assets.USDC.issuer' => null, 'yolixa.assets.USDC.enabled' => false]);

        $this->assertNotContains('USDC', app(StellarService::class)->supportedTipAssets());
    }

    public function test_usdc_is_not_supported_even_with_issuer_config_in_current_mvp(): void
    {
        $issuer = KeyPair::random()->getAccountId();

        config([
            'yolixa.assets.USDC.issuer' => $issuer,
            'yolixa.assets.USDC.enabled' => true,
        ]);

        $this->assertSame(['XLM'], app(StellarService::class)->supportedTipAssets());
    }

    public function test_tip_input_validation_uses_current_xlm_amount_helper(): void
    {
        config([
            'yolixa.min_payment_amount' => '0.0000001',
            'yolixa.max_payment_amount' => '1000',
        ]);

        $service = app(StellarService::class);
        $method = new \ReflectionMethod(StellarService::class, 'validateTipInputs');

        $result = $method->invoke(
            $service,
            KeyPair::random()->getAccountId(),
            KeyPair::random()->getAccountId(),
            'XLM',
            '1.0000000'
        );

        $this->assertSame(['success' => true], $result);

        $tooPrecise = $method->invoke(
            $service,
            KeyPair::random()->getAccountId(),
            KeyPair::random()->getAccountId(),
            'XLM',
            '1.00000001'
        );

        $this->assertFalse($tooPrecise['success']);
        $this->assertSame('Enter a valid XLM amount with up to 7 decimal places.', $tooPrecise['message']);
    }
}
