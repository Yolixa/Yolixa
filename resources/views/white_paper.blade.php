<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="{{ asset('assets/images/favicon-32x32.png') }}">
    <title>Yolixa Whitepaper - Decentralized Tipping Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        outfit: ['Outfit', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            blue: '#3b82f6',
                            purple: '#8b5cf6',
                            dark: '#0B0F19',
                            darker: '#06090F',
                            card: '#131A2A',
                        }
                    },
                    animation: {
                        'pulse-slow': 'pulse 4s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                    }
                }
            }
        }
    </script>
    <style>
        body {
            background-color: #0B0F19;
            color: #E2E8F0;
            overflow-x: hidden;
            font-family: 'Inter', sans-serif;
        }

        h1, h2, h3, h4, h5, h6 {
            font-family: 'Outfit', sans-serif;
        }

        .glass-panel {
            background: rgba(19, 26, 42, 0.3);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.04);
            border-radius: 1.5rem;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .glass-panel:hover {
            border-color: rgba(139, 92, 246, 0.2);
            background: rgba(19, 26, 42, 0.5);
            transform: translateY(-2px);
        }

        .text-gradient {
            background: linear-gradient(135deg, #60A5FA 0%, #A78BFA 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .bg-gradient-brand {
            background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
        }

        /* Status Badges */
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.2rem 0.6rem;
            border-radius: 9999px;
            font-size: 0.65rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .status-live {
            background: rgba(16, 185, 129, 0.1);
            color: #34D399;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        .status-progress {
            background: rgba(245, 158, 11, 0.1);
            color: #FBBF24;
            border: 1px solid rgba(245, 158, 11, 0.3);
        }

        .status-planned {
            background: rgba(59, 130, 246, 0.1);
            color: #60A5FA;
            border: 1px solid rgba(59, 130, 246, 0.3);
        }

        /* Ambient Glows */
        .glow-circle-1 {
            position: absolute;
            top: -10%;
            left: -10%;
            width: 50vw;
            height: 50vw;
            background: radial-gradient(circle, rgba(139,92,246,0.12) 0%, rgba(0,0,0,0) 65%);
            z-index: -1;
            border-radius: 50%;
        }

        .glow-circle-2 {
            position: absolute;
            bottom: -20%;
            right: -10%;
            width: 60vw;
            height: 60vw;
            background: radial-gradient(circle, rgba(59,130,246,0.08) 0%, rgba(0,0,0,0) 65%);
            z-index: -1;
            border-radius: 50%;
        }

        /* Content Nav */
        .toc-link {
            position: relative;
            color: #64748B;
            transition: all 0.3s ease;
            display: block;
            padding: 0.5rem 0;
            padding-left: 1rem;
            border-left: 2px solid rgba(255,255,255,0.05);
            font-size: 0.875rem;
        }
        .toc-link:hover {
            color: #E2E8F0;
            border-left-color: rgba(255,255,255,0.2);
        }
        .toc-link.active {
            color: #A78BFA;
            font-weight: 600;
            border-left-color: #8B5CF6;
            background: linear-gradient(90deg, rgba(139, 92, 246, 0.05) 0%, transparent 100%);
        }

        /* Top Nav active state */
        .top-nav-link {
            position: relative;
            color: #94A3B8;
            transition: color 0.3s ease;
            padding-bottom: 0.5rem;
        }
        .top-nav-link:hover {
            color: #FFFFFF;
        }
        .top-nav-link.active {
            color: #A78BFA;
            font-weight: 600;
        }
        .top-nav-link::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 0;
            height: 2px;
            background: #8B5CF6;
            transition: width 0.3s ease;
        }
        .top-nav-link.active::after {
            width: 20px;
        }

        /* Grid Background pattern */
        .grid-pattern {
            background-image: linear-gradient(rgba(255, 255, 255, 0.02) 1px, transparent 1px),
            linear-gradient(90deg, rgba(255, 255, 255, 0.02) 1px, transparent 1px);
            background-size: 40px 40px;
            background-position: center center;
        }

        /* Nav Scroll State */
        .nav-scrolled {
            background: rgba(6, 9, 15, 0.9);
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.2);
        }

        /* Mobile Overlay */
        #mobile-menu {
            transform: translateX(100%);
            transition: transform 0.3s ease-in-out;
        }
        #mobile-menu.open {
            transform: translateX(0);
        }
        .overlay {
            display: none;
            opacity: 0;
            transition: opacity 0.3s ease;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.6);
            backdrop-filter: blur(4px);
            z-index: 55;
        }
        .overlay.show {
            display: block;
            opacity: 1;
        }
    </style>
</head>
<body class="relative min-h-screen selection:bg-purple-500/30">

    <!-- Ambient Background -->
    <div class="fixed inset-0 grid-pattern z-[-2]"></div>
    <div class="fixed top-0 left-0 w-full h-full overflow-hidden z-[-1] pointer-events-none">
        <div class="glow-circle-1 animate-pulse-slow"></div>
        <div class="glow-circle-2" style="animation: pulse 5s cubic-bezier(0.4, 0, 0.6, 1) infinite reverse;"></div>
    </div>

    <!-- Navigation -->
    <nav id="mainNav" class="fixed top-0 w-full z-50 transition-all duration-300 px-6 py-4 border-b border-transparent">
        <div class="max-w-7xl mx-auto flex justify-between items-center">

            <!-- Logo -->
            <a href="#" class="flex items-center gap-3 group">
                <div class="relative">
                    <div class="w-10 h-10 rounded-xl bg-gradient-brand flex items-center justify-center shadow-lg shadow-purple-500/20 transition-transform duration-300 group-hover:scale-105 relative z-10">
                        <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
                        </svg>
                    </div>
                </div>
                <span class="text-2xl font-outfit font-bold text-white tracking-wide">Yolixa</span>
            </a>

            <!-- Desktop Links -->
            <div class="hidden md:flex items-center gap-8 text-sm font-medium">
                <a href="#overview" class="top-nav-link">Overview</a>
                <a href="#how-it-works" class="top-nav-link">How it Works</a>
                <a href="#stellar-integration" class="top-nav-link">Architecture</a>
                <a href="#fees-rewards" class="top-nav-link">Tokenomics</a>
                <a href="#roadmap" class="top-nav-link">Roadmap</a>
            </div>

            <!-- CTA / Mobile Toggle -->
            <div class="flex items-center gap-4">
                <a href="{{ url('/') }}" class="hidden sm:inline-flex px-5 py-2 rounded-lg bg-white/5 hover:bg-white/10 border border-white/10 text-white font-semibold text-sm transition-all duration-300 shadow-sm hover:shadow-purple-500/10">
                    Back to App
                </a>

                <!-- Hamburger -->
                <button id="mobileMenuBtn" class="md:hidden p-2 text-slate-300 hover:text-white focus:outline-none">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                       <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            </div>
        </div>
    </nav>

    <!-- Mobile Drawer Overlay -->
    <div id="mobileOverlay" class="overlay md:hidden"></div>

    <!-- Mobile Drawer -->
    <div id="mobile-menu" class="fixed inset-y-0 right-0 w-64 bg-[#06090F] border-l border-white/10 z-[60] p-6 shadow-2xl flex flex-col md:hidden">
        <div class="flex justify-between items-center mb-10">
            <span class="text-xl font-outfit font-bold text-white">Menu</span>
            <button id="closeMobileBtn" class="text-slate-400 hover:text-white p-2">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                   <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <div class="flex flex-col gap-2 text-sm">
            <a href="#overview" class="mobile-nav-link p-3 rounded-lg text-slate-300 hover:bg-white/5 hover:text-purple-400 font-medium transition-colors">1. Project Overview</a>
            <a href="#how-it-works" class="mobile-nav-link p-3 rounded-lg text-slate-300 hover:bg-white/5 hover:text-purple-400 font-medium transition-colors">2. How it Works</a>
            <a href="#stellar-integration" class="mobile-nav-link p-3 rounded-lg text-slate-300 hover:bg-white/5 hover:text-purple-400 font-medium transition-colors">3. Architecture</a>
            <a href="#fees-rewards" class="mobile-nav-link p-3 rounded-lg text-slate-300 hover:bg-white/5 hover:text-purple-400 font-medium transition-colors">4. Fees & Rewards</a>
            <a href="#roadmap" class="mobile-nav-link p-3 rounded-lg text-slate-300 hover:bg-white/5 hover:text-purple-400 font-medium transition-colors">5. Roadmap & Vision</a>
        </div>
        <div class="mt-auto pt-6 border-t border-white/10">
            <a href="{{ url('/') }}" class="flex justify-center w-full px-5 py-3 rounded-lg bg-purple-600/20 border border-purple-500/30 text-white font-medium transition-colors hover:bg-purple-600/40">
                Back to App
            </a>
        </div>
    </div>

    <!-- Main Content Layout -->
    <div class="max-w-7xl mx-auto px-6 pt-36 pb-24 flex flex-col lg:flex-row gap-12 relative">

        <!-- Table of Contents Sidebar -->
        <aside class="hidden lg:block w-64 shrink-0">
            <div class="sticky top-32 glass-panel p-6">
                <h3 class="text-xs uppercase tracking-wider text-slate-400 font-bold mb-4">Contents</h3>
                <nav class="flex flex-col text-sm font-medium">
                    <a href="#overview" class="toc-link">1. Project Overview</a>
                    <a href="#problem-solution" class="toc-link">2. Problem & Solution</a>
                    <a href="#how-it-works" class="toc-link">3. How Yolixa Works</a>
                    <a href="#stellar-integration" class="toc-link">4. Stellar Integration</a>
                    <a href="#fees-rewards" class="toc-link">5. Fees & Token Rewards</a>
                    <a href="#security" class="toc-link">6. Security & Operations</a>
                    <a href="#roadmap" class="toc-link">7. Future Roadmap</a>
                </nav>

                <div class="mt-8 pt-6 border-t border-white/10">
                    <h4 class="text-xs uppercase tracking-wider text-slate-400 font-bold mb-4">Implementation Status</h4>
                    <div class="flex flex-col gap-3">
                        <div class="flex items-center gap-3"><span class="w-2.5 h-2.5 rounded-full bg-emerald-400 shadow-[0_0_8px_rgba(52,211,153,0.5)]"></span> <span class="text-xs text-slate-300 font-medium">Fully Implemented</span></div>
                        <div class="flex items-center gap-3"><span class="w-2.5 h-2.5 rounded-full bg-amber-400 shadow-[0_0_8px_rgba(251,191,36,0.5)]"></span> <span class="text-xs text-slate-300 font-medium">In Progress</span></div>
                        <div class="flex items-center gap-3"><span class="w-2.5 h-2.5 rounded-full bg-blue-400 shadow-[0_0_8px_rgba(96,165,250,0.5)]"></span> <span class="text-xs text-slate-300 font-medium">Planned Scope</span></div>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Whitepaper Document -->
        <main class="flex-1 space-y-24 min-w-0">

            <!-- Cover/Hero -->
            <section class="text-center md:text-left pt-6 pb-12 border-b border-white/10 relative">
                <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-purple-500/10 border border-purple-500/20 text-purple-300 font-semibold text-xs mb-6 animate__animated animate__fadeInDown">
                    <span class="w-2 h-2 rounded-full bg-purple-400 animate-pulse"></span> Official Documentation v2.0
                </div>
                <h1 class="text-5xl md:text-7xl font-black text-white leading-[1.1] mb-6 tracking-tight animate__animated animate__fadeInUp">
                    The Decentralized <br>
                    <span class="text-gradient">Tipping Economy.</span>
                </h1>
                <p class="text-lg md:text-xl text-slate-400 max-w-2xl leading-relaxed mb-10 animate__animated animate__fadeInUp" style="animation-delay: 0.1s;">
                    A comprehensive technical overview detailing Yolixa's current codebase implementation, blockchain architecture, and roadmap on the Stellar network.
                </p>
                <div class="flex flex-wrap items-center gap-3 animate__animated animate__fadeInUp" style="animation-delay: 0.2s;">
                    <div class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-white/5 border border-white/10">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <span class="text-xs font-medium text-slate-300">Project Started: 2025</span>
                    </div>
                    <div class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-white/5 border border-white/10">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        <span class="text-xs font-medium text-slate-300">Last Updated: June 2026</span>
                    </div>
                    <div class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-white/5 border border-white/10">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        <span class="text-xs font-medium text-slate-300">Network: Stellar Testnet</span>
                    </div>
                </div>
            </section>

            <!-- 1. Overview -->
            <section id="overview" class="scroll-mt-32">
                <div class="flex items-center gap-4 mb-8">
                    <div class="w-10 h-10 rounded-xl bg-blue-500/10 text-blue-400 flex items-center justify-center font-black text-lg border border-blue-500/20">1</div>
                    <h2 class="text-3xl font-bold text-white">Project Overview</h2>
                </div>
                <div class="glass-panel p-8 md:p-10">
                    <p class="text-slate-300 leading-relaxed mb-8 text-lg">
                        Yolixa is a highly scalable, decentralized tipping platform built natively on the <strong>Stellar network</strong>. Our primary objective is to empower content creators by facilitating borderless, ultra-low-fee microtransactions between them and their audience, fully abstracting complex blockchain concepts into a modern web experience.
                    </p>
                    <div class="grid md:grid-cols-2 gap-6">
                        <div class="p-6 rounded-2xl bg-white/[0.02] border border-white/5 relative overflow-hidden transition-all duration-300 hover:border-purple-500/30">
                            <h4 class="text-white font-bold mb-3 font-outfit text-xl">Core Philosophy</h4>
                            <p class="text-sm text-slate-400 leading-relaxed">Creators should keep the value they generate. We aim to replace the traditional 5-15% legacy Web2 platform fee overhead with decentralized smart mechanics and minimal native network costs.</p>
                        </div>
                        <div class="p-6 rounded-2xl bg-white/[0.02] border border-white/5 relative overflow-hidden transition-all duration-300 hover:border-emerald-500/30">
                            <h4 class="text-white font-bold mb-3 font-outfit text-xl flex flex-wrap items-center gap-3">
                                Current Status
                                <span class="status-badge status-live">Live</span>
                            </h4>
                            <p class="text-sm text-slate-400 leading-relaxed">The core application plumbing—including non-custodial wallet integrations, fast tipping workflows, secure admin dashboards, and blockchain state monitoring—is deployed and functionally robust.</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- 2. Problem/Solution -->
            <section id="problem-solution" class="scroll-mt-32">
                <div class="flex flex-col md:flex-row gap-8">
                    <div class="flex-1 glass-panel p-8 text-center md:text-left border-t-4 border-t-red-500/40 relative">
                        <div class="w-12 h-12 rounded-xl bg-red-500/10 flex items-center justify-center mb-6 mx-auto md:mx-0 shadow-lg shadow-red-500/10">
                            <svg class="w-6 h-6 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        </div>
                        <h3 class="text-2xl font-bold text-white mb-6">The Legacy Problem</h3>
                        <ul class="space-y-5 text-left inline-block">
                            <li class="flex gap-4">
                                <span class="w-1.5 h-1.5 rounded-full bg-red-400 mt-2 shrink-0 shadow-[0_0_8px_rgba(248,113,113,0.8)]"></span>
                                <p class="text-sm text-slate-300 leading-relaxed"><strong class="text-white">High Platform Rent:</strong> Traditional platforms extract unsustainable percentages (5-20%) from every financial interaction.</p>
                            </li>
                            <li class="flex gap-4">
                                <span class="w-1.5 h-1.5 rounded-full bg-red-400 mt-2 shrink-0 shadow-[0_0_8px_rgba(248,113,113,0.8)]"></span>
                                <p class="text-sm text-slate-300 leading-relaxed"><strong class="text-white">Delayed Settlements:</strong> Creators are forced to wait days or weeks for manual fiat payout batches.</p>
                            </li>
                            <li class="flex gap-4">
                                <span class="w-1.5 h-1.5 rounded-full bg-red-400 mt-2 shrink-0 shadow-[0_0_8px_rgba(248,113,113,0.8)]"></span>
                                <p class="text-sm text-slate-300 leading-relaxed"><strong class="text-white">Geographical Borders:</strong> Global fans are locked out by regional payment processing limits and KYC walls.</p>
                            </li>
                        </ul>
                    </div>
                    <div class="flex-1 glass-panel p-8 text-center md:text-left border-t-4 border-t-emerald-500/40 relative overflow-hidden">
                        <div class="absolute inset-0 bg-gradient-to-br from-emerald-500/5 to-transparent"></div>
                        <div class="relative z-10">
                            <div class="w-12 h-12 rounded-xl bg-emerald-500/10 flex items-center justify-center mb-6 mx-auto md:mx-0 shadow-lg shadow-emerald-500/10">
                                <svg class="w-6 h-6 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <h3 class="text-2xl font-bold text-white mb-6">The Yolixa Solution</h3>
                            <ul class="space-y-5 text-left inline-block">
                                <li class="flex gap-4">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 mt-2 shrink-0 shadow-[0_0_8px_rgba(52,211,153,0.8)]"></span>
                                    <p class="text-sm text-slate-300 leading-relaxed"><strong class="text-white">Micro-fees:</strong> Stellar's network ensures transaction fees strictly remain under fractions of a penny.</p>
                                </li>
                                <li class="flex gap-4">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 mt-2 shrink-0 shadow-[0_0_8px_rgba(52,211,153,0.8)]"></span>
                                    <p class="text-sm text-slate-300 leading-relaxed"><strong class="text-white">Instant Settlement:</strong> Tipped assets arrive in the creator's self-custodial wallet within 3 to 5 seconds.</p>
                                </li>
                                <li class="flex gap-4">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 mt-2 shrink-0 shadow-[0_0_8px_rgba(52,211,153,0.8)]"></span>
                                    <p class="text-sm text-slate-300 leading-relaxed"><strong class="text-white">True Global Reach:</strong> Anyone with a decentralized Web3 wallet can frictionlessly tip without restrictive barriers.</p>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </section>

            <!-- 3. How Works -->
            <section id="how-it-works" class="scroll-mt-32">
                <div class="flex items-center gap-4 mb-8">
                    <div class="w-10 h-10 rounded-xl bg-blue-500/10 text-blue-400 flex items-center justify-center font-black text-lg border border-blue-500/20">3</div>
                    <h2 class="text-3xl font-bold text-white">How Yolixa Works</h2>
                </div>

                <div class="space-y-6">
                    <!-- Creator Flow -->
                    <div class="glass-panel p-6 md:p-8 flex flex-col md:flex-row gap-8 items-start md:items-center relative border-transparent hover:border-blue-500/30">
                        <div class="w-16 h-16 md:w-20 md:h-20 rounded-2xl bg-gradient-brand flex items-center justify-center shrink-0 shadow-lg shadow-blue-500/20">
                            <svg class="w-8 h-8 md:w-10 md:h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                        </div>
                        <div class="flex-1">
                            <div class="flex flex-wrap items-center gap-3 mb-3">
                                <h3 class="text-xl font-bold text-white">Creator Flow</h3>
                                <span class="status-badge status-live">Live</span>
                            </div>
                            <p class="text-slate-400 text-sm leading-relaxed mb-5">Content creators onboard securely using a standard Stellar wallet such as Freighter or Rabet. The frontend generates a public referral tipping URL, and the current MVP receives direct XLM testnet payments verified through Horizon.</p>
                            <div class="flex flex-wrap gap-2">
                                <span class="px-3 py-1 bg-white/[0.04] border border-white/10 rounded-md text-[0.7rem] uppercase tracking-wider font-bold text-slate-300">Wallet Connect Auth</span>
                                <span class="px-3 py-1 bg-white/[0.04] border border-white/10 rounded-md text-[0.7rem] uppercase tracking-wider font-bold text-slate-300">Dashboard Control</span>
                                <span class="px-3 py-1 bg-white/[0.04] border border-white/10 rounded-md text-[0.7rem] uppercase tracking-wider font-bold text-slate-300">Fast Trustline Setup</span>
                            </div>
                        </div>
                    </div>

                    <!-- Supporter Flow -->
                    <div class="glass-panel p-6 md:p-8 flex flex-col md:flex-row gap-8 items-start md:items-center relative border-transparent hover:border-purple-500/30">
                        <div class="w-16 h-16 md:w-20 md:h-20 rounded-2xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shrink-0 shadow-lg shadow-purple-500/20">
                            <svg class="w-8 h-8 md:w-10 md:h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                        </div>
                        <div class="flex-1">
                            <div class="flex flex-wrap items-center gap-3 mb-3">
                                <h3 class="text-xl font-bold text-white">Fan / Supporter Flow</h3>
                                <span class="status-badge status-live">Live</span>
                            </div>
                            <p class="text-slate-400 text-sm leading-relaxed mb-5">When a supporter engages via the provided creator URL, they select their preferred tipping asset. The application securely packages a unique XDR envelope, requests a web-client signature, and accurately delegates the transaction to the public Stellar Horizon API. The transfer process is entirely non-custodial and P2P.</p>
                            <div class="flex flex-wrap gap-2">
                                <span class="px-3 py-1 bg-white/[0.04] border border-white/10 rounded-md text-[0.7rem] uppercase tracking-wider font-bold text-slate-300">Dynamic UI Input</span>
                                <span class="px-3 py-1 bg-white/[0.04] border border-white/10 rounded-md text-[0.7rem] uppercase tracking-wider font-bold text-slate-300">Pre-Fund Network Checks</span>
                                <span class="px-3 py-1 bg-white/[0.04] border border-white/10 rounded-md text-[0.7rem] uppercase tracking-wider font-bold text-slate-300">Direct XDR Signing</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- 4. Tech / Stellar -->
            <section id="stellar-integration" class="scroll-mt-32">
                <div class="flex items-center gap-4 mb-8">
                    <div class="w-10 h-10 rounded-xl bg-blue-500/10 text-blue-400 flex items-center justify-center font-black text-lg border border-blue-500/20">4</div>
                    <h2 class="text-3xl font-bold text-white">Stellar Network Architecture</h2>
                </div>

                <div class="glass-panel p-8 md:p-10">
                    <p class="text-slate-300 mb-8 leading-relaxed text-lg">Yolixa heavily leverages Stellar for its remarkable throughput and native smart asset protocols. The internal service handlers are inherently defensive against common Web3 failure vectors.</p>

                    <div class="grid lg:grid-cols-2 gap-8 relative">
                        <div class="bg-white/[0.02] border border-white/5 p-6 rounded-2xl relative z-10 transition-colors hover:bg-white/[0.04]">
                            <h4 class="text-lg font-bold text-white mb-4 flex flex-wrap items-center gap-3 justify-between">
                                Trustline Automation
                                <span class="status-badge status-live">Live</span>
                            </h4>
                            <p class="text-sm text-slate-400 leading-relaxed mb-4">A custom backend abstraction—specifically through our <code class="text-blue-400 font-medium font-mono text-[0.7rem] tracking-tight bg-blue-500/10 px-1.5 py-0.5 rounded">buildTrustlineXdr</code> logic—authorizes creators to trust incoming external assets (e.g. USDC, YLX) with a heavily simplified, 1-click frontend request.</p>
                        </div>
                        <div class="bg-white/[0.02] border border-white/5 p-6 rounded-2xl relative z-10 transition-colors hover:bg-white/[0.04]">
                            <h4 class="text-lg font-bold text-white mb-4 flex flex-wrap items-center gap-3 justify-between">
                                Pre-Funding Fail-Safes
                                <span class="status-badge status-live">Live</span>
                            </h4>
                            <p class="text-sm text-slate-400 leading-relaxed mb-4">Stellar transactions universally block unsupported drops (the <code>op_no_destination</code> error). Yolixa's TipService utilizes aggressive pre-transaction polling on the backend and frontend to ensure receiver accounts are primed, effectively blocking failed broadcast attempts.</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- 5. Fees & Tokenomics -->
            <section id="fees-rewards" class="scroll-mt-32">
                <div class="flex items-center gap-4 mb-8">
                    <div class="w-10 h-10 rounded-xl bg-blue-500/10 text-blue-400 flex items-center justify-center font-black text-lg border border-blue-500/20">5</div>
                    <h2 class="text-3xl font-bold text-white">Platform Fees & Token Rewards</h2>
                </div>

                <div class="grid lg:grid-cols-2 gap-8 relative">
                    <div class="glass-panel p-8 border-t-2 border-emerald-500/50">
                        <div class="w-12 h-12 bg-emerald-500/10 border border-emerald-500/20 rounded-xl flex items-center justify-center mb-6 shadow-lg shadow-emerald-500/10">
                            <svg class="w-6 h-6 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div class="flex flex-wrap items-center gap-3 mb-4">
                            <h4 class="text-2xl font-bold text-white">Adaptive Fee Module</h4>
                            <span class="status-badge status-live">Live</span>
                        </div>
                        <p class="text-sm text-slate-400 mb-6 leading-relaxed">Yolixa utilizes a granular fee logic structured to incentivize native ecosystem growth while remaining highly competitive against legacy platforms.</p>
                        <div class="text-sm text-slate-300 bg-[#06090F] border border-white/5 p-5 rounded-xl font-mono shadow-inner">
                            <div class="flex justify-between items-center border-b border-white/5 pb-3 mb-3">
                                <span class="font-medium">Asset: YLX</span>
                                <span class="px-2 py-1 bg-emerald-500/20 text-emerald-400 rounded-md font-bold text-xs tracking-wide">0.00% Fee</span>
                            </div>
                            <div class="flex justify-between items-center border-b border-white/5 pb-3 mb-3">
                                <span class="font-medium">Asset: XLM</span>
                                <span class="px-2 py-1 bg-blue-500/20 text-blue-400 rounded-md font-bold text-xs tracking-wide">1.50% Fee</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="font-medium">Asset: USDC</span>
                                <span class="px-2 py-1 bg-blue-500/20 text-blue-400 rounded-md font-bold text-xs tracking-wide">1.50% Fee</span>
                            </div>
                        </div>
                    </div>

                    <div class="glass-panel p-8 border border-amber-500/20 border-t-2 border-t-amber-500/50 relative overflow-hidden flex flex-col">
                        <div class="absolute top-0 right-0 p-6 opacity-[0.03] pointer-events-none">
                            <svg class="w-32 h-32 text-amber-500" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                        </div>
                        <div class="relative z-10 flex-1">
                            <div class="flex flex-wrap items-center gap-3 mb-4">
                                <h4 class="text-2xl font-bold text-white">YLX Rewards Engine</h4>
                                <span class="status-badge status-progress">Partially Implemented</span>
                            </div>
                            <p class="text-sm text-slate-400 mb-6 leading-relaxed">The YLX token system is being intentionally designed to reward active participants. Foundational portions of the reward logic exist in the backend architecture.</p>

                            <div class="bg-amber-500/5 border border-amber-500/20 p-5 rounded-xl mb-4 transition-colors hover:bg-amber-500/10">
                                <div class="flex flex-wrap justify-between items-center mb-2 gap-2">
                                    <h5 class="text-white text-sm font-bold">Profile Accumulation</h5>
                                    <span class="text-[0.6rem] uppercase tracking-wider font-bold text-amber-500 px-2 py-1 bg-amber-500/10 rounded">In Progress</span>
                                </div>
                                <p class="text-xs text-slate-400 leading-relaxed">System architecture contains scalable logic for crediting YLX proportional to tip volume natively.</p>
                            </div>
                            <div class="bg-blue-500/5 border border-blue-500/20 p-5 rounded-xl transition-colors hover:bg-blue-500/10">
                                <div class="flex flex-wrap justify-between items-center mb-2 gap-2">
                                    <h5 class="text-white text-sm font-bold">Supporter Incentives</h5>
                                    <span class="text-[0.6rem] uppercase tracking-wider font-bold text-blue-400 px-2 py-1 bg-blue-400/10 rounded">Planned</span>
                                </div>
                                <p class="text-xs text-slate-400 leading-relaxed">Future updates will safely establish an algorithmic protocol to automatically reward ecosystem fans.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- 6. Security -->
            <section id="security" class="scroll-mt-32">
                <div class="flex items-center gap-4 mb-8">
                    <div class="w-10 h-10 rounded-xl bg-blue-500/10 text-blue-400 flex items-center justify-center font-black text-lg border border-blue-500/20">6</div>
                    <h2 class="text-3xl font-bold text-white">Security & Operational Control</h2>
                </div>

                <div class="glass-panel p-0 overflow-hidden flex flex-col md:flex-row">
                    <div class="p-8 md:w-1/2 border-b md:border-b-0 md:border-r border-white/5 relative bg-white/[0.01]">
                        <div class="flex flex-wrap items-center gap-3 mb-4">
                            <h4 class="text-xl font-bold text-white">Ledger Verification</h4>
                            <span class="status-badge status-live">Live</span>
                        </div>
                        <p class="text-sm text-slate-400 leading-relaxed">To rigorously avert spoofed financial payloads, the backend forcefully verifies submitted transaction hashes directly against the unified Horizon RPC API. It definitively asserts that tip amounts, destination receivers, and asset hashes identically mirror the blockchain.</p>
                    </div>
                    <div class="p-8 md:w-1/2 bg-white/[0.02]">
                        <div class="flex flex-wrap items-center gap-3 mb-4">
                            <h4 class="text-xl font-bold text-white">Observability Matrix</h4>
                            <span class="status-badge status-live">Live</span>
                        </div>
                        <p class="text-sm text-slate-400 leading-relaxed">Crucial application errors log securely into dedicated monitoring channels. The deployed <code class="text-purple-300 font-mono text-[0.7rem] bg-purple-500/10 px-1 rounded">AdminPanel</code> offers authorized oversight to modulate fee parameters securely and effectively manage platform metrics.</p>
                    </div>
                </div>
            </section>

            <!-- 7. Roadmap -->
            <section id="roadmap" class="scroll-mt-32 border-t border-white/5 pt-16">
                <div class="text-center mb-16">
                    <h2 class="text-4xl font-bold text-white mb-5 font-outfit">Current Roadmap Context</h2>
                    <p class="text-slate-400 max-w-2xl mx-auto text-lg leading-relaxed">A transparent tracking of the project's foundational delivery milestones and upcoming decentralized ecosystem upgrades.</p>
                </div>

                <div class="relative border-l border-white/10 ml-4 md:ml-[50%] md:-translate-x-px space-y-16 pb-10">

                    <!-- Phase 1 -->
                    <div class="relative pl-10 md:pl-0 group">
                        <div class="absolute left-[-8.5px] md:left-1/2 top-1.5 w-4 h-4 rounded-full bg-emerald-500 ring-4 ring-[#0B0F19] md:-translate-x-1/2 z-10 box-content transition-transform group-hover:scale-125 duration-300"></div>
                        <div class="md:w-[45%] md:pr-14 ml-auto md:ml-0 text-left md:text-right">
                            <div class="inline-block px-3 py-1 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 font-bold text-[0.7rem] tracking-widest uppercase mb-4 rounded-md shadow-sm">Phase 1: Achieved</div>
                            <h4 class="text-2xl font-bold text-white mb-4">Blockchain Foundation</h4>
                            <ul class="text-sm text-slate-400 space-y-3 inline-block text-left md:text-right">
                                <li class="flex items-center md:flex-row-reverse gap-3"><svg class="w-4 h-4 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Non-custodial Wallet Authentication</li>
                                <li class="flex items-center md:flex-row-reverse gap-3"><svg class="w-4 h-4 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Base Tipping Logic (XLM/USDC)</li>
                                <li class="flex items-center md:flex-row-reverse gap-3"><svg class="w-4 h-4 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Ledger Verification Engine</li>
                                <li class="flex items-center md:flex-row-reverse gap-3"><svg class="w-4 h-4 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Administrative Dashboard Systems</li>
                            </ul>
                        </div>
                    </div>

                    <!-- Phase 2 -->
                    <div class="relative pl-10 md:pl-0 group">
                        <div class="absolute left-[-8.5px] md:left-1/2 top-1.5 w-4 h-4 rounded-full bg-amber-500 ring-4 ring-[#0B0F19] shadow-[0_0_15px_rgba(245,158,11,0.5)] md:-translate-x-1/2 z-10 box-content transition-transform group-hover:scale-125 duration-300"></div>
                        <div class="md:w-[45%] md:pl-14 ml-auto">
                            <div class="inline-block px-3 py-1 bg-amber-500/10 border border-amber-500/20 text-amber-400 font-bold text-[0.7rem] tracking-widest uppercase mb-4 rounded-md shadow-sm">Phase 2: In Progress</div>
                            <h4 class="text-2xl font-bold text-white mb-4">Utility Expansion</h4>
                            <ul class="text-sm text-slate-400 space-y-3">
                                <li class="flex items-center gap-3 text-emerald-300"><svg class="w-4 h-4 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Sub-Zero Fee Discount Engine</li>
                                <li class="flex items-center gap-3 text-emerald-300"><svg class="w-4 h-4 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Implementation of Native YLX Tipping</li>
                                <li class="flex items-center gap-3 text-slate-400"><svg class="w-4 h-4 text-amber-500 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/></svg> YLX Rewards Pipeline Configuration</li>
                                <li class="flex items-center gap-3 text-slate-500"><svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg> Scalable Distribution Logic</li>
                            </ul>
                        </div>
                    </div>

                    <!-- Phase 3 -->
                    <div class="relative pl-10 md:pl-0 group">
                        <div class="absolute left-[-8.5px] md:left-1/2 top-1.5 w-4 h-4 rounded-full bg-blue-500/30 ring-4 ring-[#0B0F19] md:-translate-x-1/2 z-10 box-content transition-transform group-hover:scale-125 duration-300"></div>
                        <div class="md:w-[45%] md:pr-14 ml-auto md:ml-0 text-left md:text-right opacity-80">
                            <div class="inline-block px-3 py-1 bg-blue-500/10 border border-blue-500/20 text-blue-400 font-bold text-[0.7rem] tracking-widest uppercase mb-4 rounded-md shadow-sm">Phase 3: Planned Scope</div>
                            <h4 class="text-2xl font-bold text-white mb-4">Ecosystem Maturity</h4>
                            <ul class="text-sm text-slate-400 space-y-3 inline-block text-left md:text-right">
                                <li class="flex items-center md:flex-row-reverse gap-3"><svg class="w-4 h-4 shrink-0 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M12 5l7 7-7 7"/></svg> Web3 Authenticated Content Locking</li>
                                <li class="flex items-center md:flex-row-reverse gap-3"><svg class="w-4 h-4 shrink-0 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M12 5l7 7-7 7"/></svg> Token Staking Web Interfaces</li>
                                <li class="flex items-center md:flex-row-reverse gap-3"><svg class="w-4 h-4 shrink-0 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M12 5l7 7-7 7"/></svg> Deflationary Tokenomics Enhancements</li>
                                <li class="flex items-center md:flex-row-reverse gap-3"><svg class="w-4 h-4 shrink-0 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M12 5l7 7-7 7"/></svg> DAO Framework Initializations</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </section>

        </main>
    </div>

    <!-- Footer -->
    <footer class="border-t border-white/5 bg-[#06090F] py-14 relative z-10 mt-auto">
        <div class="max-w-7xl mx-auto px-6 text-center">
            <div class="w-14 h-14 mx-auto rounded-xl bg-gradient-brand flex items-center justify-center mb-6 shadow-lg shadow-purple-500/10">
                <svg class="w-7 h-7 text-white" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
                </svg>
            </div>
            <p class="text-slate-300 font-medium text-lg mb-4 tracking-wide font-outfit">Empowering Creators Through Decentralization</p>
            <p class="text-sm text-slate-500">&copy; 2025-2026 Yolixa Network. All rights reserved.</p>
        </div>
    </footer>

    <script>
        // Desktop Navbar Scroll Effect
        const mainNav = document.getElementById('mainNav');
        window.addEventListener('scroll', () => {
            if (window.scrollY > 50) {
                mainNav.classList.add('nav-scrolled');
                mainNav.classList.remove('py-4');
                mainNav.classList.add('py-3');
            } else {
                mainNav.classList.remove('nav-scrolled');
                mainNav.classList.add('py-4');
                mainNav.classList.remove('py-3');
            }
        });

        // Setup Intersection Observer for robust Scroll Spy (Top and Sidebar navs)
        document.addEventListener('DOMContentLoaded', () => {
            const sections = document.querySelectorAll('section[id]');
            const tocLinks = document.querySelectorAll('.toc-link');
            const topNavLinks = document.querySelectorAll('.top-nav-link');
            const mobileNavLinks = document.querySelectorAll('.mobile-nav-link');

            const observerOptions = {
                root: null,
                rootMargin: '-10% 0px -80% 0px',
                threshold: 0
            };

            const observerCallback = (entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const targetId = entry.target.getAttribute('id');

                        // Update Sidebar TOC
                        tocLinks.forEach(link => {
                            link.classList.remove('active');
                            if (link.getAttribute('href') === `#${targetId}`) {
                                link.classList.add('active');
                            }
                        });

                        // Update Top Nav
                        topNavLinks.forEach(link => {
                            link.classList.remove('active');
                            if (link.getAttribute('href') === `#${targetId}`) {
                                link.classList.add('active');
                            }
                        });

                        // Update Mobile Nav
                        mobileNavLinks.forEach(link => {
                            link.classList.remove('text-purple-400', 'bg-white/5');
                            link.classList.add('text-slate-300');
                            if (link.getAttribute('href') === `#${targetId}`) {
                                link.classList.add('text-purple-400', 'bg-white/5');
                                link.classList.remove('text-slate-300');
                            }
                        });
                    }
                });
            };

            const observer = new IntersectionObserver(observerCallback, observerOptions);
            sections.forEach(sec => observer.observe(sec));

            // Smooth Scroll on Click
            const allLinks = [...tocLinks, ...topNavLinks, ...mobileNavLinks];
            allLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    const targetId = this.getAttribute('href');
                    if (targetId.startsWith('#')) {
                        e.preventDefault();
                        const targetSection = document.querySelector(targetId);
                        if (targetSection) {
                            // close mobile menu if open
                            if(document.getElementById('mobile-menu').classList.contains('open')) {
                                closeMobileMenu();
                            }

                            const navHeight = document.getElementById('mainNav').offsetHeight;
                            const targetPosition = targetSection.getBoundingClientRect().top + window.pageYOffset - navHeight - 32;
                            window.scrollTo({
                                top: targetPosition,
                                behavior: 'smooth'
                            });
                        }
                    }
                });
            });
        });

        // Mobile Drawer Logic
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const closeMobileBtn = document.getElementById('closeMobileBtn');
        const mobileMenu = document.getElementById('mobile-menu');
        const overlay = document.getElementById('mobileOverlay');

        function openMobileMenu() {
            mobileMenu.classList.add('open');
            overlay.classList.add('show');
            document.body.style.overflow = 'hidden';
        }

        function closeMobileMenu() {
            mobileMenu.classList.remove('open');
            overlay.classList.remove('show');
            document.body.style.overflow = '';
        }

        mobileMenuBtn.addEventListener('click', openMobileMenu);
        closeMobileBtn.addEventListener('click', closeMobileMenu);
        overlay.addEventListener('click', closeMobileMenu);
    </script>
</body>
</html>
