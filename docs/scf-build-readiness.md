# SCF Build Readiness

Last updated: 2026-08-27

Use this document as the submission-facing readiness tracker. Status labels are intentionally conservative.

| Requirement | Status | Repository Evidence | Public/Testnet Evidence | Remaining Action |
| --- | --- | --- | --- | --- |
| Product MVP | IMPLEMENTED IN CODE - PUBLIC PROOF PENDING | Laravel creator profiles, Freighter/Rabet wallet auth, Soroban `TipIntent`, XLM tip flow, confirmation service, tests | MANUAL ACTION REQUIRED | Complete a real Testnet deployment and browser wallet E2E run |
| Soroban use | VERIFIED | `soroban/contracts/tip_router/src/lib.rs`, Rust tests, generated TS bindings | MANUAL ACTION REQUIRED | Add deployed router ID and transaction evidence after Testnet deployment |
| Architecture | VERIFIED | README architecture, contract README, `resources/js/soroban-tip.js`, verifier service | MANUAL ACTION REQUIRED | Capture E2E evidence showing fan -> router -> creator/treasury routing |
| Wallet auth | IMPLEMENTED IN CODE - BROWSER VALIDATION PENDING | `WalletController`, `WalletAuthenticationTest` for Freighter-compatible and Rabet/SEP-53-style signatures, invalid signature, expiry/reuse | MANUAL ACTION REQUIRED | Complete real Freighter and Rabet browser authentication tests |
| Smart contract | VERIFIED | `YolixaTipRouter` implements initialize, admin controls, token allowlist, pause, `tip`, `tip_split`, receipts, stats | MANUAL ACTION REQUIRED | Deploy and query public Testnet contract |
| Contract source | VERIFIED | `soroban/contracts/tip_router/src/*` | NOT APPLICABLE | Add root license before presenting repo as legally open source |
| Fee routing | VERIFIED | Contract tests and Laravel verifier confirm 150 BPS split and fee/creator payout checks | MANUAL ACTION REQUIRED | Capture public transaction showing 1.5% routing |
| Test coverage | VERIFIED | `php artisan test`; `cargo test --workspace --locked` | NOT APPLICABLE | Keep final command outputs with submission notes |
| CI | IMPLEMENTED — PUBLIC PROOF PENDING | `.github/workflows/soroban-ci.yml` covers app and Soroban checks | MANUAL ACTION REQUIRED | Confirm GitHub Actions is green on the pushed branch |
| Testnet contract | MANUAL ACTION REQUIRED | Deployment runbook in `docs/scf-v2-phase-2.md` | MANUAL ACTION REQUIRED | Deploy `YolixaTipRouter`, initialize admin/treasury/150 BPS, enable XLM SAC |
| Testnet transaction | MANUAL ACTION REQUIRED | Smoke-test commands in `docs/scf-v2-phase-2.md` | MANUAL ACTION REQUIRED | Execute real smoke tip and record tx hash plus ledger |
| End-to-end demo | MANUAL ACTION REQUIRED | Browser wallet checklist in `docs/scf-v2-phase-2.md` | MANUAL ACTION REQUIRED | Perform a real Freighter or Rabet Testnet tip and record proof |
| Wallet test matrix | IMPLEMENTED IN CODE - BROWSER VALIDATION PENDING | Matrix in `docs/scf-v2-phase-2.md`; Freighter and Rabet are current MVP scope | MANUAL ACTION REQUIRED | Fill real browser evidence for both wallets before marking verified |
| Security | VERIFIED | Sender auth, self-tip blocking, allowlist, fee cap, pause, admin auth, replay protection, verifier mismatch tests | MANUAL ACTION REQUIRED | Add public proof and review production admin plan before Mainnet |
| Open-source plan | MANUAL ACTION REQUIRED | `docs/open-source-plan.md` | NOT APPLICABLE | Repository owner must add a root `LICENSE` or update metadata before submission |
| User validation | MANUAL ACTION REQUIRED | `docs/user-validation-template.md` | MANUAL ACTION REQUIRED | Record real creator tests only after they happen |
| Competition/differentiation | VERIFIED | README and whitepaper avoid exclusivity claims and focus on Soroban-first routing primitive | NOT APPLICABLE | Keep claims defensible in SCF form |
| Future roadmap | VERIFIED | README, whitepaper, and readiness docs separate completed work from SCF-funded scope | NOT APPLICABLE | Keep future items out of completed-feature claims |
| Mainnet plan | PLANNED FOR SCF | Mainnet intentionally disabled in config and documented as future hardening/deployment scope | MANUAL ACTION REQUIRED | Complete admin/governance, asset validation, observability, and Mainnet deployment after funding |
