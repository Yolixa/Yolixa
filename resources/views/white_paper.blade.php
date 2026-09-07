<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="{{ asset('assets/images/favicon-32x32.png') }}">
    <title>Yolixa Whitepaper - Stellar Testnet Creator Tipping</title>
    <script src="{{ asset('assets/js/talwind_cdn.js') }}"></script>
    <style>
        body { background: #080b12; color: #e5e7eb; font-family: Inter, ui-sans-serif, system-ui, sans-serif; }
        .panel { background: rgba(17, 24, 39, .72); border: 1px solid rgba(148, 163, 184, .18); border-radius: 12px; }
        .gradient-text { background: linear-gradient(135deg, #38bdf8, #cb6ce6); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        .tag { display: inline-flex; border: 1px solid rgba(56, 189, 248, .28); color: #7dd3fc; background: rgba(14, 165, 233, .1); border-radius: 999px; padding: .25rem .65rem; font-size: .72rem; font-weight: 700; }
        h2 { scroll-margin-top: 7rem; }
    </style>
</head>
<body>
    <nav class="sticky top-0 z-40 bg-[#080b12]/95 border-b border-white/10 backdrop-blur">
        <div class="max-w-6xl mx-auto px-6 py-4 flex items-center justify-between gap-4">
            <a href="{{ url('/') }}" class="text-2xl font-black gradient-text">Yolixa</a>
            <a href="{{ url('/') }}" class="text-sm text-slate-300 hover:text-white">Back to App</a>
        </div>
    </nav>

    <main class="max-w-6xl mx-auto px-6 py-14">
        <section class="mb-14">
            <span class="tag">Pre-SCF MVP</span>
            <h1 class="text-4xl md:text-6xl font-black mt-5 mb-5 leading-tight">
                Non-custodial creator tipping on <span class="gradient-text">Stellar Testnet</span>
            </h1>
            <p class="text-lg md:text-xl text-slate-300 max-w-3xl leading-relaxed">
                Yolixa lets creators connect a Stellar wallet, create a unique tipping link, and receive native XLM tips directly from supporters. Transactions are signed in the user's browser wallet and verified by Laravel through Horizon before confirmed tips are stored.
            </p>
        </section>

        <section class="grid md:grid-cols-3 gap-4 mb-14">
            <div class="panel p-5"><p class="text-sm text-slate-400">Current network</p><p class="text-2xl font-black">Testnet</p></div>
            <div class="panel p-5"><p class="text-sm text-slate-400">Current asset</p><p class="text-2xl font-black">XLM</p></div>
            <div class="panel p-5"><p class="text-sm text-slate-400">Current custody model</p><p class="text-2xl font-black">Non-custodial</p></div>
        </section>

        <div class="space-y-12">
            <section class="panel p-7">
                <h2 class="text-3xl font-black mb-4">1. Yolixa Overview</h2>
                <p class="text-slate-300 leading-relaxed">Yolixa is a creator micro-tipping platform built with Laravel and Stellar. The current product is intentionally narrow: a creator wallet signs in, creates a public tipping identity, and supporters send XLM from their own wallet directly to the creator's Stellar Testnet address.</p>
            </section>

            <section class="panel p-7">
                <h2 class="text-3xl font-black mb-4">2. Problem</h2>
                <p class="text-slate-300 leading-relaxed">Many creators need small, global payments without custodial payout queues, geographic friction, or high per-payment overhead. Traditional creator monetization tools often work well for cards and subscriptions, but small international tips can still be expensive, slow, or unavailable.</p>
            </section>

            <section class="panel p-7">
                <h2 class="text-3xl font-black mb-4">3. Solution</h2>
                <p class="text-slate-300 leading-relaxed">Yolixa gives each creator a shareable tipping link. Supporters connect a Stellar wallet, approve a native XLM transaction, and receive public transaction proof. Yolixa records only confirmed transactions that match the expected sender, creator, amount, asset, and transaction hash.</p>
            </section>

            <section class="panel p-7">
                <h2 class="text-3xl font-black mb-4">4. Why Stellar</h2>
                <div class="grid md:grid-cols-2 gap-4 text-slate-300 leading-relaxed">
                    <p>Stellar's low transaction cost and fast settlement make it practical for small-value creator tips where traditional payment fees can be disproportionate.</p>
                    <p>Wallet-to-wallet settlement means creators receive payments directly. Yolixa does not need to custody balances or run a withdrawal queue for the current direct payment model.</p>
                    <p>Horizon provides public, verifiable transaction history, allowing Yolixa to verify transaction hashes instead of trusting browser-submitted payment claims.</p>
                    <p>Stellar's asset ecosystem gives Yolixa a clear future path toward stablecoin support, while the current MVP stays focused on native XLM.</p>
                </div>
            </section>

            <section class="panel p-7">
                <h2 class="text-3xl font-black mb-4">5. Target Users</h2>
                <ul class="list-disc pl-5 text-slate-300 space-y-2">
                    <li>Independent creators who want direct fan support.</li>
                    <li>Writers, artists, educators, and open-source maintainers.</li>
                    <li>Small communities that want transparent Testnet payment demos before production use.</li>
                </ul>
            </section>

            <section class="panel p-7">
                <h2 class="text-3xl font-black mb-4">6. Current Product</h2>
                <ul class="list-disc pl-5 text-slate-300 space-y-2">
                    <li>Stellar Testnet native XLM tipping.</li>
                    <li>Freighter wallet authentication and transaction signing as the default supported wallet flow.</li>
                    <li>Rabet integration code exists but should be advertised only after a real browser-wallet validation pass.</li>
                    <li>Creator registration, referral links, public tipping pages, and dashboard tip history.</li>
                    <li>Server-side transaction verification through Horizon before persistence.</li>
                </ul>
            </section>

            <section class="panel p-7">
                <h2 class="text-3xl font-black mb-4">7. User Flow</h2>
                <pre class="overflow-x-auto bg-black/35 border border-white/10 rounded-lg p-5 text-sm text-slate-200">Creator connects wallet
-> signs challenge
-> registers creator identity
-> shares unique tipping link
-> supporter connects a different wallet
-> Laravel builds unsigned XLM payment XDR
-> wallet signs transaction
-> Horizon confirms
-> Laravel verifies
-> tip appears in dashboard</pre>
            </section>

            <section class="panel p-7">
                <h2 class="text-3xl font-black mb-4">8. Technical Architecture</h2>
                <p class="text-slate-300 leading-relaxed">The browser handles wallet connection and signing. Laravel owns sessions, creator records, unsigned XDR construction, Horizon submission, verification, and database persistence. MySQL or SQLite stores users, wallets, and confirmed tip history. Stellar configuration is centralized in Laravel config instead of being hardcoded through services.</p>
            </section>

            <section class="panel p-7">
                <h2 class="text-3xl font-black mb-4">9. Non-Custodial Security Model</h2>
                <p class="text-slate-300 leading-relaxed">Wallet private keys remain inside the user's wallet. Authentication is based on signed random challenges with expiry and single-use cleanup. Creator registration is bound to the authenticated wallet session. Transaction recording does not trust frontend amount, sender, receiver, or status; Horizon verification and database uniqueness determine whether a tip is confirmed.</p>
            </section>

            <section class="panel p-7">
                <h2 class="text-3xl font-black mb-4">10. Current Stellar Integration</h2>
                <p class="text-slate-300 leading-relaxed">The current XDR contains one native XLM payment from supporter to creator. Yolixa does not currently deduct or collect a platform fee on-chain. Any future sustainability fee must be implemented transparently as an explicit Stellar operation or contract route and documented separately.</p>
            </section>

            <section class="panel p-7">
                <h2 class="text-3xl font-black mb-4">11. Business/Sustainability Model</h2>
                <p class="text-slate-300 leading-relaxed">The current MVP proves payment functionality and creator onboarding rather than revenue collection. A future model may add a transparent, user-visible sustainability fee in an atomic Stellar transaction, but Yolixa should not claim current fee revenue until that operation exists in production and is verifiable on-chain.</p>
            </section>

            <section class="panel p-7">
                <h2 class="text-3xl font-black mb-4">12. Ecosystem Impact</h2>
                <p class="text-slate-300 leading-relaxed">Yolixa can bring creator-economy activity to Stellar by making wallet-to-wallet support easy to demonstrate, verify, and repeat. The MVP is a practical entry point for creators and supporters who may later use stablecoins, widgets, APIs, or more advanced Stellar integrations.</p>
            </section>

            <section class="panel p-7">
                <h2 class="text-3xl font-black mb-4">13. Market Differentiation</h2>
                <p class="text-slate-300 leading-relaxed">Yolixa should not claim to be the only creator tipping product. Its current differentiation is a simple Stellar-native creator link, non-custodial wallet flow, and backend-verifiable payment history that can grow into a broader Stellar creator payments layer.</p>
            </section>

            <section class="panel p-7">
                <h2 class="text-3xl font-black mb-4">14. Future Roadmap</h2>
                <div class="space-y-5 text-slate-300 leading-relaxed">
                    <div><h3 class="text-xl font-bold text-white">Phase 1 - Stellar Payment Expansion</h3><p>Future development will expand the proven XLM tipping foundation with production-grade payment infrastructure, asset-aware transaction handling, planned USDC support on Stellar, trustline-aware payment UX where required, a transparent sustainability-fee architecture, stronger creator account controls, and improved payment reliability.</p></div>
                    <div><h3 class="text-xl font-bold text-white">Phase 2 - Creator Rewards & Growth</h3><p>Yolixa plans to introduce a utility-focused creator reward layer, including a future YLX reward mechanism, controlled reward distribution/claim flows, creator campaigns, richer creator analytics, and platform administration tools. Token functionality will only be introduced after the core payment product and reward utility have been validated.</p></div>
                    <div><h3 class="text-xl font-bold text-white">Phase 3 - Stellar Mainnet Launch</h3><p>After successful Testnet development and validation, Yolixa plans to launch the production payment experience on Stellar Mainnet with production configuration, operational monitoring, documentation, security hardening, asset/payment reliability, and measurable on-chain usage.</p></div>
                    <div><h3 class="text-xl font-bold text-white">Long-Term Expansion</h3><p>Longer-term opportunities may include public APIs and SDKs, additional Stellar ecosystem integrations, additional wallet options, creator monetization tools, enterprise integrations, mobile-friendly expansion, carefully evaluated DeFi/liquidity functionality, cross-chain interoperability only where real user demand justifies it, and AI-assisted creator tools only where they improve the actual product. These items are outside the current MVP and outside immediate implementation.</p></div>
                </div>
            </section>

            <section class="panel p-7">
                <h2 class="text-3xl font-black mb-4">15. SCF Development Vision</h2>
                <p class="text-slate-300 leading-relaxed">SCF Build support would fund future work beyond the existing foundation: production-grade Stellar payment expansion, stablecoin support, transparent fee architecture, stronger demo evidence, creator growth tooling, and a measured Mainnet path. Existing MVP work should be presented as the technical foundation, not as work still requiring funding.</p>
            </section>

            <section class="panel p-7">
                <h2 class="text-3xl font-black mb-4">16. Long-Term Vision</h2>
                <p class="text-slate-300 leading-relaxed">Yolixa's long-term goal is to become a trustworthy Stellar-native creator payments layer. The immediate path remains deliberately practical: prove direct payments, document security boundaries, collect real user feedback, and expand only where creator payment utility is clear.</p>
            </section>
        </div>
    </main>
</body>
</html>
