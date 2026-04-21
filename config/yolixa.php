<?php

return [
    'fee_percentage' => env('YOLIXA_FEE_PERCENTAGE', 0.05), // 5% default
    
    // YLX prices for supported assets
    'ylx_price' => [
        'XLM' => env('YOLIXA_YLX_PRICE_XLM', 0.1), // e.g. 1 YLX = 0.1 XLM -> 10 YLX per 1 XLM tip
        'USDC' => env('YOLIXA_YLX_PRICE_USDC', 0.05), // e.g. 1 YLX = 0.05 USDC -> 20 YLX per 1 USDC tip
    ],
    
    'supported_tip_assets' => explode(',', env('YOLIXA_SUPPORTED_TIP_ASSETS', 'XLM,USDC,YLX')),

    // For Option A: the platform collects the original asset
    'platform_collection_wallet' => env('PLATFORM_COLLECTION_PUBLIC', env('ISSUER_PUBLIC_KEY')),
    
    // For Option A: the platform pays out YLX
    'platform_distribution_seed' => env('PLATFORM_DISTRIBUTION_SECRET', env('ISSUER_SECRET_KEY')),
];
