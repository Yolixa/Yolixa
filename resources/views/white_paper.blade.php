<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="{{ asset('assets/images/favicon-32x32.png') }}">
    <title>Yolixa Whitepaper - Stellar Soroban Creator Payments</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
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
            background: rgba(19, 26, 42, 0.38);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 1.25rem;
            box-shadow: 0 20px 70px rgba(0, 0, 0, 0.22);
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

        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.2rem 0.6rem;
            border-radius: 9999px;
            font-size: 0.65rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            white-space: nowrap;
        }

        .status-implemented {
            background: rgba(16, 185, 129, 0.1);
            color: #34D399;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        .status-pending {
            background: rgba(245, 158, 11, 0.1);
            color: #FBBF24;
            border: 1px solid rgba(245, 158, 11, 0.3);
        }

        .status-planned {
            background: rgba(59, 130, 246, 0.1);
            color: #60A5FA;
            border: 1px solid rgba(59, 130, 246, 0.3);
        }

        .status-limited {
            background: rgba(248, 113, 113, 0.1);
            color: #FCA5A5;
            border: 1px solid rgba(248, 113, 113, 0.3);
        }

        .grid-pattern {
            background-image: linear-gradient(rgba(255, 255, 255, 0.025) 1px, transparent 1px),
            linear-gradient(90deg, rgba(255, 255, 255, 0.025) 1px, transparent 1px);
            background-size: 42px 42px;
            background-position: center center;
        }

        .toc-link {
            color: #94A3B8;
            display: block;
            padding: 0.45rem 0 0.45rem 1rem;
            border-left: 2px solid rgba(255,255,255,0.06);
            font-size: 0.82rem;
            transition: all 0.2s ease;
        }

        .toc-link:hover,
        .toc-link.active {
            color: #A78BFA;
            border-left-color: #8B5CF6;
            background: linear-gradient(90deg, rgba(139, 92, 246, 0.08), transparent);
        }

        .nav-scrolled {
            background: rgba(6, 9, 15, 0.92);
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
        }

        pre {
            white-space: pre-wrap;
            word-break: break-word;
        }
    </style>
</head>
<body class="relative min-h-screen selection:bg-purple-500/30">
    <div class="fixed inset-0 grid-pattern z-[-2]"></div>
    <div class="fixed inset-x-0 top-0 h-96 bg-gradient-to-b from-purple-900/25 via-blue-900/10 to-transparent z-[-1] pointer-events-none"></div>

    <nav id="mainNav" class="fixed top-0 w-full z-50 transition-all duration-300 px-6 py-4 border-b border-transparent">
        <div class="max-w-7xl mx-auto flex justify-between items-center">
            <a href="{{ url('/') }}" class="flex items-center gap-3 group">
                <div class="w-10 h-10 rounded-xl bg-gradient-brand flex items-center justify-center shadow-lg shadow-purple-500/20 transition-transform duration-300 group-hover:scale-105">
                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
                    </svg>
                </div>
                <span class="text-2xl font-outfit font-bold text-white tracking-wide">Yolixa</span>
            </a>

            <div class="hidden md:flex items-center gap-6 text-sm font-medium text-slate-300">
                <a href="#executive-summary" class="hover:text-white transition-colors">Summary</a>
                <a href="#architecture" class="hover:text-white transition-colors">Architecture</a>
                <a href="#security-model" class="hover:text-white transition-colors">Security</a>
                <a href="#roadmap" class="hover:text-white transition-colors">Roadmap</a>
            </div>

            <a href="{{ url('/') }}" class="inline-flex px-5 py-2 rounded-lg bg-white/5 hover:bg-white/10 border border-white/10 text-white font-semibold text-sm transition-all duration-300">
                Back to App
            </a>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-6 pt-32 pb-24 flex flex-col lg:flex-row gap-12 relative">
        <aside class="hidden lg:block w-72 shrink-0">
            <div class="sticky top-28 glass-panel p-6">
                <h3 class="text-xs uppercase tracking-wider text-slate-400 font-bold mb-4">Contents</h3>
                <nav class="flex flex-col">
                    <a href="#executive-summary" class="toc-link">1. Executive Summary</a>
                    <a href="#problem" class="toc-link">2. Problem</a>
                    <a href="#why-stellar" class="toc-link">3. Why Stellar / Soroban</a>
                    <a href="#architecture" class="toc-link">4. Current Architecture</a>
                    <a href="#tip-router" class="toc-link">5. YolixaTipRouter</a>
                    <a href="#mvp-status" class="toc-link">6. Current MVP Status</a>
                    <a href="#assets" class="toc-link">7. Assets</a>
                    <a href="#ylx" class="toc-link">8. YLX</a>
                    <a href="#fees" class="toc-link">9. Fees</a>
                    <a href="#security-model" class="toc-link">10. Security Model</a>
                    <a href="#open-source" class="toc-link">11. Open Source</a>
                    <a href="#competition" class="toc-link">12. Competition</a>
                    <a href="#roadmap" class="toc-link">13. Roadmap</a>
                    <a href="#limitations" class="toc-link">14. Limitations</a>
                </nav>
                <div class="mt-8 pt-6 border-t border-white/10 space-y-3">
                    <div class="flex items-center justify-between gap-3 text-xs">
                        <span class="text-slate-400">Network</span>
                        <span class="status-badge status-pending">Testnet</span>
                    </div>
                    <div class="flex items-center justify-between gap-3 text-xs">
                        <span class="text-slate-400">Asset</span>
                        <span class="status-badge status-implemented">XLM</span>
                    </div>
                    <div class="flex items-center justify-between gap-3 text-xs">
                        <span class="text-slate-400">Public Proof</span>
                        <span class="status-badge status-pending">Pending</span>
                    </div>
                </div>
            </div>
        </aside>

        <main class="flex-1 space-y-20 min-w-0">
            <section class="pt-6 pb-12 border-b border-white/10">
                <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-purple-500/10 border border-purple-500/20 text-purple-300 font-semibold text-xs mb-6">
                    <span class="w-2 h-2 rounded-full bg-purple-400"></span>
                    SCF Testnet MVP Candidate
                </div>
                <h1 class="text-5xl md:text-7xl font-black text-white leading-[1.05] mb-6 tracking-tight">
                    Non-Custodial Creator Payments <span class="text-gradient">on Stellar.</span>
                </h1>
                <p class="text-lg md:text-xl text-slate-400 max-w-3xl leading-relaxed mb-8">
                    Yolixa is a creator monetization layer built around a Soroban payment router. The current MVP supports Freighter and Rabet for XLM tipping on Stellar Testnet, with atomic creator/treasury settlement, on-chain receipts, and backend verification.
                </p>
                <div class="flex flex-wrap gap-3">
                    <span class="status-badge status-implemented">Router Implemented</span>
                    <span class="status-badge status-implemented">Laravel Integrated</span>
                    <span class="status-badge status-pending">Public Testnet Proof Pending</span>
                    <span class="status-badge status-planned">Mainnet Future Scope</span>
                </div>
            </section>

            <section id="executive-summary" class="scroll-mt-28">
                <div class="flex items-center gap-4 mb-6">
                    <div class="w-10 h-10 rounded-xl bg-blue-500/10 text-blue-400 flex items-center justify-center font-black border border-blue-500/20">1</div>
                    <h2 class="text-3xl font-bold text-white">Executive Summary</h2>
                </div>
                <div class="glass-panel p-8 md:p-10 space-y-5 text-slate-300 leading-relaxed">
                    <p>
                        Yolixa is a non-custodial creator payment and monetization layer built on Stellar and Soroban. The current Testnet MVP lets creators publish Yolixa profiles or referral links and lets fans approve XLM tips from Freighter or Rabet.
                    </p>
                    <p>
                        The core primitive is `YolixaTipRouter`, a Soroban contract that atomically routes the fan-authorized payment into creator payout and Yolixa treasury fee. Yolixa does not take custody of the fan's funds, and the backend independently verifies the final on-chain transaction before recording a confirmed tip.
                    </p>
                </div>
            </section>

            <section id="problem" class="scroll-mt-28">
                <div class="flex items-center gap-4 mb-6">
                    <div class="w-10 h-10 rounded-xl bg-red-500/10 text-red-300 flex items-center justify-center font-black border border-red-500/20">2</div>
                    <h2 class="text-3xl font-bold text-white">Problem</h2>
                </div>
                <div class="grid md:grid-cols-2 gap-5">
                    @foreach ([
                        'High platform fees reduce what creators keep from direct fan support.',
                        'Settlement friction makes small international payments feel slower and more complex than they should.',
                        'Geographic and payment limitations keep some fans and creators outside existing monetization rails.',
                        'Platform dependency leaves creators exposed to changing rules, payout policies, and account controls.',
                        'Very small direct payments are poorly supported by many traditional systems.',
                        'Collaborative payouts are hard to execute fairly when several creators share one work.'
                    ] as $problem)
                        <div class="glass-panel p-5">
                            <p class="text-sm text-slate-300 leading-relaxed">{{ $problem }}</p>
                        </div>
                    @endforeach
                </div>
            </section>

            <section id="why-stellar" class="scroll-mt-28">
                <div class="flex items-center gap-4 mb-6">
                    <div class="w-10 h-10 rounded-xl bg-cyan-500/10 text-cyan-300 flex items-center justify-center font-black border border-cyan-500/20">3</div>
                    <h2 class="text-3xl font-bold text-white">Why Stellar / Soroban</h2>
                </div>
                <div class="glass-panel p-8 md:p-10">
                    <div class="grid md:grid-cols-2 gap-6">
                        <div>
                            <h3 class="text-xl font-bold text-white mb-3">Stellar Settlement</h3>
                            <p class="text-sm text-slate-400 leading-relaxed">Stellar is well suited to payment products because settlement is fast, fees are low, and accounts can hold native XLM and issued assets through a mature asset model.</p>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-white mb-3">Stellar Asset Contracts</h3>
                            <p class="text-sm text-slate-400 leading-relaxed">SACs let Soroban contracts interact with Stellar assets through a consistent token interface. Yolixa uses the native XLM SAC in the current MVP and can later add validated assets such as USDC through explicit allowlisting.</p>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-white mb-3">Soroban Authorization</h3>
                            <p class="text-sm text-slate-400 leading-relaxed">Soroban authorization allows the fan wallet to approve the exact invocation that moves funds. Yolixa can route payments without storing fan secrets or signing on behalf of fans.</p>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-white mb-3">Programmability</h3>
                            <p class="text-sm text-slate-400 leading-relaxed">A contract-level router enables deterministic fee splitting, replay protection, receipts, creator stats, pause controls, and future collaborative payout primitives while keeping settlement atomic.</p>
                        </div>
                    </div>
                </div>
            </section>

            <section id="architecture" class="scroll-mt-28">
                <div class="flex items-center gap-4 mb-6">
                    <div class="w-10 h-10 rounded-xl bg-purple-500/10 text-purple-300 flex items-center justify-center font-black border border-purple-500/20">4</div>
                    <h2 class="text-3xl font-bold text-white">Current Architecture</h2>
                </div>
                <div class="glass-panel p-8 md:p-10 space-y-6">
                    <pre class="text-sm md:text-base leading-relaxed text-slate-200 bg-[#06090F] border border-white/10 rounded-xl p-6 font-mono">Creator creates Yolixa profile
        |
        v
Public creator/referral link
        |
        v
Fan connects Freighter or Rabet
        |
        v
Server creates TipIntent
        |
        v
Fan authorizes Soroban transaction
        |
        v
YolixaTipRouter
   |              |
   v              v
Creator       Treasury
98.5%          1.5%
        |
        v
Backend independently verifies on-chain result
        |
        v
Tip confirmed + creator stats/dashboard</pre>
                    <p class="text-slate-300 leading-relaxed">
                        Yolixa does not custody the fan's funds. The fan signs with Freighter or Rabet, the router executes the payment split atomically, and Laravel records the payment only after verification succeeds.
                    </p>
                </div>
            </section>

            <section id="tip-router" class="scroll-mt-28">
                <div class="flex items-center gap-4 mb-6">
                    <div class="w-10 h-10 rounded-xl bg-emerald-500/10 text-emerald-300 flex items-center justify-center font-black border border-emerald-500/20">5</div>
                    <h2 class="text-3xl font-bold text-white">YolixaTipRouter</h2>
                </div>
                <div class="glass-panel p-8 md:p-10 space-y-6">
                    <p class="text-slate-300 leading-relaxed">
                        `YolixaTipRouter` is initialized with an admin address, treasury address, and fee basis points. The current app configuration uses 150 BPS. The contract enforces a maximum configured fee cap of 300 BPS.
                    </p>
                    <div class="grid md:grid-cols-2 gap-5">
                        @foreach ([
                            'Admin-only controls require authorization for fee, treasury, token allowlist, pause, and unpause changes.',
                            'The token allowlist rejects disabled or unexpected SAC addresses.',
                            '`tip` routes one creator payout plus platform fee.',
                            '`tip_split` supports collaborative contract-level payouts with BPS allocations.',
                            'Replay protection stores receipts by sender plus tip_id.',
                            'Receipts and creator stats are queryable through read-only contract calls.',
                            'Typed events expose standard tip, split tip, fee, treasury, token, and pause changes.',
                            'Failed token transfers roll back contract state and earlier transfers atomically.'
                        ] as $item)
                            <div class="bg-white/[0.03] border border-white/10 rounded-xl p-5">
                                <p class="text-sm text-slate-300 leading-relaxed">{{ $item }}</p>
                            </div>
                        @endforeach
                    </div>
                    <p class="text-sm text-amber-200 bg-amber-500/10 border border-amber-500/20 rounded-xl p-4">
                        Contract capabilities and product capabilities are intentionally separated: `tip_split` exists and is tested at contract level, while product-level split tipping UI/backend integration remains future funded scope.
                    </p>
                </div>
            </section>

            <section id="mvp-status" class="scroll-mt-28">
                <div class="flex items-center gap-4 mb-6">
                    <div class="w-10 h-10 rounded-xl bg-blue-500/10 text-blue-300 flex items-center justify-center font-black border border-blue-500/20">6</div>
                    <h2 class="text-3xl font-bold text-white">Current MVP Status</h2>
                </div>
                <div class="glass-panel p-6 mb-5 space-y-3">
                    <h3 class="text-xl font-bold text-white">Supported Testnet Wallets</h3>
                    <p class="text-sm font-semibold text-white">Supported Testnet wallets:</p>
                    <ul class="grid sm:grid-cols-2 gap-3 text-sm text-slate-300">
                        <li class="rounded-lg border border-white/10 bg-white/[0.03] px-4 py-3">Freighter</li>
                        <li class="rounded-lg border border-white/10 bg-white/[0.03] px-4 py-3">Rabet</li>
                    </ul>
                    <p class="text-sm text-slate-300 leading-relaxed">
                        Freighter and Rabet are current MVP wallet scope. Rabet is not SCF-funded future scope. Until real browser Testnet transactions are captured, both wallet paths should be described as: "Implemented - Testnet browser validation pending."
                    </p>
                </div>
                <div class="grid md:grid-cols-2 gap-5">
                    @foreach ([
                        ['Stellar Testnet', 'Implemented'],
                        ['XLM Soroban tipping', 'Implemented'],
                        ['Wallet authentication', 'Implemented'],
                        ['Freighter wallet integration', 'Browser Validation Pending'],
                        ['Rabet wallet integration', 'Browser Validation Pending'],
                        ['Creator profiles/referral links', 'Implemented'],
                        ['Non-custodial TipRouter', 'Implemented'],
                        ['Creator/treasury atomic routing', 'Implemented'],
                        ['Transaction verification', 'Implemented'],
                        ['Replay/idempotency protection', 'Implemented'],
                        ['Automated contract/application tests', 'Implemented'],
                        ['GitHub CI workflow', 'Implemented'],
                        ['Public Testnet deployment evidence', 'Proof Pending'],
                        ['Browser wallet E2E evidence', 'Proof Pending']
                    ] as [$label, $status])
                        <div class="glass-panel p-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                            <span class="text-sm text-slate-300">{{ $label }}</span>
                            <span class="status-badge self-start sm:self-auto {{ $status === 'Implemented' ? 'status-implemented' : 'status-pending' }}">{{ $status }}</span>
                        </div>
                    @endforeach
                </div>
            </section>

            <section id="assets" class="scroll-mt-28">
                <div class="flex items-center gap-4 mb-6">
                    <div class="w-10 h-10 rounded-xl bg-sky-500/10 text-sky-300 flex items-center justify-center font-black border border-sky-500/20">7</div>
                    <h2 class="text-3xl font-bold text-white">Assets</h2>
                </div>
                <div class="grid md:grid-cols-3 gap-5">
                    <div class="glass-panel p-6 border-emerald-500/25">
                        <span class="status-badge status-implemented mb-4">Implemented</span>
                        <h3 class="text-xl font-bold text-white mb-3">XLM</h3>
                        <p class="text-sm text-slate-400 leading-relaxed">XLM is the current Phase 2 Soroban product asset. The router uses the native Testnet XLM SAC after explicit allowlisting.</p>
                    </div>
                    <div class="glass-panel p-6 border-blue-500/25">
                        <span class="status-badge status-planned mb-4">Planned</span>
                        <h3 class="text-xl font-bold text-white mb-3">USDC</h3>
                        <p class="text-sm text-slate-400 leading-relaxed">USDC is planned funded scope. It requires approved SAC configuration, validation, allowlisting, trustline UX where needed, and product verification before being presented as live.</p>
                    </div>
                    <div class="glass-panel p-6 border-blue-500/25">
                        <span class="status-badge status-planned mb-4">Future</span>
                        <h3 class="text-xl font-bold text-white mb-3">Other Assets</h3>
                        <p class="text-sm text-slate-400 leading-relaxed">Additional Stellar assets are future scope only after explicit allowlisting, UX support, and verifier coverage.</p>
                    </div>
                </div>
            </section>

            <section id="ylx" class="scroll-mt-28">
                <div class="flex items-center gap-4 mb-6">
                    <div class="w-10 h-10 rounded-xl bg-amber-500/10 text-amber-300 flex items-center justify-center font-black border border-amber-500/20">8</div>
                    <h2 class="text-3xl font-bold text-white">YLX</h2>
                </div>
                <div class="glass-panel p-8 md:p-10 space-y-5 text-slate-300 leading-relaxed">
                    <p>
                        YLX should be treated as an experimental future creator loyalty and reward concept. The current Testnet MVP does not include production YLX distribution, reward settlement, investment utility, price appreciation mechanics, or guaranteed rewards.
                    </p>
                    <p>
                        Existing configuration and dashboard fields are legacy/experimental surfaces. They should not be described as a live token economy in SCF materials.
                    </p>
                </div>
            </section>

            <section id="fees" class="scroll-mt-28">
                <div class="flex items-center gap-4 mb-6">
                    <div class="w-10 h-10 rounded-xl bg-indigo-500/10 text-indigo-300 flex items-center justify-center font-black border border-indigo-500/20">9</div>
                    <h2 class="text-3xl font-bold text-white">Fees</h2>
                </div>
                <div class="glass-panel p-8 md:p-10">
                    <div class="grid md:grid-cols-3 gap-5">
                        <div class="bg-white/[0.03] border border-white/10 rounded-xl p-5">
                            <p class="text-sm text-slate-400 mb-2">Current XLM platform fee</p>
                            <p class="text-3xl font-black text-white">150 BPS</p>
                            <p class="text-sm text-slate-400 mt-2">1.5%</p>
                        </div>
                        <div class="bg-white/[0.03] border border-white/10 rounded-xl p-5">
                            <p class="text-sm text-slate-400 mb-2">Creator amount</p>
                            <p class="text-3xl font-black text-white">98.5%</p>
                            <p class="text-sm text-slate-400 mt-2">before network-related considerations</p>
                        </div>
                        <div class="bg-white/[0.03] border border-white/10 rounded-xl p-5">
                            <p class="text-sm text-slate-400 mb-2">Contract fee cap</p>
                            <p class="text-3xl font-black text-white">300 BPS</p>
                            <p class="text-sm text-slate-400 mt-2">3% maximum</p>
                        </div>
                    </div>
                    <p class="text-sm text-slate-400 leading-relaxed mt-6">
                        USDC and YLX fee schedules are not live product commitments in the current MVP.
                    </p>
                </div>
            </section>

            <section id="security-model" class="scroll-mt-28">
                <div class="flex items-center gap-4 mb-6">
                    <div class="w-10 h-10 rounded-xl bg-emerald-500/10 text-emerald-300 flex items-center justify-center font-black border border-emerald-500/20">10</div>
                    <h2 class="text-3xl font-bold text-white">Security Model</h2>
                </div>
                <div class="glass-panel p-8 md:p-10">
                    <div class="grid md:grid-cols-2 gap-4">
                        @foreach ([
                            'Non-custodial design: the fan wallet authorizes payment from its own account.',
                            'No fan secret is stored by Yolixa.',
                            'The server creates a bounded TipIntent before transaction construction.',
                            'Submitted transaction hashes are normalized and cannot be attached to multiple intents.',
                            'The backend verifies the transaction envelope targets the configured router.',
                            'Verification checks sender, creator, token, amount, and contract tip ID.',
                            'Router receipt and creator stats are read from contract state before confirmation.',
                            'The XLM SAC must be enabled in the router token allowlist.',
                            'Self-tipping is blocked in the UI/backend and contract.',
                            'Contract replay protection rejects duplicate sender plus tip_id.',
                            'Laravel confirmation is idempotent and creates exactly one Tip row per intent.',
                            'Pause control, admin authorization, and the 300 BPS fee cap are tested.'
                        ] as $item)
                            <div class="bg-white/[0.03] border border-white/10 rounded-xl p-4">
                                <p class="text-sm text-slate-300 leading-relaxed">{{ $item }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            <section id="open-source" class="scroll-mt-28">
                <div class="flex items-center gap-4 mb-6">
                    <div class="w-10 h-10 rounded-xl bg-slate-500/10 text-slate-300 flex items-center justify-center font-black border border-slate-500/20">11</div>
                    <h2 class="text-3xl font-bold text-white">Open Source</h2>
                </div>
                <div class="glass-panel p-8 md:p-10 space-y-5 text-slate-300 leading-relaxed">
                    <p>
                        The Soroban contract source is public in the repository and is intended to remain publicly auditable.
                    </p>
                    <p>
                        A root `LICENSE` file is not currently present. Until the repository owner adds an explicit license, Yolixa should not be described as legally open source. The owner should add the selected permissive license before SCF submission and then update repository metadata accordingly.
                    </p>
                </div>
            </section>

            <section id="competition" class="scroll-mt-28">
                <div class="flex items-center gap-4 mb-6">
                    <div class="w-10 h-10 rounded-xl bg-pink-500/10 text-pink-300 flex items-center justify-center font-black border border-pink-500/20">12</div>
                    <h2 class="text-3xl font-bold text-white">Competition & Differentiation</h2>
                </div>
                <div class="glass-panel p-8 md:p-10 space-y-5 text-slate-300 leading-relaxed">
                    <p>
                        Creator-payment products already exist in the Stellar ecosystem. Yolixa should not claim to be the only creator tipping platform on Stellar.
                    </p>
                    <div class="grid md:grid-cols-2 gap-4">
                        @foreach ([
                            'Soroban-first non-custodial payment routing.',
                            'Atomic creator plus platform treasury settlement.',
                            'Reusable routing primitive that can support more than one consumer website.',
                            'On-chain replay/idempotency receipts.',
                            'Creator stats stored and queryable by the router.',
                            'Collaborative `tip_split` capability at contract level.',
                            'Future embeddable SDK, widget, and API surface.',
                            'Infrastructure potential for other creator products.'
                        ] as $item)
                            <div class="bg-white/[0.03] border border-white/10 rounded-xl p-4">
                                <p class="text-sm text-slate-300 leading-relaxed">{{ $item }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            <section id="roadmap" class="scroll-mt-28">
                <div class="flex items-center gap-4 mb-6">
                    <div class="w-10 h-10 rounded-xl bg-blue-500/10 text-blue-300 flex items-center justify-center font-black border border-blue-500/20">13</div>
                    <h2 class="text-3xl font-bold text-white">Roadmap</h2>
                </div>
                <div class="space-y-8">
                    <div class="glass-panel p-6 border-emerald-500/25">
                        <div class="flex flex-wrap items-center gap-3 mb-5">
                            <h3 class="text-xl font-black text-white tracking-wide">BUILT BEFORE SCF</h3>
                            <span class="status-badge status-implemented">Repository Work</span>
                        </div>
                        <div class="grid md:grid-cols-2 gap-3">
                            @foreach ([
                                'Soroban YolixaTipRouter.',
                                'Non-custodial XLM routing.',
                                '1.5% fee split.',
                                'Wallet authentication.',
                                'Freighter integration code path; Testnet browser validation pending.',
                                'Rabet integration code path; Testnet browser validation pending.',
                                'Creator profiles and referral links.',
                                'Server-created TipIntent flow.',
                                'Transaction verification.',
                                'Replay and idempotency protections.',
                                'Contract tests.',
                                'Laravel tests.',
                                'CI workflow.'
                            ] as $item)
                                <div class="bg-emerald-500/[0.04] border border-emerald-500/15 rounded-xl p-4">
                                    <p class="text-sm text-slate-300 leading-relaxed">{{ $item }}</p>
                                </div>
                            @endforeach
                        </div>
                        <p class="text-sm text-amber-200 bg-amber-500/10 border border-amber-500/20 rounded-xl p-4 mt-5">
                            Real Testnet deployment evidence and browser wallet E2E evidence are not listed as completed until a real run is recorded.
                        </p>
                    </div>

                    <div class="glass-panel p-6 border-blue-500/25">
                        <div class="flex flex-wrap items-center gap-3 mb-5">
                            <h3 class="text-xl font-black text-white tracking-wide">SCF-FUNDED FUTURE DEVELOPMENT</h3>
                            <span class="status-badge status-planned">Funding Scope</span>
                        </div>
                        <div class="grid md:grid-cols-2 gap-5">
                            <div class="bg-blue-500/[0.04] border border-blue-500/15 rounded-xl p-5">
                                <h4 class="text-white font-bold mb-3">Product / Protocol Expansion</h4>
                                <ul class="space-y-2 text-sm text-slate-300 leading-relaxed">
                                    <li>USDC Soroban integration.</li>
                                    <li>Application-level `tip_split` integration.</li>
                                    <li>Multi-creator collaborative payouts.</li>
                                    <li>Multi-asset UX.</li>
                                    <li>Embeddable tipping widget.</li>
                                    <li>Reusable Yolixa SDK/API.</li>
                                </ul>
                            </div>
                            <div class="bg-blue-500/[0.04] border border-blue-500/15 rounded-xl p-5">
                                <h4 class="text-white font-bold mb-3">Testnet Growth</h4>
                                <ul class="space-y-2 text-sm text-slate-300 leading-relaxed">
                                    <li>Creator analytics.</li>
                                    <li>Soroban event indexing.</li>
                                    <li>Expanded dashboard.</li>
                                    <li>Public developer documentation.</li>
                                    <li>Monitoring.</li>
                                    <li>Creator beta and real usage metrics.</li>
                                </ul>
                            </div>
                            <div class="bg-blue-500/[0.04] border border-blue-500/15 rounded-xl p-5">
                                <h4 class="text-white font-bold mb-3">Mainnet</h4>
                                <ul class="space-y-2 text-sm text-slate-300 leading-relaxed">
                                    <li>Production security hardening.</li>
                                    <li>Secure admin/governance model.</li>
                                    <li>Validated Mainnet XLM/USDC configuration.</li>
                                    <li>Mainnet router deployment.</li>
                                    <li>Production infrastructure and observability.</li>
                                    <li>Creator launch and measurable Mainnet usage.</li>
                                </ul>
                            </div>
                            <div class="bg-blue-500/[0.04] border border-blue-500/15 rounded-xl p-5">
                                <h4 class="text-white font-bold mb-3">Experimental / Future</h4>
                                <p class="text-sm text-slate-300 leading-relaxed">
                                    YLX may remain an experimental future loyalty/reward concept if retained, but speculative token rewards are not the primary SCF-funded value proposition.
                                </p>
                                <p class="text-sm text-slate-400 leading-relaxed mt-3">
                                    Future wallet work may include additional Stellar wallets, Stellar Wallets Kit or another standardized abstraction, mobile wallet UX, and WalletConnect-compatible wallets.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section id="limitations" class="scroll-mt-28 border-t border-white/10 pt-14">
                <div class="flex items-center gap-4 mb-6">
                    <div class="w-10 h-10 rounded-xl bg-rose-500/10 text-rose-300 flex items-center justify-center font-black border border-rose-500/20">14</div>
                    <h2 class="text-3xl font-bold text-white">Current Limitations</h2>
                </div>
                <div class="glass-panel p-8 md:p-10">
                    <div class="grid md:grid-cols-2 gap-4">
                        @foreach ([
                            'Current network is Stellar Testnet.',
                            'Current Soroban product asset is XLM.',
                            'Freighter or Rabet authorization is manual in the browser.',
                            'Production/Mainnet launch is pending.',
                            'USDC product routing is pending.',
                            'Production YLX rewards are pending.',
                            'Production monitoring is pending.',
                            'Application-level split tipping is pending.'
                        ] as $item)
                            <div class="bg-white/[0.03] border border-white/10 rounded-xl p-4">
                                <p class="text-sm text-slate-300 leading-relaxed">{{ $item }}</p>
                            </div>
                        @endforeach
                    </div>
                    <p class="text-sm text-slate-400 leading-relaxed mt-6">
                        These limitations are intentional funding boundaries. The MVP proves technical capability and a concrete Soroban architecture while leaving meaningful Stellar-related engineering for SCF-funded milestones.
                    </p>
                </div>
            </section>
        </main>
    </div>

    <footer class="border-t border-white/5 bg-[#06090F] py-14 relative z-10 mt-auto">
        <div class="max-w-7xl mx-auto px-6 text-center">
            <div class="w-14 h-14 mx-auto rounded-xl bg-gradient-brand flex items-center justify-center mb-6 shadow-lg shadow-purple-500/10">
                <svg class="w-7 h-7 text-white" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
                </svg>
            </div>
            <p class="text-slate-300 font-medium text-lg mb-4 tracking-wide font-outfit">Yolixa SCF Testnet MVP Whitepaper</p>
            <p class="text-sm text-slate-500">&copy; 2025-2026 Yolixa. Public Testnet evidence pending real deployment.</p>
        </div>
    </footer>

    <script>
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

        document.addEventListener('DOMContentLoaded', () => {
            const sections = document.querySelectorAll('section[id]');
            const tocLinks = document.querySelectorAll('.toc-link');

            const observer = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    if (!entry.isIntersecting) {
                        return;
                    }

                    const targetId = entry.target.getAttribute('id');
                    tocLinks.forEach((link) => {
                        link.classList.toggle('active', link.getAttribute('href') === `#${targetId}`);
                    });
                });
            }, {
                root: null,
                rootMargin: '-10% 0px -80% 0px',
                threshold: 0,
            });

            sections.forEach((section) => observer.observe(section));
        });
    </script>
</body>
</html>
