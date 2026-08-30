# Yolixa

Yolixa is a Soroban-first, non-custodial creator monetization and payment-routing platform on Stellar. Fans authorize payments from their own wallets, and the `YolixaTipRouter` atomically routes the creator payout and Yolixa platform fee without Yolixa taking custody of user funds.

## Current Status

This branch, `scf-v2-phase-2-integration`, contains the SCF Build Open Track Testnet MVP candidate.

Implemented in repository code:

- Stellar Testnet configuration.
- Freighter and Rabet wallet authentication with one-time challenges.
- XLM tipping through the `YolixaTipRouter` Soroban contract.
- Server-created `TipIntent` records.
- Non-custodial fan-authorized payment execution.
- Atomic creator payout plus 1.5% treasury fee routing.
- Backend transaction verification against the configured router, token, sender, creator, amount, and contract tip ID.
- On-chain router receipts and creator stats reads.
- Replay/idempotency protection in both contract and Laravel confirmation flow.
- Rust contract tests, Laravel tests, frontend build, and GitHub Actions CI.

Current MVP wallet scope:

- Freighter: current MVP wallet; code integrated for authentication and Soroban XLM signing; Testnet browser validation pending.
- Rabet: current MVP wallet; code integrated for authentication and Soroban XLM signing; Testnet browser validation pending.

Public Testnet deployment and browser E2E evidence are not yet recorded in this repository. See [docs/scf-v2-phase-2.md](docs/scf-v2-phase-2.md).

## Architecture

```text
Fan wallet
    |
    | authorizes Soroban invocation
    v
YolixaTipRouter
    |
    +----> Creator: 98.5% net payout
    |
    +----> Yolixa treasury: 1.5% platform fee
```

The router transfers from the fan's authorized Stellar Asset Contract balance during the same Soroban transaction. Yolixa does not store fan private keys and does not use a platform-controlled wallet to sign fan payments.

The Laravel backend does not trust browser-provided proof alone. Confirmation verifies the final transaction via RPC and reads router state for `tip_exists`, `get_tip`, `get_creator_stats`, `get_fee_bps`, `get_treasury`, `is_paused`, and `is_token_enabled`.

## Assets

Current MVP support:

- `XLM`: implemented for Soroban Testnet tipping.

Future funded scope:

- `USDC`: planned after explicit SAC validation, allowlisting, and product integration.
- `YLX`: experimental/future creator loyalty and reward concept only. Production token distribution and reward settlement are not part of the current Testnet MVP.
- Other Stellar assets: future only after explicit allowlisting and verification work.

## Requirements

- PHP 8.2 with `gmp`, `sodium`, `sqlite3`, and `pdo_sqlite`.
- Composer.
- Node.js 22+ and npm.
- Rust stable with `wasm32v1-none`.
- Stellar CLI 27.x for contract deployment/build verification.
- Freighter or Rabet 1.8.0+ installed in the browser for wallet-signed tips.

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
mkdir -p database
touch database/database.sqlite
php artisan migrate:fresh --seed
npm ci
npm run build
php artisan serve
```

For local Soroban tipping, configure these values in `.env` after deploying the router:

```env
YOLIXA_NETWORK=testnet
YOLIXA_TIP_EXECUTION_MODE=soroban
YOLIXA_PLATFORM_WALLET_PUBLIC=
SOROBAN_ENABLED=true
SOROBAN_RPC_URL=https://soroban-testnet.stellar.org
SOROBAN_TIP_ROUTER_CONTRACT_ID=
SOROBAN_XLM_TOKEN_CONTRACT_ID=
SOROBAN_TIP_ROUTER_FEE_BPS=150
```

Never commit Stellar seeds, mnemonics, private keys, or funded wallet secrets.

## Testing

Application checks:

```bash
composer install --no-interaction --prefer-dist --no-progress
npm ci
npm run build
php artisan test
```

Soroban checks:

```bash
cd soroban
cargo fmt --check
cargo clippy --workspace --all-targets --locked -- -D warnings
cargo test --workspace --locked
stellar contract build --package yolixa-tip-router --locked
```

GitHub Actions runs the same application and Soroban coverage in `.github/workflows/soroban-ci.yml`.

## Testnet Deployment

Use [docs/scf-v2-phase-2.md](docs/scf-v2-phase-2.md) for the reproducible Testnet deployment, router initialization, XLM SAC allowlisting, CLI smoke test, and browser wallet E2E evidence checklist.

Evidence placeholders before SCF submission:

- Router contract ID: `MANUAL ACTION REQUIRED`
- XLM SAC ID: `CDLZFC3SYJYDZT7K67VZ75HPJVIEUVNIXF47ZG2FB2RMQQVU2HHGCYSC`
- Admin public address: `MANUAL ACTION REQUIRED`
- Treasury public address: `MANUAL ACTION REQUIRED`
- Deployment transaction hash: `MANUAL ACTION REQUIRED`
- Smoke-test transaction hash and ledger: `MANUAL ACTION REQUIRED`

## Current Limitations

- Testnet only; Mainnet launch is intentionally pending.
- Soroban product flow supports XLM only.
- Freighter/Rabet approval is manual in the browser.
- Public Testnet contract and E2E transaction evidence must be supplied by a human operator.
- USDC Soroban routing, production YLX rewards, analytics/indexing, moderation, production monitoring, off-ramps, mobile apps, and embeddable SDK/widget work remain future scope.
- Contract-level `tip_split` is implemented and tested; product-level split tipping UI/backend integration remains future scope.
- No root `LICENSE` file exists yet. See [docs/open-source-plan.md](docs/open-source-plan.md).

## SCF-Funded Roadmap Boundary

Completed before SCF: the Testnet MVP architecture, Soroban router, Laravel wallet-signed standard XLM tip integration, verification, idempotency, and automated tests.

Planned for SCF funding:

- Product integration for `tip_split`.
- USDC via an approved Stellar Asset Contract.
- Multi-asset UX.
- Embeddable tipping component, SDK, and API documentation.
- Expanded public Testnet creator beta, analytics/event indexing, monitoring, and E2E evidence.
- Mainnet hardening, production admin/governance plan, deployment, observability, and launch documentation.
- Additional Stellar wallets, Stellar Wallets Kit/standardized wallet abstraction, mobile wallet UX, and WalletConnect-compatible wallets.

Freighter and Rabet are current MVP scope, not future wallet scope.

Yolixa should be evaluated as a technically credible Testnet MVP with a concrete Stellar/Soroban path to Mainnet, not as a completed production platform.

## Repository Metadata Recommendation

Suggested GitHub description:

`Non-custodial creator tipping and payment routing on Stellar, powered by Soroban.`

Suggested topics:

`stellar`, `soroban`, `creator-economy`, `payments`, `micropayments`, `laravel`, `rust`, `web3`
