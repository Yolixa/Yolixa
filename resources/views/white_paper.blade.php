<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="{{ asset('assets/images/favicon-32x32.png') }}">
    <title>Yolixa Whitepaper - Decentralized Tipping Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'yolixa-blue': '#3b82f6',
                        'yolixa-purple': '#8b5cf6',
                        'dark-bg': '#0f172a'
                    }
                }
            }
        }
    </script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap');

        * {
            font-family: 'Inter', sans-serif;
        }

        .gradient-bg {
            background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
        }

        .gradient-text {
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .page {
            min-height: 100vh;
            page-break-after: always;
            padding: 2rem;
            background: white;
            color: #1e293b;
        }

        .page:last-child {
            page-break-after: avoid;
        }

        .section-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .icon-circle {
            width: 90px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
        }

        .tokenomics-chart {
            width: 200px;
            height: 200px;
            border-radius: 50%;
            background: conic-gradient(from 0deg,
                    #3b82f6 0deg 144deg,
                    #8b5cf6 144deg 234deg,
                    #10b981 234deg 306deg,
                    #f59e0b 306deg 342deg,
                    #ef4444 342deg 360deg);
            margin: 0 auto;
            position: relative;
        }

        .tokenomics-chart::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 100px;
            height: 100px;
            background: white;
            border-radius: 50%;
        }

        .timeline-item {
            position: relative;
            padding-left: 3rem;
            margin-bottom: 2rem;
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
        }

        .timeline-item::after {
            content: '';
            position: absolute;
            left: 9px;
            top: 20px;
            width: 2px;
            height: calc(100% + 1rem);
            background: linear-gradient(to bottom, #3b82f6, #8b5cf6);
        }

        .timeline-item:last-child::after {
            display: none;
        }

        @media print {
            .page {
                page-break-after: always;
                margin: 0;
                padding: 1rem;
            }

            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }

        .workflow-step {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            border: 2px solid transparent;
            background-clip: padding-box;
            position: relative;
        }

        .workflow-step::before {
            content: '';
            position: absolute;
            inset: 0;
            padding: 2px;
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            border-radius: inherit;
            mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            mask-composite: exclude;
        }
    </style>
</head>

<body class="bg-white text-slate-800">
    <!-- Cover Page -->
    <div class="page flex flex-col justify-center items-center text-center relative overflow-hidden">
        <!-- Background Elements -->
        <div class="absolute top-20 left-20 w-32 h-32 gradient-bg rounded-full opacity-10 blur-xl"></div>
        <div class="absolute bottom-20 right-20 w-40 h-40 gradient-bg rounded-full opacity-10 blur-xl"></div>

        <!-- Logo Placeholder -->
        <div class="mb-12">
            <div class="w-32 h-32 gradient-bg rounded-3xl flex items-center justify-center shadow-2xl">
                <svg class="w-16 h-16 text-white" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5" />
                </svg>
            </div>
        </div>

        <!-- Title -->
        <h1 class="text-7xl font-black mb-6 gradient-text leading-tight">
            Yolixa
        </h1>

        <!-- Tagline -->
        <h2 class="text-3xl font-bold mb-12 text-slate-600">
            Decentralized Tipping. Empowering Creators Globally.
        </h2>

        <!-- Subtitle -->
        <div class="section-card max-w-2xl">
            <p class="text-xl font-medium text-slate-700">
                A Revolutionary Blockchain Platform Built on Stellar Network
            </p>
        </div>

        <!-- Version & Date -->
        <div class="absolute bottom-8 text-slate-500">
            <p class="text-lg font-medium">Whitepaper v1.0 | 2025</p>
        </div>
    </div>

    <!-- Introduction Page -->
    <div class="page">
        <div class="max-w-4xl mx-auto">
            <!-- Heading -->
            <div class="flex items-center mb-8">
                <div class="w-12 h-12 gradient-bg rounded-xl flex items-center justify-center mr-4">
                    <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h2 class="text-5xl font-black gradient-text">Introduction</h2>
            </div>

            <!-- Welcome Section -->
            <div class="section-card">
                <h3 class="text-2xl font-bold mb-6 text-slate-800">Welcome to the Future of the Creator Economy</h3>
                <p class="text-lg leading-relaxed text-slate-700 mb-6">
                    Yolixa is a decentralized tipping platform built on the Stellar blockchain.
                    Our mission is to empower creators and influencers by enabling direct, borderless,
                    and transparent tips from their audience—without traditional payment intermediaries.
                </p>

                <div class="grid md:grid-cols-2 gap-8 mt-8">
                    <!-- What is Yolixa -->
                    <div>
                        <h4 class="text-xl font-bold mb-4 text-yolixa-blue">What is Yolixa?</h4>
                        <ul class="space-y-3 text-slate-700">
                            <li class="flex items-start">
                                <div class="w-2 h-2 bg-yolixa-blue rounded-full mt-2 mr-3 flex-shrink-0"></div>
                                <span>A decentralized tipping platform powered by the Stellar blockchain.</span>
                            </li>
                            <li class="flex items-start">
                                <div class="w-2 h-2 bg-yolixa-blue rounded-full mt-2 mr-3 flex-shrink-0"></div>
                                <span>Influencers register and receive unique referral links.</span>
                            </li>
                            <li class="flex items-start">
                                <div class="w-2 h-2 bg-yolixa-blue rounded-full mt-2 mr-3 flex-shrink-0"></div>
                                <span>Fans can connect wallets and tip instantly in XLM/USDC.</span>
                            </li>
                            <li class="flex items-start">
                                <div class="w-2 h-2 bg-yolixa-blue rounded-full mt-2 mr-3 flex-shrink-0"></div>
                                <span>A small platform fee supports ecosystem growth and long-term stability while keeping creators first.</span>
                            </li>
                        </ul>
                    </div>

                    <!-- Key Benefits -->
                    <div>
                        <h4 class="text-xl font-bold mb-4 text-yolixa-purple">Key Benefits</h4>
                        <ul class="space-y-3 text-slate-700">
                            <li class="flex items-start">
                                <div class="w-2 h-2 bg-yolixa-purple rounded-full mt-2 mr-3 flex-shrink-0"></div>
                                <span>Direct, borderless tipping without intermediaries.</span>
                            </li>
                            <li class="flex items-start">
                                <div class="w-2 h-2 bg-yolixa-purple rounded-full mt-2 mr-3 flex-shrink-0"></div>
                                <span>Ultra-low transaction costs on Stellar (under $0.01).</span>
                            </li>
                            <li class="flex items-start">
                                <div class="w-2 h-2 bg-yolixa-purple rounded-full mt-2 mr-3 flex-shrink-0"></div>
                                <span>Creators earn extra YLX token rewards with every tip.</span>
                            </li>
                            <li class="flex items-start">
                                <div class="w-2 h-2 bg-yolixa-purple rounded-full mt-2 mr-3 flex-shrink-0"></div>
                                <span>Transparent, secure, and instant settlements directly to non-custodial wallets.</span>
                            </li>
                            <li class="flex items-start">
                                <div class="w-2 h-2 bg-yolixa-purple rounded-full mt-2 mr-3 flex-shrink-0"></div>
                                <span>Fans are also rewarded—driving engagement and long-term loyalty.</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Market Opportunity -->
            <div class="section-card">
                <h3 class="text-2xl font-bold mb-6 text-slate-800">Market Opportunity</h3>
                <div class="grid md:grid-cols-3 gap-6">
                    <div class="text-center">
                        <div class="text-4xl font-black gradient-text mb-2">$104B</div>
                        <p class="text-slate-600">Global Creator Economy</p>
                    </div>
                    <div class="text-center">
                        <div class="text-4xl font-black gradient-text mb-2">50M+</div>
                        <p class="text-slate-600">Content Creators Worldwide</p>
                    </div>
                    <div class="text-center">
                        <div class="text-4xl font-black gradient-text mb-2">$15B</div>
                        <p class="text-slate-600">Annual Tipping Market</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Problem Page -->
    <div class="page">
        <div class="max-w-4xl mx-auto">
            <!-- Heading -->
            <div class="flex items-center mb-8">
                <div class="w-12 h-12 bg-red-500 rounded-xl flex items-center justify-center mr-4">
                    <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                    </svg>
                </div>
                <h2 class="text-5xl font-black text-red-500">Problem</h2>
            </div>

            <!-- Challenges -->
            <div class="section-card">
                <h3 class="text-2xl font-bold mb-6 text-slate-800">Current Challenges in Digital Tipping</h3>
                <p class="text-lg text-slate-700 mb-8">
                    Although the creator economy is booming, current tipping platforms remain inefficient, costly, and geographically restrictive—leaving creators underpaid, audiences disengaged, and millions excluded.
                </p>

                <div class="grid md:grid-cols-2 gap-8">
                    <div class="space-y-6">
                        <!-- High Fees -->
                        <div class="flex items-start space-x-4">
                            <div class="icon-circle bg-red-100" style="width: 95px;">
                                <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M3 6a2.25 2.25 0 012.25-2.25h15A2.25 2.25 0 0121.75 6v12a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 18V6z" />
                                </svg>
                            </div>
                            <div>
                                <h4 class="text-xl font-bold mb-2 text-red-600">High Platform Fees</h4>
                                <p class="text-slate-700">Tipping platforms cut 3–8% in fees, reducing creator earnings.</p>
                            </div>
                        </div>

                        <!-- Delayed Payments -->
                        <div class="flex items-start space-x-4">
                            <div class="icon-circle bg-red-100" style="width: 130px;">
                                <svg class="w-6 h-6 text-red-500" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <h4 class="text-xl font-bold mb-2 text-red-600">Delayed Payouts</h4>
                                <p class="text-slate-700">Creators typically wait 7–30 days to receive funds, creating unnecessary cash flow challenges.</p>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <!-- Limited Reach -->
                        <div class="flex items-start space-x-4">
                            <div class="icon-circle bg-red-100" style="width: 110px;">
                                <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c4.97 0 9 4.03 9 9s-4.03 9-9 9-9-4.03-9-9 4.03-9 9-9z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12h19.5M12 2.25v19.5" />
                                </svg>
                            </div>
                            <div>
                                <h4 class="text-xl font-bold mb-2 text-red-600">Limited Global Access</h4>
                                <p class="text-slate-700">Banking limits keep millions of creators in developing regions from earning.</p>
                            </div>
                        </div>

                        <!-- Low Engagement -->
                        <div class="flex items-start space-x-4">
                            <div class="icon-circle bg-red-100" style="width: 130px;">
                                <svg class="w-6 h-6 text-red-500" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z" />
                                </svg>
                            </div>
                            <div>
                                <h4 class="text-xl font-bold mb-2 text-red-600">Low Audience Engagement</h4>
                                <p class="text-slate-700">Current platforms lack incentives for repeat tipping, leading to weak creator–fan relationships.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cost of Inefficiency -->
            <div class="section-card bg-red-50 border-red-200">
                <h3 class="text-2xl font-bold mb-4 text-red-700">The Cost of Inefficiency</h3>
                <div class="grid md:grid-cols-3 gap-6 text-center">
                    <div>
                        <div class="text-3xl font-black text-red-600 mb-2">Billions Lost</div>
                        <p class="text-red-700">Creators collectively lose billions to platform fees every year.</p>
                    </div>
                    <div>
                        <div class="text-3xl font-black text-red-600 mb-2">60%+</div>
                        <p class="text-red-700">Creators earn significantly less than their true potential income.</p>
                    </div>
                    <div>
                        <div class="text-3xl font-black text-red-600 mb-2">2B+</div>
                        <p class="text-red-700">People remain excluded from the global digital economy.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Solution Page -->
    <div class="page">
        <div class="max-w-4xl mx-auto">
            <div class="flex items-center mb-8">
                <div class="w-12 h-12 bg-green-500 rounded-xl flex items-center justify-center mr-4">
                    <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                    </svg>
                </div>
                <h2 class="text-5xl font-black text-green-500">Solution</h2>
            </div>

            <div class="section-card">
                <h3 class="text-2xl font-bold mb-6 gradient-text">Yolixa: The Future of Borderless Tipping</h3>
                <p class="text-lg text-slate-700 mb-8">
                    Yolixa is creating the first truly global, blockchain-powered tipping ecosystem for the creator economy. Built on Stellar and fueled by the YLX token, it delivers instant, low-cost, and transparent payments—while unlocking sustainable rewards and stronger engagement for creators and supporters alike.
                </p>

                <div class="grid md:grid-cols-2 gap-8">
                    <div class="space-y-6">
                        <div class="flex items-start space-x-4">
                            <div class="icon-circle gradient-bg" style="width:125px;">
                                <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            </div>
                            <div>
                                <h4 class="text-xl font-bold mb-2 text-green-600">Instant Settlements</h4>
                                <p class="text-slate-700">Transactions confirm within <span class="font-semibold">3–5 seconds</span> via Stellar’s high-speed consensus protocol.</p>
                            </div>
                        </div>

                        <div class="flex items-start space-x-4">
                            <div class="icon-circle gradient-bg" style="width:120px;">
                                <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 1C5.925 1 1 5.925 1 12s4.925 11 11 11 11-4.925 11-11S18.075 1 12 1zm0 18a7 7 0 110-14 7 7 0 010 14zm.75-10h-1.5v2.25H9.75v1.5h1.5V15h1.5v-2.25h1.5v-1.5h-1.5V9z" />
                                </svg>
                            </div>
                            <div>
                                <h4 class="text-xl font-bold mb-2 text-green-600">Minimal Fees</h4>
                                <p class="text-slate-700">Average transaction costs are less than <span class="font-semibold">$0.01</span>, making micro-tipping scalable worldwide.</p>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <div class="flex items-start space-x-4">
                            <div class="icon-circle gradient-bg" style="width:135px;">
                                <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                                </svg>
                            </div>
                            <div>
                                <h4 class="text-xl font-bold mb-2 text-green-600">YLX Token Utility</h4>
                                <p class="text-slate-700">
                                    YLX tokens reward users while enabling staking, premium features, and long-term participation in the ecosystem.
                                </p>
                            </div>
                        </div>

                        <div class="flex items-start space-x-4">
                            <div class="icon-circle gradient-bg" style="width:120px;">
                                <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 2C6.486 2 2 6.486 2 12s4.486 10 10 10
                                    10-4.486 10-10S17.514 2 12 2zm0 18c-1.654 0-3-3.582-3-8s1.346-8 3-8
                                    3 3.582 3 8-1.346 8-3 8zm-7-8c0-1.542.29-2.979.793-4.243l2.53 1.461
                                    A17.104 17.104 0 007 12c0 1.355.216 2.633.578 3.782l-2.53 1.461
                                    A9.958 9.958 0 015 12zm14 0c0 1.542-.29 2.979-.793 4.243l-2.53-1.461
                                    c.362-1.149.578-2.427.578-3.782s-.216-2.633-.578-3.782l2.53-1.461
                                    A9.958 9.958 0 0119 12z" />
                                </svg>
                            </div>
                            <div>
                                <h4 class="text-xl font-bold mb-2 text-green-600">Global Accessibility</h4>
                                <p class="text-slate-700">Anyone with internet can send or receive tips—no banks, no borders, no exclusions.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="section-card bg-green-50 border-green-200">
                <h3 class="text-2xl font-bold mb-6 text-green-700">Why Stellar Blockchain?</h3>
                <div class="grid md:grid-cols-4 gap-6">
                    <div class="text-center">
                        <div class="text-3xl font-black text-green-600 mb-2">3–5s</div>
                        <p class="text-green-700">Transaction Speed</p>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl font-black text-green-600 mb-2">&lt;$0.01</div>
                        <p class="text-green-700">Average Fee</p>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl font-black text-green-600 mb-2">99.99%</div>
                        <p class="text-green-700">Network Uptime</p>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl font-black text-green-600 mb-2">Cross-Border</div>
                        <p class="text-green-700">Global Payments</p>
                    </div>
                </div>
                <p class="text-sm text-slate-500 mt-6 text-center">
                    Yolixa is designed with a compliance-ready, non-custodial architecture, ensuring transparency,
                    security, and scalability for future regulations in the digital economy.
                    The platform is aligned with Stellar ecosystem standards (e.g. trustlines, asset flags, and
                    SEP-based integrations) to remain interoperable and regulation-friendly.
                </p>
            </div>

            <!-- Compliance & Governance Section -->
            <div class="section-card bg-blue-50 border-blue-200">
                <h3 class="text-2xl font-bold mb-4 text-blue-700">Compliance & Governance</h3>
                <p class="text-slate-700 mb-4">
                    Yolixa is built with a long-term, compliance-aware mindset. While the early phase focuses on
                    product-market fit, the technical and economic design already follows best practices from
                    the Stellar ecosystem and Web3 industry.
                </p>
                <ul class="space-y-2 text-slate-700">
                    <li>• Non-custodial model: user funds settle directly into user-controlled Stellar wallets.</li>
                    <li>• Trustline-based asset access, enabling granular control for users and partners.</li>
                    <li>• Compatibility with Stellar’s SEP standards (e.g., SEP-0001, SEP-0005, SEP-0010) for
                        future integrations and compliance tooling.</li>
                    <li>• Transparent on-chain activity with verifiable token supply, rewards, and burns.</li>
                    <li>• Governance vision: over time, community feedback and YLX holders will help guide
                        key ecosystem decisions.</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Platform Workflow Page -->
    <div class="page">
        <div class="max-w-4xl mx-auto">
            <div class="flex items-center mb-8">
                <div class="w-12 h-12 gradient-bg rounded-xl flex items-center justify-center mr-4">
                    <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347M6.75 15a.75.75 0 100-1.5.75.75 0 000 1.5z" />
                    </svg>
                </div>
                <h2 class="text-5xl font-black gradient-text">Platform Workflow</h2>
            </div>

            <div class="section-card">
                <h3 class="text-2xl font-bold mb-8 text-center text-slate-800">Simple 3-Step Process</h3>

                <div class="space-y-8">
                    <!-- Step 1 -->
                    <div class="workflow-step rounded-2xl p-6">
                        <div class="flex items-center mb-4">
                            <div class="w-16 h-16 gradient-bg rounded-full flex items-center justify-center text-2xl font-bold text-white mr-6">1</div>
                            <div>
                                <h4 class="text-2xl font-bold text-slate-800">Creator Registration</h4>
                                <p class="text-yolixa-blue font-semibold">Wallet Setup</p>
                            </div>
                        </div>
                        <p class="text-slate-700 ml-22">
                            Creators sign up, connect their Stellar wallet, set up a trustline to YLX, and receive a unique referral link
                            to share with fans. Creators keep ~98–99% of each tip, with instant settlement to their own non-custodial wallet.
                        </p>
                    </div>

                    <!-- Step 2 -->
                    <div class="workflow-step rounded-2xl p-6">
                        <div class="flex items-center mb-4">
                            <div class="w-16 h-16 gradient-bg rounded-full flex items-center justify-center text-2xl font-bold text-white mr-6">2</div>
                            <div>
                                <h4 class="text-2xl font-bold text-slate-800">Fan Tipping</h4>
                                <p class="text-yolixa-purple font-semibold">Seamless Contribution</p>
                            </div>
                        </div>
                        <p class="text-slate-700 ml-22">
                            Fans open the creator’s referral link, connect their Stellar wallet, create a trustline to YLX to receive bonus rewards,
                            and tip directly in XLM or USDC — no traditional account signup required. The experience is fast, transparent,
                            and borderless.
                        </p>
                    </div>

                    <!-- Step 3 -->
                    <div class="workflow-step rounded-2xl p-6">
                        <div class="flex items-center mb-4">
                            <div class="w-16 h-16 gradient-bg rounded-full flex items-center justify-center text-2xl font-bold text-white mr-6">3</div>
                            <div>
                                <h4 class="text-2xl font-bold text-slate-800">Instant Rewards</h4>
                                <p class="text-green-600 font-semibold">Fair Distribution</p>
                            </div>
                        </div>
                        <p class="text-slate-700 ml-22">
                            Every tip is instantly recorded on-chain. Creators receive the majority of the tip amount plus a YLX bonus,
                            while fans also earn YLX rewards for supporting. Reward mechanics are designed to grow the ecosystem
                            without creating unsustainable sell pressure.
                        </p>
                    </div>
                </div>
            </div>

            <div class="section-card bg-blue-50 border-blue-200">
                <h3 class="text-2xl font-bold mb-4 text-blue-700">Platform Benefits</h3>
                <div class="grid md:grid-cols-2 gap-8">
                    <div>
                        <h4 class="font-bold text-blue-600 mb-3">For Creators</h4>
                        <ul class="space-y-2 text-slate-700">
                            <li>• Instant Stellar settlement.</li>
                            <li>• Global access without banking limits.</li>
                            <li>• Bonus YLX on every tip and engagement.</li>
                            <li>• Easy referral-based onboarding.</li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="font-bold text-blue-600 mb-3">For Supporters</h4>
                        <ul class="space-y-2 text-slate-700">
                            <li>• No centralized account signup.</li>
                            <li>• Tip directly in XLM/USDC.</li>
                            <li>• Earn YLX rewards for participation.</li>
                            <li>• Fast, transparent blockchain transfers.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Token Utility Page -->
    <div class="page">
        <div class="max-w-4xl mx-auto">
            <!-- Section Heading -->
            <div class="flex items-center mb-8">
                <div class="w-12 h-12 gradient-bg rounded-xl flex items-center justify-center mr-4">
                    <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                    </svg>
                </div>
                <h2 class="text-5xl font-black gradient-text">Token Utility (YLX)</h2>
            </div>

            <!-- Intro -->
            <div class="section-card">
                <h3 class="text-2xl font-bold mb-6 text-slate-800">The Core of Yolixa Economy</h3>
                <p class="text-lg text-slate-700 mb-8">
                    The Yolixa Token (YLX) is not just a tipping token—it is the backbone of the Yolixa ecosystem.
                    It fuels tipping, rewards, and engagement while ensuring long-term value for creators, supporters, and stakeholders.
                </p>

                <!-- Token Utility Grid -->
                <div class="grid md:grid-cols-2 gap-8">
                    <div class="space-y-6">
                        <!-- Tipping -->
                        <div class="flex items-start space-x-4">
                            <div class="icon-circle gradient-bg" style="width: 120px; height: 55px;">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.5 0-2.5.75-2.5 1.75S10.5 11.5 12 11.5s2.5.75 2.5 1.75S13.5 15 12 15m0-7V7m0 8v1m0 4.5a9.5 9.5 0 100-19 9.5 9.5 0 000 19z" />
                                </svg>
                            </div>
                            <div>
                                <h4 class="text-xl font-bold mb-2 text-yolixa-blue">Tipping Currency</h4>
                                <p class="text-slate-700">Fans can tip directly in YLX or get extra rewards when tipping in XLM/USDC that auto-converts to YLX in the reward logic.</p>
                            </div>
                        </div>

                        <!-- Rewards -->
                        <div class="flex items-start space-x-4">
                            <div class="icon-circle gradient-bg" style="width: 120px; height: 55px;">
                                <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                                </svg>
                            </div>
                            <div>
                                <h4 class="text-xl font-bold mb-2 text-yolixa-blue">Engagement Rewards</h4>
                                <p class="text-slate-700">Creators and fans both earn YLX as bonuses for tipping activity, referrals, and platform engagement.</p>
                            </div>
                        </div>

                        <!-- Discounts -->
                        <div class="flex items-start space-x-4">
                            <div class="icon-circle gradient-bg" style="width: 120px; height: 55px;">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 9h.01M15 15h.01M7.5 16.5l9-9m1.086-3.414a2 2 0 012.828 0l1.5 1.5a2 2 0 010 2.828l-9.75 9.75a2 2 0 01-1.414.586H6.75a2.25 2.25 0 01-2.25-2.25v-4.5c0-.398.158-.78.44-1.06l9.75-9.75z" />
                                </svg>
                            </div>
                            <div>
                                <h4 class="text-xl font-bold mb-2 text-yolixa-blue">Discounts & Benefits</h4>
                                <p class="text-slate-700">Holding YLX unlocks reduced fees, premium platform tools, and priority access to new features.</p>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <!-- Access -->
                        <div class="flex items-start space-x-4">
                            <div class="icon-circle gradient-bg" style="width: 115px; height: 58px;">
                                <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                                </svg>
                            </div>
                            <div>
                                <h4 class="text-xl font-bold mb-2 text-yolixa-purple">Exclusive Access</h4>
                                <p class="text-slate-700">YLX holders unlock premium creator content, beta features, and VIP community benefits.</p>
                            </div>
                        </div>

                        <!-- Staking -->
                        <div class="flex items-start space-x-4">
                            <div class="icon-circle gradient-bg" style="width: 115px; height: 58px;">
                                <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M3 7v10a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2z" />
                                </svg>
                            </div>
                            <div>
                                <h4 class="text-xl font-bold mb-2 text-yolixa-purple">Staking Rewards</h4>
                                <p class="text-slate-700">Stake YLX tokens to earn consistent passive rewards and strengthen long-term engagement.</p>
                            </div>
                        </div>

                        <!-- Referral -->
                        <div class="flex items-start space-x-4">
                            <div class="icon-circle gradient-bg" style="width: 100px; height: 60px;">
                                <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.5 20a7.5 7.5 0 0115 0c-2.5 1-5 1.75-7.5 1.75S7 21 4.5 20z" />
                                </svg>
                            </div>
                            <div>
                                <h4 class="text-xl font-bold mb-2 text-yolixa-purple">Referral Bonuses</h4>
                                <p class="text-slate-700">Earn extra YLX by inviting new creators and fans to join the ecosystem.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Token Economics -->
            <div class="section-card bg-purple-50 border-purple-200">
                <h3 class="text-2xl font-bold mb-6 text-purple-700">Token Economics Model</h3>
                <div class="grid md:grid-cols-3 gap-6">
                    <div class="text-center">
                        <div class="text-3xl font-black text-purple-600 mb-2">Deflationary</div>
                        <p class="text-purple-700">
                            A portion of platform revenue (e.g., 1% of fees) is periodically used to buy back and burn YLX,
                            reducing circulating supply and increasing scarcity over time.
                        </p>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl font-black text-purple-600 mb-2">Utility-Driven</div>
                        <p class="text-purple-700">
                            Real-world tipping, staking, and reward use cases create organic, sustainable demand for YLX.
                        </p>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl font-black text-purple-600 mb-2">Community-Led</div>
                        <p class="text-purple-700">
                            Over time, YLX holders and creators will help shape key parameters, rewards, and ecosystem expansions.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tokenomics Page -->
    <div class="page">
        <div class="max-w-4xl mx-auto">
            <!-- Heading -->
            <div class="flex items-center mb-8">
                <div class="w-12 h-12 gradient-bg rounded-xl flex items-center justify-center mr-4">
                    <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10
                   10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5
                   1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z" />
                    </svg>
                </div>
                <h2 class="text-5xl font-black gradient-text">Tokenomics</h2>
            </div>

            <!-- Token Overview -->
            <div class="section-card">
                <div class="grid md:grid-cols-2 gap-8">
                    <div>
                        <h3 class="text-2xl font-bold mb-6 text-slate-800">Token Overview</h3>
                        <div class="space-y-4">
                            <div class="flex justify-between items-center py-3 border-b border-slate-200">
                                <span class="text-slate-600">Token Name:</span>
                                <span class="font-bold">Yolixa Token (YLX)</span>
                            </div>
                            <div class="flex justify-between items-center py-3 border-b border-slate-200">
                                <span class="text-slate-600">Total Supply:</span>
                                <span class="font-bold">1,000,000,000 YLX</span>
                            </div>
                            <div class="flex justify-between items-center py-3 border-b border-slate-200">
                                <span class="text-slate-600">Blockchain:</span>
                                <span class="font-bold">Stellar Network</span>
                            </div>
                            <div class="flex justify-between items-center py-3">
                                <span class="text-slate-600">Token Type:</span>
                                <span class="font-bold">Utility Token</span>
                            </div>
                        </div>
                    </div>

                    <!-- Chart Placeholder -->
                    <div class="text-center">
                        <h4 class="text-xl font-bold mb-6 text-slate-800">Token Distribution</h4>
                        <div class="tokenomics-chart mx-auto mb-6"></div>
                        <div class="text-center">
                            <div class="text-2xl font-bold gradient-text mb-2">1B YLX</div>
                            <div class="text-sm text-slate-600">Total Supply</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Allocation Breakdown -->
            <div class="section-card">
                <h3 class="text-2xl font-bold mb-6 text-slate-800">Allocation Breakdown</h3>
                <div class="space-y-4">
                    <div class="flex items-center justify-between p-4 bg-blue-50 rounded-lg border border-blue-200">
                        <div class="flex items-center">
                            <div class="w-4 h-4 bg-yolixa-blue rounded-full mr-3"></div>
                            <div>
                                <span class="font-bold">Rewards Pool (40%)</span>
                                <p class="text-sm text-slate-600">Incentives for creators, supporters, referrals, and ecosystem activity.</p>
                            </div>
                        </div>
                        <span class="font-bold text-yolixa-blue">400M YLX</span>
                    </div>

                    <div class="flex items-center justify-between p-4 bg-green-50 rounded-lg border border-green-200">
                        <div class="flex items-center">
                            <div class="w-4 h-4 bg-green-500 rounded-full mr-3"></div>
                            <div>
                                <span class="font-bold">Team & Development (10%)</span>
                                <p class="text-sm text-slate-600">Core team allocation with a long-term vesting schedule.</p>
                            </div>
                        </div>
                        <span class="font-bold text-green-600">100M YLX</span>
                    </div>

                    <div class="flex items-center justify-between p-4 bg-yellow-50 rounded-lg border border-yellow-200">
                        <div class="flex items-center">
                            <div class="w-4 h-4 bg-yellow-500 rounded-full mr-3"></div>
                            <div>
                                <span class="font-bold">Marketing, Partnerships & Ecosystem (45%)</span>
                                <p class="text-sm text-slate-600">
                                    Growth, collaborations, creator onboarding campaigns, exchange listings, and ecosystem grants.
                                </p>
                            </div>
                        </div>
                        <span class="font-bold text-yellow-600">450M YLX</span>
                    </div>

                    <div class="flex items-center justify-between p-4 bg-red-50 rounded-lg border border-red-200">
                        <div class="flex items-center">
                            <div class="w-4 h-4 bg-red-500 rounded-full mr-3"></div>
                            <div>
                                <span class="font-bold">Reserve Fund (5%)</span>
                                <p class="text-sm text-slate-600">Emergency reserves and future strategic initiatives.</p>
                            </div>
                        </div>
                        <span class="font-bold text-red-600">50M YLX</span>
                    </div>
                </div>
            </div>

            <!-- Vesting Schedule -->
            <div class="section-card bg-gradient-to-r from-blue-50 to-purple-50 border-blue-200">
                <h3 class="text-2xl font-bold mb-6 gradient-text">Vesting & Emission Schedule</h3>
                <div class="grid md:grid-cols-2 gap-8">
                    <div>
                        <h4 class="font-bold text-slate-800 mb-4">Team Tokens</h4>
                        <ul class="space-y-2 text-slate-700">
                            <li>• 12-month cliff.</li>
                            <li>• 36-month linear vesting after the cliff.</li>
                            <li>• No immediate liquidity for core team allocation.</li>
                            <li>• Linked to product and ecosystem milestones.</li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="font-bold text-slate-800 mb-4">Rewards Distribution</h4>
                        <ul class="space-y-2 text-slate-700">
                            <li>• Daily reward emissions governed by activity.</li>
                            <li>• Gradually decreasing over time to avoid inflation.</li>
                            <li>• Anti-abuse mechanisms and caps per wallet.</li>
                            <li>• Short-term vesting/linear unlock for selected rewards to reduce immediate sell pressure.</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Supply & Burn Mechanics -->
            <div class="section-card bg-purple-50 border-purple-200">
                <h3 class="text-2xl font-bold mb-4 text-purple-700">Supply & Burn Mechanics</h3>
                <p class="text-slate-700 mb-4">
                    YLX follows a transparent and predictable supply model. While the total supply is fixed at 1B YLX, the
                    effective circulating supply can decrease over time through planned burns.
                </p>
                <ul class="space-y-2 text-slate-700">
                    <li>• A small portion of platform fees may be allocated to periodic buyback-and-burn events.</li>
                    <li>• Burn events are announced publicly, with on-chain proof for full transparency.</li>
                    <li>• The burn policy focuses on long-term sustainability, not short-term pump-and-dump behavior.</li>
                </ul>
            </div>

            <!-- Security & Audits -->
            <div class="section-card bg-slate-50 border-slate-200">
                <h3 class="text-2xl font-bold mb-4 text-slate-800">Security & Audits</h3>
                <p class="text-slate-700 mb-4">
                    Security is a core pillar of Yolixa’s design. The platform operates on a non-custodial model where users
                    maintain control over their wallets and assets.
                </p>
                <ul class="space-y-2 text-slate-700">
                    <li>• Smart contract and integration logic are built with minimal attack surface and clear separation of concerns.</li>
                    <li>• Internal reviews and code audits happen before any major feature or tokenomics update.</li>
                    <li>• The long-term roadmap includes partnering with reputable third-party Web3 security firms for external audits.</li>
                    <li>• Transparent communication with the community around any security updates or changes.</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Revenue Model Page -->
    <div class="page">
        <div class="max-w-4xl mx-auto">
            <!-- Heading -->
            <div class="flex items-center mb-8">
                <div class="w-12 h-12 gradient-bg rounded-xl flex items-center justify-center mr-4">
                    <!-- Revenue / Earnings Icon -->
                    <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M3 3h2v18H3V3zm16 0h2v18h-2V3zM7 13h2v8H7v-8zm4-6h2v14h-2V7zm4 4h2v10h-2V11z" />
                        <path d="M12 1a3 3 0 100 6h1v2h-1a5 5 0 110-10h1v2h-1z" />
                    </svg>
                </div>
                <h2 class="text-5xl font-black gradient-text">Revenue Model</h2>
            </div>

            <!-- Revenue Streams -->
            <div class="section-card">
                <h3 class="text-2xl font-bold mb-6 text-slate-800">Sustainable Revenue Streams</h3>
                <p class="text-lg text-slate-700 mb-8">
                    Yolixa ensures sustainability through a diversified revenue model with minimal fees, premium services,
                    and long-term expansion opportunities—all while prioritizing creators and supporters.
                </p>

                <div class="grid md:grid-cols-2 gap-6">
                    <!-- Small Tipping Fees -->
                    <div class="bg-blue-50 border border-blue-200 rounded-xl p-6">
                        <div class="icon-circle gradient-bg mb-4" style="width:65px">
                            <!-- Percent Icon -->
                            <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" fill="none" />
                                <path d="M8 16l8-8" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                                <circle cx="9" cy="9" r="1.5" fill="currentColor" />
                                <circle cx="15" cy="15" r="1.5" fill="currentColor" />
                            </svg>
                        </div>
                        <h4 class="text-xl font-bold mb-3 text-yolixa-blue">Small Tipping Fees</h4>
                        <p class="text-slate-700 mb-4">
                            The platform sustains itself by charging a minimal <strong>1–2% fee</strong> on every tip — keeping creators first
                            while funding operations, development, and ecosystem expansion.
                        </p>
                        <div class="text-sm text-slate-600">
                            <strong>Revenue Split:</strong> ~98–99% to creators, ~1–2% to the platform.
                        </div>
                    </div>

                    <!-- Premium Tools & Staking -->
                    <div class="bg-purple-50 border border-purple-200 rounded-xl p-6">
                        <div class="icon-circle gradient-bg mb-4" style="width:65px">
                            <!-- Star / tools icon -->
                            <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2l2.09 6.26L20 9.27l-5 3.64L16.18 20 12 16.9 7.82 20 9 12.91 4 9.27l5.91-.99L12 2z" />
                            </svg>
                        </div>
                        <h4 class="text-xl font-bold mb-3 text-yolixa-purple">Premium Tools & Staking</h4>
                        <p class="text-slate-700 mb-4">
                            Additional revenue comes from optional premium features for creators (advanced analytics, branding tools,
                            automation) and staking-related services for YLX holders.
                        </p>
                        <div class="text-sm text-slate-600">
                            <strong>Goal:</strong> Align platform revenue with real usage and long-term value creation.
                        </div>
                    </div>

                    <!-- Future Expansion -->
                    <div class="bg-green-50 border border-green-200 rounded-xl p-6 md:col-span-2">
                        <div class="icon-circle gradient-bg mb-4" style="width:65px">
                            <!-- Rocket Icon -->
                            <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M4 13a8 8 0 0116 0c0 3.866-2.239 7-5 8l-.5 2-2-.5c-3.5-.875-6.5-4.134-6.5-8.5z" />
                            </svg>
                        </div>
                        <h4 class="text-xl font-bold mb-3 text-green-600">Future Expansion</h4>
                        <p class="text-slate-700 mb-4">
                            The expansion roadmap includes integrations with merchandise and creator collaboration tools, sponsorship marketplaces, and more advanced revenue-sharing models
                            between creators, brands, and fans.
                        </p>
                        <div class="text-sm text-slate-600">
                            <strong>Timeline:</strong> Phase 3 (2025–2026) and beyond, aligned with adoption milestones.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sustainability Factors -->
            <div class="section-card bg-gradient-to-r from-green-50 to-blue-50 border-green-200">
                <h3 class="text-2xl font-bold mb-6 gradient-text">Sustainability Factors</h3>
                <div class="grid md:grid-cols-2 gap-8">
                    <div>
                        <h4 class="font-bold text-slate-800 mb-4">Growth Drivers</h4>
                        <ul class="space-y-2 text-slate-700">
                            <li>• Network effects via creator adoption.</li>
                            <li>• Viral sharing of tipping links and content.</li>
                            <li>• Cross-platform and cross-chain integrations.</li>
                            <li>• Creator and community-driven expansion loops.</li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="font-bold text-slate-800 mb-4">Cost Optimization</h4>
                        <ul class="space-y-2 text-slate-700">
                            <li>• Stellar’s low transaction costs and high efficiency.</li>
                            <li>• Automated flows minimizing operational overhead.</li>
                            <li>• Efficient tokenomics and transparent emissions.</li>
                            <li>• Community-driven model, reducing centralized marketing costs.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Roadmap Page -->
    <div class="page">
        <div class="max-w-4xl mx-auto">
            <div class="flex items-center mb-8">
                <div class="w-12 h-12 gradient-bg rounded-xl flex items-center justify-center mr-4">
                    <!-- Roadmap Icon -->
                    <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M4 17l6-6 4 4 6-6" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </div>
                <h2 class="text-5xl font-black gradient-text">Roadmap</h2>
            </div>

            <div class="section-card">
                <h3 class="text-2xl font-bold mb-8 text-center text-slate-800">Development Timeline</h3>

                <div class="space-y-8">
                    <!-- Phase 1 -->
                    <div class="timeline-item">
                        <div class="bg-blue-50 border border-blue-200 rounded-xl p-6">
                            <div class="flex items-center mb-4">
                                <div class="w-12 h-12 gradient-bg rounded-full flex items-center justify-center text-white font-bold mr-4">1</div>
                                <div>
                                    <h4 class="text-2xl font-bold text-slate-800">Phase 1: MVP Launch</h4>
                                    <span class="text-yolixa-blue font-semibold">Q1–Q2 2025</span>
                                </div>
                            </div>
                            <div class="ml-16">
                                <p class="text-slate-700 mb-4">
                                    Launch of the Minimum Viable Product with essential tipping features and token deployment.
                                </p>
                                <div class="grid md:grid-cols-2 gap-4">
                                    <ul class="space-y-2 text-slate-700">
                                        <li>✓ Stellar blockchain integration.</li>
                                        <li>✓ Basic tipping functionality.</li>
                                        <li>✓ YLX token deployment.</li>
                                        <li>✓ Wallet connection for creators.</li>
                                    </ul>
                                    <ul class="space-y-2 text-slate-700">
                                        <li>✓ Referral link system.</li>
                                        <li>✓ Initial analytics dashboard.</li>
                                        <li>✓ Community building.</li>
                                        <li>✓ Web application launch.</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Phase 2 -->
                    <div class="timeline-item">
                        <div class="bg-purple-50 border border-purple-200 rounded-xl p-6">
                            <div class="flex items-center mb-4">
                                <div class="w-12 h-12 gradient-bg rounded-full flex items-center justify-center text-white font-bold mr-4">2</div>
                                <div>
                                    <h4 class="text-2xl font-bold text-slate-800">Phase 2: Growth & Features</h4>
                                    <span class="text-yolixa-purple font-semibold">Q3–Q4 2025</span>
                                </div>
                            </div>
                            <div class="ml-16">
                                <p class="text-slate-700 mb-4">
                                    Expansion of platform features and growth of creator and fan ecosystem.
                                </p>
                                <div class="grid md:grid-cols-2 gap-4">
                                    <ul class="space-y-2 text-slate-700">
                                        <li>• Premium subscription tiers.</li>
                                        <li>• Advanced creator tools.</li>
                                        <li>• Social media integrations.</li>
                                        <li>• YLX staking & rewards.</li>
                                    </ul>
                                    <ul class="space-y-2 text-slate-700">
                                        <li>• Referral & loyalty program.</li>
                                        <li>• Third-party API integrations.</li>
                                        <li>• Enhanced security protocols & monitoring.</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Phase 3 -->
                    <div class="timeline-item">
                        <div class="bg-green-50 border border-green-200 rounded-xl p-6">
                            <div class="flex items-center mb-4">
                                <div class="w-12 h-12 gradient-bg rounded-full flex items-center justify-center text-white font-bold mr-4">3</div>
                                <div>
                                    <h4 class="text-2xl font-bold text-slate-800">Phase 3: Global Expansion</h4>
                                    <span class="text-green-600 font-semibold">Q1–Q4 2026</span>
                                </div>
                            </div>
                            <div class="ml-16">
                                <p class="text-slate-700 mb-4">
                                    Scaling the ecosystem globally with advanced Web3 features and cross-chain compatibility.
                                </p>
                                <div class="grid md:grid-cols-2 gap-4">
                                    <ul class="space-y-2 text-slate-700">
                                        <li>• Merchandise & e-commerce features.</li>
                                        <li>• Cross-chain support.</li>
                                        <li>• Enterprise partnerships.</li>
                                    </ul>
                                    <ul class="space-y-2 text-slate-700">
                                        <li>• AI-driven personalization.</li>
                                        <li>• Global strategic alliances.</li>
                                        <li>• DeFi-based features & liquidity programs.</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Vision Page -->
    <div class="page">
        <div class="max-w-4xl mx-auto">
            <!-- Vision Heading -->
            <div class="flex items-center mb-8">
                <div class="w-12 h-12 gradient-bg rounded-xl flex items-center justify-center mr-4">
                    <!-- Eye / Vision Icon -->
                    <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                    </svg>
                </div>
                <h2 class="text-5xl font-black gradient-text">Vision</h2>
            </div>

            <!-- Core Vision Statement -->
            <div class="section-card">
                <h3 class="text-3xl font-bold mb-8 text-center gradient-text">
                    Redefining the Global Standard for Decentralized Tipping
                </h3>
                <p class="text-xl text-slate-700 leading-relaxed text-center mb-12">
                    Yolixa envisions a world where creators earn fairly, supporters can tip instantly,
                    and blockchain removes barriers between talent and financial reward.
                    Our mission is to empower the creator economy with transparency, inclusivity, and trust.
                </p>

                <!-- Pillars -->
                <div class="grid md:grid-cols-3 gap-8 mb-12">
                    <div class="text-center">
                        <div class="w-20 h-20 gradient-bg rounded-full flex items-center justify-center mx-auto mb-6">
                            <!-- Globe Icon -->
                            <svg class="w-10 h-10 text-white" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2a10 10 0 100 20 10 10 0 000-20zm0 0c-2.21 0-4 4.48-4 10s1.79 10 4 10 4-4.48 4-10-1.79-10-4-10z" />
                            </svg>
                        </div>
                        <h4 class="text-2xl font-bold mb-4 text-yolixa-blue">Global Accessibility</h4>
                        <p class="text-slate-700">
                            Breaking down financial and geographic barriers so anyone with internet
                            can participate in the creator economy.
                        </p>
                    </div>

                    <div class="text-center">
                        <div class="w-20 h-20 gradient-bg rounded-full flex items-center justify-center mx-auto mb-6">
                            <!-- Lightning Icon -->
                            <svg class="w-10 h-10 text-white" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        <h4 class="text-2xl font-bold mb-4 text-yolixa-purple">Instant Value Transfer</h4>
                        <p class="text-slate-700">
                            Real-time, low-cost blockchain transactions that maximize the value creators receive.
                        </p>
                    </div>

                    <div class="text-center">
                        <div class="w-20 h-20 gradient-bg rounded-full flex items-center justify-center mx-auto mb-6">
                            <!-- Users / Community Icon -->
                            <svg class="w-10 h-10 text-white" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.5 20a7.5 7.5 0 0115 0c-2.5 1-5 1.75-7.5 1.75S7 21 4.5 20z" />
                            </svg>
                        </div>
                        <h4 class="text-2xl font-bold mb-4 text-green-600">Community Empowerment</h4>
                        <p class="text-slate-700">
                            Rewarding both creators and supporters to build a thriving, self-sustaining ecosystem.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Long-Term Impact -->
            <div class="section-card bg-gradient-to-r from-blue-50 to-purple-50 border-blue-200">
                <h3 class="text-2xl font-bold mb-8 text-center gradient-text">Our Long-Term Impact</h3>

                <div class="space-y-8">
                    <div class="flex items-start space-x-6">
                        <div class="w-16 h-16 gradient-bg rounded-full flex items-center justify-center flex-shrink-0" style="width: 120px;">
                            <span class="text-2xl font-bold text-white">2025</span>
                        </div>
                        <div>
                            <h4 class="text-xl font-bold mb-3 text-slate-800">Market Leadership</h4>
                            <p class="text-slate-700">
                                Establish Yolixa as a leading decentralized tipping platform with tens of thousands of active creators
                                and strong monthly tipping volume.
                            </p>
                        </div>
                    </div>

                    <div class="flex items-start space-x-6">
                        <div class="w-16 h-16 gradient-bg rounded-full flex items-center justify-center flex-shrink-0" style="width: 120px;">
                            <span class="text-2xl font-bold text-white">2026</span>
                        </div>
                        <div>
                            <h4 class="text-xl font-bold mb-3 text-slate-800">Ecosystem Expansion</h4>
                            <p class="text-slate-700">
                                Launch advanced creator tools including merchandise integration, cross-chain support,
                                and enterprise partnerships.
                            </p>
                        </div>
                    </div>

                    <div class="flex items-start space-x-6">
                        <div class="w-16 h-16 gradient-bg rounded-full flex items-center justify-center flex-shrink-0" style="width: 120px;">
                            <span class="text-2xl font-bold text-white">2027+</span>
                        </div>
                        <div>
                            <h4 class="text-xl font-bold mb-3 text-slate-800">Global Standard</h4>
                            <p class="text-slate-700">
                                Become a universal standard for creator monetization, integrated with major social platforms
                                and used by creators worldwide.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Closing Call-to-Action -->
            <div class="section-card text-center">
                <h3 class="text-2xl font-bold mb-6 gradient-text">Join the Revolution</h3>
                <p class="text-lg text-slate-700 mb-8">
                    The future of the creator economy is decentralized, transparent, and fair.
                    Yolixa is building the infrastructure to make this vision a reality,
                    empowering millions of creators worldwide to thrive.
                </p>

                <div class="grid md:grid-cols-2 gap-8">
                    <div class="bg-blue-50 border border-blue-200 rounded-xl p-6">
                        <h4 class="text-xl font-bold mb-4 text-yolixa-blue">For Creators</h4>
                        <p class="text-slate-700">
                            Take control of your income with minimal fees, instant payouts, and borderless reach.
                        </p>
                    </div>

                    <div class="bg-purple-50 border border-purple-200 rounded-xl p-6">
                        <h4 class="text-xl font-bold mb-4 text-yolixa-purple">For Supporters</h4>
                        <p class="text-slate-700">
                            Support creators you love directly and earn rewards for your engagement.
                            Every contribution makes a lasting impact.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Contact Page -->
    <div class="page">
        <div class="max-w-4xl mx-auto">
            <!-- Contact Heading -->
            <div class="flex items-center mb-8">
                <div class="w-12 h-12 gradient-bg rounded-xl flex items-center justify-center mr-4">
                    <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                </div>
                <h2 class="text-5xl font-black gradient-text">Contact Us</h2>
            </div>

            <!-- Intro -->
            <div class="section-card text-center mb-8">
                <h3 class="text-3xl font-bold mb-6 gradient-text">Let’s Connect</h3>
                <p class="text-lg text-slate-700 mb-8">
                    Whether you’re a creator, supporter, investor, or partner — we’d love to hear from you.
                    Reach out through our official channels or join our growing community.
                </p>
            </div>

            <!-- Official Channels + Community -->
            <div class="grid md:grid-cols-1 gap-8 mb-8">
                <!-- Official -->
                <div class="bg-white rounded-2xl shadow-lg p-8 border border-slate-100">
                    <h4 class="text-3xl font-extrabold mb-8 text-center text-yolixa-blue">Official Channels</h4>

                    <div class="space-y-8">
                        <!-- Website -->
                        <div class="flex items-center space-x-5 hover:bg-slate-50 transition rounded-xl p-4">
                            <div class="w-12 h-12 bg-gradient-to-r from-yolixa-blue to-blue-600 rounded-xl flex items-center justify-center shadow-md">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="2">
                                    <path
                                        d="M12 3C7.03 3 3 7.03 3 12c0 4.97 4.03 9 9 9 4.97 0 9-4.03 9-9 0-4.97-4.03-9-9-9zm0 16c-3.86 0-7-3.14-7-7 0-3.86 3.14-7 7-7 3.86 0 7 3.14 7 7 0 3.86-3.14 7-7 7z" />
                                    <path d="M12 3v18M3 12h18" />
                                </svg>
                            </div>
                            <div>
                                <p class="font-bold text-slate-800">Website</p>
                                <a href="https://www.yolixa.com" target="_blank" class="text-slate-600 hover:text-yolixa-blue transition">www.yolixa.com</a>
                            </div>
                        </div>

                        <!-- Email -->
                        <div class="flex items-center space-x-5 hover:bg-slate-50 transition rounded-xl p-4">
                            <div class="w-12 h-12 bg-gradient-to-r from-yolixa-blue to-blue-600 rounded-xl flex items-center justify-center shadow-md">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="2">
                                    <path
                                        d="M3 8l9 6 9-6M4 6h16a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2z" />
                                </svg>
                            </div>
                            <div>
                                <p class="font-bold text-slate-800">Email</p>
                                <a href="mailto:info.yolixa@gmail.com" class="text-slate-600 hover:text-yolixa-blue transition">yolixaofficial@gmail.com</a>
                            </div>
                        </div>

                        <!-- Twitter -->
                        <div class="flex items-center space-x-5 hover:bg-slate-50 transition rounded-xl p-4">
                            <div class="w-12 h-12 bg-gradient-to-r from-yolixa-blue to-blue-600 rounded-xl flex items-center justify-center shadow-md">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-white" viewBox="0 0 24 24" fill="currentColor">
                                    <path
                                        d="M22.46 6c-.77.35-1.6.58-2.46.69a4.26 4.26 0 001.88-2.35 8.35 8.35 0 01-2.68 1.03A4.22 4.22 0 0015.5 4c-2.33 0-4.22 1.89-4.22 4.22 0 .33.04.65.1.96A11.96 11.96 0 013 5.16a4.22 4.22 0 001.31 5.64 4.21 4.21 0 01-1.91-.53v.05a4.23 4.23 0 003.38 4.14 4.24 4.24 0 01-1.9.07 4.23 4.23 0 003.95 2.94A8.48 8.48 0 012 19.54a11.94 11.94 0 006.48 1.9c7.78 0 12.04-6.45 12.04-12.04 0-.18-.01-.35-.02-.52A8.6 8.6 0 0022.46 6z" />
                                </svg>
                            </div>
                            <div>
                                <p class="font-bold text-slate-800">Twitter</p>
                                <a href="https://twitter.com/YolixaOfficial" target="_blank" class="text-slate-600 hover:text-yolixa-blue transition">@YolixaOfficial</a>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <!-- Call to Action -->
            <div class="section-card bg-gradient-to-r from-blue-50 to-purple-50 border-blue-200 text-center">
                <h4 class="text-2xl font-bold mb-6 gradient-text">Ready to Get Started?</h4>
                <p class="text-lg text-slate-700 mb-8">
                    Join thousands of creators and supporters shaping the future of decentralized tipping.
                    Your journey starts here.
                </p>

                <div class="grid md:grid-cols-3 gap-6">
                    <div class="bg-white rounded-xl p-6 border border-slate-200">
                        <h5 class="font-bold text-yolixa-blue mb-2">For Creators</h5>
                        <p class="text-sm text-slate-600">Sign up and start receiving instant tips worldwide.</p>
                    </div>

                    <div class="bg-white rounded-xl p-6 border border-slate-200">
                        <h5 class="font-bold text-yolixa-purple mb-2">For Investors</h5>
                        <p class="text-sm text-slate-600">Discover YLX token utility and growth opportunities.</p>
                    </div>

                    <div class="bg-white rounded-xl p-6 border border-slate-200">
                        <h5 class="font-bold text-green-600 mb-2">For Partners</h5>
                        <p class="text-sm text-slate-600">Collaborate with Yolixa and explore integrations.</p>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="text-center mt-8 pt-8 border-t border-slate-200">
                <div class="flex items-center justify-center mb-4">
                    <div class="w-8 h-8 gradient-bg rounded-lg flex items-center justify-center mr-3">
                        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5" />
                        </svg>
                    </div>
                    <span class="text-2xl font-bold gradient-text">Yolixa</span>
                </div>
                <p class="text-slate-600 mb-2">Decentralized Tipping. Empowering Creators Everywhere.</p>
                <p class="text-sm text-slate-500">© 2025 Yolixa. All rights reserved.</p>
            </div>
        </div>
    </div>


    <script>
        // Print functionality
        function printWhitepaper() {
            window.print();
        }

        // Add any interactive elements here
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Yolixa Whitepaper loaded successfully');
        });
    </script>
    <script>
        (function() {
            function c() {
                var b = a.contentDocument || a.contentWindow.document;
                if (b) {
                    var d = b.createElement('script');
                    d.innerHTML = "window.__CF$cv$params={r:'97df994632d19098',t:'MTc1NzY4MjE0Mi4wMDAwMDA='};var a=document.createElement('script');a.nonce='';a.src='/cdn-cgi/challenge-platform/scripts/jsd/main.js';document.getElementsByTagName('head')[0].appendChild(a);";
                    b.getElementsByTagName('head')[0].appendChild(d)
                }
            }
            if (document.body) {
                var a = document.createElement('iframe');
                a.height = 1;
                a.width = 1;
                a.style.position = 'absolute';
                a.style.top = 0;
                a.style.left = 0;
                a.style.border = 'none';
                a.style.visibility = 'hidden';
                document.body.appendChild(a);
                if ('loading' !== document.readyState) c();
                else if (window.addEventListener) document.addEventListener('DOMContentLoaded', c);
                else {
                    var e = document.onreadystatechange || function() {};
                    document.onreadystatechange = function(b) {
                        e(b);
                        'loading' !== document.readyState && (document.onreadystatechange = e, c())
                    }
                }
            }
        })();
    </script>
</body>

</html>
