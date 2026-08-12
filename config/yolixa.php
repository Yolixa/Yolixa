<?php

return [
    'network' => env('YOLIXA_NETWORK', env('STELLAR_NETWORK', 'testnet')),
    'stellar_network' => env('YOLIXA_NETWORK', env('STELLAR_NETWORK', 'testnet')),
    'stellar_horizon' => env('STELLAR_HORIZON', env('STELLAR_TESTNET_HORIZON_URL', 'https://horizon-testnet.stellar.org')),
    'stellar_passphrase' => env('STELLAR_PASSPHRASE', env('STELLAR_TESTNET_NETWORK_PASSPHRASE', 'Test SDF Network ; September 2015')),
    'stellar_horizon_urls' => [
        'testnet' => env('STELLAR_TESTNET_HORIZON_URL', 'https://horizon-testnet.stellar.org'),
        'mainnet' => env('STELLAR_MAINNET_HORIZON_URL', 'https://horizon.stellar.org'),
    ],
    'stellar_passphrases' => [
        'testnet' => env('STELLAR_TESTNET_NETWORK_PASSPHRASE', 'Test SDF Network ; September 2015'),
        'mainnet' => env('STELLAR_MAINNET_NETWORK_PASSPHRASE', 'Public Global Stellar Network ; September 2015'),
    ],
    'explorer_tx_urls' => [
        'testnet' => env('STELLAR_EXPLORER_TESTNET_TX_URL', 'https://stellar.expert/explorer/testnet/tx/{hash}'),
        'mainnet' => env('STELLAR_EXPLORER_MAINNET_TX_URL', 'https://stellar.expert/explorer/public/tx/{hash}'),
    ],
    'explorer_account_urls' => [
        'testnet' => env('STELLAR_EXPLORER_TESTNET_ACCOUNT_URL', 'https://stellar.expert/explorer/testnet/account/{account}'),
        'mainnet' => env('STELLAR_EXPLORER_MAINNET_ACCOUNT_URL', 'https://stellar.expert/explorer/public/account/{account}'),
    ],

    'platform_public_key' => env('YOLIXA_PLATFORM_WALLET_PUBLIC', env('YOLIXA_PLATFORM_PUBLIC_KEY')),
    'fee_percentage' => env('YOLIXA_PLATFORM_FEE_PERCENT', env('YOLIXA_FEE_PERCENTAGE', 0.015)),
    'min_payment_amount' => env('YOLIXA_MIN_PAYMENT_AMOUNT', 0.0000001),
    'max_payment_amount' => env('YOLIXA_MAX_PAYMENT_AMOUNT', 1000),
    'wallet_challenge_ttl_seconds' => env('YOLIXA_WALLET_CHALLENGE_TTL_SECONDS', 300),
    'tip_execution_mode' => env('YOLIXA_TIP_EXECUTION_MODE', 'soroban'),

    'enabled_wallets' => array_values(array_filter(array_map(
        'trim',
        explode(',', env('YOLIXA_ENABLED_WALLETS', 'freighter,rabet'))
    ))),

    'features' => [
        'xlm_payments' => filter_var(env('YOLIXA_FEATURE_XLM_PAYMENTS', true), FILTER_VALIDATE_BOOL),
        'issued_assets' => filter_var(env('YOLIXA_FEATURE_ISSUED_ASSETS', false), FILTER_VALIDATE_BOOL),
        'revenue_splitting' => filter_var(env('YOLIXA_FEATURE_REVENUE_SPLITTING', false), FILTER_VALIDATE_BOOL),
        'mainnet' => filter_var(env('YOLIXA_FEATURE_MAINNET', false), FILTER_VALIDATE_BOOL),
    ],

    'asset' => [
        'code' => env('YOLIXA_ASSET_CODE', 'XLM'),
        'issuer' => env('YOLIXA_ASSET_ISSUER'),
        'display_name' => env('YOLIXA_ASSET_DISPLAY_NAME', 'Stellar Lumens'),
        'enabled' => filter_var(env('YOLIXA_ASSET_ENABLED', true), FILTER_VALIDATE_BOOL),
    ],

    'assets' => [
        'XLM' => [
            'code' => 'XLM',
            'issuer' => null,
            'enabled' => true,
            'display_name' => 'Stellar Lumens',
        ],
        'USDC' => [
            'code' => env('USDC_ASSET_CODE', 'USDC'),
            'issuer' => env('USDC_ISSUER_PUBLIC'),
            'enabled' => filled(env('USDC_ISSUER_PUBLIC')),
            'display_name' => 'USD Coin',
        ],
    ],

    // YLX prices for supported assets
    'ylx_price' => [
        'XLM' => env('YOLIXA_YLX_PRICE_XLM', 0.1), // e.g. 1 YLX = 0.1 XLM -> 10 YLX per 1 XLM tip
        'USDC' => env('YOLIXA_YLX_PRICE_USDC', 0.05), // e.g. 1 YLX = 0.05 USDC -> 20 YLX per 1 USDC tip
    ],

    'supported_tip_assets' => explode(',', env('YOLIXA_SUPPORTED_TIP_ASSETS', 'XLM,USDC')),
    'ylx_reward_rate_percent' => env('YLX_REWARD_RATE_PERCENT', 1),

    // Legacy name retained for existing views/services. Prefer platform_public_key in new code.
    'platform_collection_wallet' => env('PLATFORM_COLLECTION_PUBLIC', env('YOLIXA_PLATFORM_WALLET_PUBLIC', env('YOLIXA_PLATFORM_PUBLIC_KEY'))),

    // For Option A: the platform pays out YLX
    'platform_distribution_seed' => env('PLATFORM_DISTRIBUTION_SECRET', env('ISSUER_SECRET_KEY')),

    'ylx_asset' => [
        'code' => env('YLX_ASSET_CODE', 'YLX'),
        'issuer' => env('YLX_ISSUER_PUBLIC'),
    ],

    'soroban' => [
        'enabled' => filter_var(env('SOROBAN_ENABLED', true), FILTER_VALIDATE_BOOL),
        'rpc_url' => env('SOROBAN_RPC_URL', 'https://soroban-testnet.stellar.org'),
        'tip_router_contract_id' => env('SOROBAN_TIP_ROUTER_CONTRACT_ID'),
        'xlm_token_contract_id' => env('SOROBAN_XLM_TOKEN_CONTRACT_ID'),
        'tip_intent_ttl_minutes' => env('SOROBAN_TIP_INTENT_TTL_MINUTES', 30),
        'fee_bps' => env('SOROBAN_TIP_ROUTER_FEE_BPS', 150),

        // Legacy receipt-only registry. The Phase 2 router path does not use these.
        'tip_registry_contract_id' => env('SOROBAN_TIP_REGISTRY_CONTRACT_ID'),
        'platform_signer_public' => env('SOROBAN_PLATFORM_SIGNER_PUBLIC'),
        'platform_signer_secret' => env('SOROBAN_PLATFORM_SIGNER_SECRET'),
    ],
];
