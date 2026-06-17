<?php

return [
    'fee_percentage' => env('YOLIXA_FEE_PERCENTAGE', 0.015), // 1.5% default, recorded for reporting.
    
    // YLX prices for supported assets
    'ylx_price' => [
        'XLM' => env('YOLIXA_YLX_PRICE_XLM', 0.1), // e.g. 1 YLX = 0.1 XLM -> 10 YLX per 1 XLM tip
        'USDC' => env('YOLIXA_YLX_PRICE_USDC', 0.05), // e.g. 1 YLX = 0.05 USDC -> 20 YLX per 1 USDC tip
    ],
    
    'supported_tip_assets' => explode(',', env('YOLIXA_SUPPORTED_TIP_ASSETS', 'XLM')),

    'stellar_horizon' => env('STELLAR_HORIZON', 'https://horizon-testnet.stellar.org'),

    'stellar_passphrase' => env('STELLAR_PASSPHRASE', 'Test SDF Network ; September 2015'),

    // For Option A: the platform collects the original asset
    'platform_collection_wallet' => env('PLATFORM_COLLECTION_PUBLIC', env('ISSUER_PUBLIC_KEY')),
    
    // For Option A: the platform pays out YLX
    'platform_distribution_seed' => env('PLATFORM_DISTRIBUTION_SECRET', env('ISSUER_SECRET_KEY')),
];
