# SCF Submission Draft

## Project Abstract

Yolixa is a non-custodial creator tipping MVP on Stellar Testnet. Creators connect a Stellar wallet, create a unique tipping link, and receive native XLM tips directly from supporters. Supporters sign transactions in their browser wallet, and Yolixa verifies confirmed transactions through Horizon before storing tip history.

## Problem

Small creators need lightweight global payments, but traditional payout systems often introduce geographic limits, high fees, withdrawal delays, account dependency, and poor support for very small payments.

## Solution

Yolixa gives creators a simple Stellar-native tipping page. The current MVP proves the core flow: wallet authentication, creator onboarding, referral links, browser-signed XLM transactions, Horizon submission, backend verification, and dashboard history.

## Target Users

- independent creators,
- open-source maintainers,
- community educators,
- artists and writers,
- small online communities that want low-friction fan support.

## Current Prototype State

Implemented in the repository:

- Laravel application with creator registration and dashboards,
- Stellar Testnet configuration,
- Freighter wallet authentication using signed challenges,
- native XLM tip XDR construction,
- wallet-side transaction signing flow,
- Horizon submission,
- backend transaction verification,
- idempotent confirmed tip recording,
- public referral/profile tipping pages,
- automated tests for critical current-MVP behavior.

Evidence placeholders:

- Public GitHub URL: `[TO BE PROVIDED]`
- Demo URL: `[TO BE PROVIDED]`
- Demo video: `[TO BE PROVIDED]`
- Real Testnet transaction hash: `[TO BE PROVIDED]`
- Founder/team information: `[TO BE PROVIDED]`

## Why Stellar

Stellar is well suited to micro-tipping because it has low transaction costs, fast settlement, public transaction history, native global wallet-to-wallet transfers, and an asset ecosystem that can later support stablecoin payments. Direct Stellar payments let creators receive value without waiting for a custodial withdrawal queue.

## Current Technical Architecture

```text
Browser wallet -> Laravel XDR builder -> Freighter signing -> Horizon submission
-> Laravel Horizon verification -> database tip record -> creator dashboard
```

Yolixa does not store private keys or sign transactions for users.

## Differentiation

Yolixa focuses on direct creator payments, clear referral links, non-custodial wallet control, and backend-verifiable Stellar settlement. The current MVP intentionally avoids speculative token economics and keeps the first product centered on usable payments.

## Ecosystem Contribution

Yolixa can introduce more creators and supporters to Stellar through a simple creator-economy use case. Future expansion can generate visible Testnet and Mainnet payment activity and reusable integration patterns for creator platforms.

## Future On-Chain Growth

Future development can expand from XLM tips into asset-aware payments, USDC support, transparent sustainability fees, public APIs/widgets, and eventually production Mainnet usage.

## Proposed SCF Build Tranche Plan

### Tranche 1 - Payment Expansion / MVP Development

Measurable deliverables:

- productionized Stellar payment architecture,
- planned USDC support design and implementation,
- trustline-aware payment flow where required,
- transparent platform sustainability-fee architecture,
- creator profile/account improvements,
- improved transaction reliability,
- documented public demo with reproducible Testnet evidence.

### Tranche 2 - Testnet Product Expansion

Measurable deliverables:

- full Testnet multi-asset flow,
- creator rewards/YLX utility implementation if validated,
- reward distribution/claim architecture,
- creator growth/analytics functionality,
- operational/admin tooling,
- measurable Testnet user or pilot activity.

### Tranche 3 - Mainnet Launch

Measurable deliverables:

- production Stellar Mainnet deployment,
- production XLM/approved asset payments,
- security and monitoring,
- public documentation,
- Mainnet transaction evidence,
- measurable creator/supporter adoption,
- production release.

## Risks And Mitigations

- Wallet UX complexity: keep Freighter-first MVP flow and document Testnet setup clearly.
- Spoofed transaction claims: verify every recorded tip through Horizon and enforce unique transaction hashes.
- Scope creep: keep USDC, YLX, staking, DeFi, and mainnet launch as future milestones.
- Production security: require a mainnet readiness checklist, monitoring, and review before launch.

## Technical Team Capability

`[ADD FOUNDER/TEAM BACKGROUND, RELEVANT EXPERIENCE, AND MAINTAINER DETAILS]`

## Budget

`[ADD FINAL SCF BUDGET AND TIMELINE]`
