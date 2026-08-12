# Yolixa

Yolixa is a Laravel creator-tipping MVP built on Stellar. Creators connect a Stellar wallet, publish a referral link, and fans send Testnet XLM tips from Freighter.

## Phase Status

Phase 1 delivered the `YolixaTipRouter` Soroban smart contract, its Rust business logic, replay protection, security checks, contract tests, and contract CI.

Phase 2 integrates that router into the Laravel app on Stellar Testnet. The application creates a server-side `TipIntent`, Freighter signs the fan-source transaction, Laravel submits/records the transaction, and the backend verifies the final on-chain router receipt before marking the tip confirmed.

## Current Soroban Architecture

The Phase 2 Soroban flow is:

```text
fan
 -> YolixaTipRouter
 -> creator net payout
 -> Yolixa treasury platform fee
```

The router does not custody funds. During the `tip(sender, creator, token, amount, tip_id)` call, the contract transfers the creator payout and treasury fee atomically from the fan's authorized Stellar Asset Contract balance.

The backend does not trust browser-provided proof. It verifies the transaction envelope invokes the configured router and then makes read-only contract calls for `tip_exists`, `get_tip`, `get_creator_stats`, `get_fee_bps`, `get_treasury`, `is_paused`, and `is_token_enabled`.

## Classic Mode

Classic payment support remains available only when `YOLIXA_TIP_EXECUTION_MODE=classic` is selected before transaction construction. Yolixa never automatically falls back from a submitted Soroban transaction to a classic payment. Pending, not found, timeout, or temporary RPC states remain safely retryable against the same intent/hash.

## Configuration

Yolixa Phase 2 is Testnet-only.

```env
YOLIXA_NETWORK=testnet
YOLIXA_TIP_EXECUTION_MODE=soroban
SOROBAN_ENABLED=true
SOROBAN_RPC_URL=https://soroban-testnet.stellar.org
SOROBAN_TIP_ROUTER_CONTRACT_ID=
SOROBAN_XLM_TOKEN_CONTRACT_ID=
SOROBAN_TIP_ROUTER_FEE_BPS=150
YOLIXA_MIN_PAYMENT_AMOUNT=0.0000001
YOLIXA_MAX_PAYMENT_AMOUNT=1000
```

Keep real router and SAC IDs in local `.env` or deployment secrets. Never commit Stellar seeds, mnemonics, or private keys.

## Install And Run

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
npm install
npm run build
php artisan serve
```

Node 22+ is required by the current Stellar JS SDK.

## Wallet Test Flow

1. Install Freighter.
2. Switch Freighter to Stellar Testnet.
3. Use different funded Testnet accounts for creator and fan.
4. Authenticate the fan through the wallet challenge.
5. Open the creator referral page and send an XLM tip.
6. Confirm the backend records one `tips` row for the `TipIntent` and stores the Soroban tx hash, router ID, token ID, contract tip ID, receipt, and creator stats.

## Evidence

Phase 2 deployment and validation evidence is tracked in `docs/scf-v2-phase-2.md`.

## Current Limitations

- Mainnet is intentionally disabled.
- Phase 2 supports XLM only.
- Browser/Freighter approval is manual.
- Real Testnet deployment evidence must not include secrets.
- YLX reward automation, USDC routing, analytics, moderation, and production observability remain Phase 3+ candidates.
