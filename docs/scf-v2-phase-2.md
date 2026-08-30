# SCF V2 Phase 2 Evidence

Last updated: 2026-08-27

This document separates implemented repository evidence from public Testnet evidence. Do not fill in contract IDs, addresses, transaction hashes, ledgers, creator names, or usage claims unless they come from a real deployment or a real browser wallet run.

## Scope

Phase 2 integrates the `YolixaTipRouter` Soroban contract into the Laravel creator tipping flow on Stellar Testnet.

```text
Fan wallet
    |
    | authorizes Soroban invocation
    v
YolixaTipRouter
    |
    +----> Creator: net payout
    |
    +----> Yolixa treasury: platform fee
```

The router is non-custodial. Yolixa does not hold fan funds and does not use a platform signer for fan payments.

## Implemented Repository Evidence

- Contract source: `soroban/contracts/tip_router/src/lib.rs`
- Contract tests: `soroban/contracts/tip_router/src/test.rs`
- Laravel intent and confirmation service: `app/Services/SorobanTipRouterService.php`
- On-chain evidence verifier: `app/Services/SorobanTransactionVerifier.php`
- Freighter/Rabet transaction client: `resources/js/soroban-tip.js`
- Application tests: `tests/Feature/*Soroban*`, `tests/Feature/WalletAuthenticationTest.php`, and Soroban verifier unit tests
- CI: `.github/workflows/soroban-ci.yml`

Current local toolchain observed in this workspace:

```text
PHP: 8.2.28
Node: 18.8.0 locally; GitHub Actions uses Node 22 and package.json requires Node 22+
npm: 8.18.0
Cargo: 1.97.1
Stellar CLI: 27.1.0
```

Use Node 22+ for reproducible local frontend work even though this workspace's existing Node 18 installation can currently build the bundle.

## Current Public Testnet Evidence

Status: `MANUAL ACTION REQUIRED`

No real public router deployment, initialization transaction, smoke-test tip transaction, ledger number, or browser wallet E2E evidence is recorded yet.

Known public value derived from the Stellar Asset Contract ID algorithm:

- Native Testnet XLM SAC ID: `CDLZFC3SYJYDZT7K67VZ75HPJVIEUVNIXF47ZG2FB2RMQQVU2HHGCYSC`

Validated command:

```bash
stellar contract id asset --rpc-url https://soroban-testnet.stellar.org --network-passphrase "Test SDF Network ; September 2015" --asset native
```

Local deployment blockers in this workspace:

- `stellar keys ls` returned no configured identities.
- No funded Testnet admin, treasury, fan, or creator identities were available locally.
- This Codex session cannot approve Freighter or Rabet browser prompts.

## Current Stellar CLI Syntax References

The commands below were checked against local Stellar CLI 27.1.0 help and the current Stellar CLI documentation.

- Stellar CLI manual: https://developers.stellar.org/docs/tools/cli/stellar-cli
- Stellar Asset Contract deployment/id docs: https://developers.stellar.org/docs/tools/cli/cookbook/deploy-stellar-asset-contract

## Browser Wallet API Notes

Rabet is current MVP scope. The browser code expects modern Rabet behavior:

- `rabet.connect()` returns the active public key as `publicKey`, `address`, or a string response.
- `rabet.signMessage(challenge)` is required for wallet ownership authentication.
- `rabet.getNetwork()` is required so Yolixa can reject non-Testnet wallets before authentication or tip signing.
- `rabet.sign(preparedTransactionXdr, 'testnet')` returns the signed XDR as `xdr` or another normalized signed-XDR field.

Older Rabet versions without these APIs receive user-facing upgrade errors. Yolixa never requests or handles a Rabet private key.

## Reproducible Testnet Deployment Runbook

Use a clean terminal. Do not commit secrets, seed phrases, private keys, or local CLI config.

### 1. Check Tooling

```bash
stellar version
rustc --version
cargo --version
node --version
npm --version
php -v
composer --version
```

Optional explicit network alias:

```bash
stellar network add yolixa-testnet \
  --rpc-url https://soroban-testnet.stellar.org \
  --network-passphrase "Test SDF Network ; September 2015"
```

### 2. Build and Verify the Contract

```bash
cd soroban
cargo fmt --check
cargo clippy --workspace --all-targets --locked -- -D warnings
cargo test --workspace --locked
stellar contract build --package yolixa-tip-router --locked
```

Expected WASM path:

```text
soroban/target/wasm32v1-none/release/yolixa_tip_router.wasm
```

### 3. Create Separate Testnet Identities

Use separate identities for admin, treasury, fan, and creator.

```bash
stellar keys generate yolixa-admin \
  --fund \
  --rpc-url https://soroban-testnet.stellar.org \
  --network-passphrase "Test SDF Network ; September 2015"

stellar keys generate yolixa-treasury \
  --fund \
  --rpc-url https://soroban-testnet.stellar.org \
  --network-passphrase "Test SDF Network ; September 2015"

stellar keys generate yolixa-smoke-fan \
  --fund \
  --rpc-url https://soroban-testnet.stellar.org \
  --network-passphrase "Test SDF Network ; September 2015"

stellar keys generate yolixa-smoke-creator \
  --fund \
  --rpc-url https://soroban-testnet.stellar.org \
  --network-passphrase "Test SDF Network ; September 2015"
```

Record public addresses only:

```bash
stellar keys public-key yolixa-admin
stellar keys public-key yolixa-treasury
stellar keys public-key yolixa-smoke-fan
stellar keys public-key yolixa-smoke-creator
```

### 4. Deploy `YolixaTipRouter`

```bash
ROUTER_ID=$(stellar contract deploy \
  --wasm target/wasm32v1-none/release/yolixa_tip_router.wasm \
  --source-account yolixa-admin \
  --rpc-url https://soroban-testnet.stellar.org \
  --network-passphrase "Test SDF Network ; September 2015" \
  --alias yolixa-tip-router-testnet)

echo "$ROUTER_ID"
```

If your shell does not support command substitution, run the deploy command and copy the returned public C-address into `ROUTER_ID`.

### 5. Initialize the Router

```bash
TREASURY_ADDRESS=$(stellar keys public-key yolixa-treasury)

stellar contract invoke \
  --id "$ROUTER_ID" \
  --source-account yolixa-admin \
  --rpc-url https://soroban-testnet.stellar.org \
  --network-passphrase "Test SDF Network ; September 2015" \
  -- initialize \
  --admin yolixa-admin \
  --treasury "$TREASURY_ADDRESS" \
  --fee_bps 150
```

Record the transaction hash and ledger from the command output or with `stellar tx fetch`.

### 6. Derive and Enable Native XLM SAC

```bash
XLM_SAC_ID=$(stellar contract id asset \
  --rpc-url https://soroban-testnet.stellar.org \
  --network-passphrase "Test SDF Network ; September 2015" \
  --asset native)

echo "$XLM_SAC_ID"
```

```bash
stellar contract invoke \
  --id "$ROUTER_ID" \
  --source-account yolixa-admin \
  --rpc-url https://soroban-testnet.stellar.org \
  --network-passphrase "Test SDF Network ; September 2015" \
  -- set_token \
  --admin yolixa-admin \
  --token "$XLM_SAC_ID" \
  --enabled true
```

### 7. Query Router Configuration

Use `--send no` for read-only simulation.

```bash
stellar contract invoke --send no --id "$ROUTER_ID" --source-account yolixa-admin --rpc-url https://soroban-testnet.stellar.org --network-passphrase "Test SDF Network ; September 2015" -- get_admin
stellar contract invoke --send no --id "$ROUTER_ID" --source-account yolixa-admin --rpc-url https://soroban-testnet.stellar.org --network-passphrase "Test SDF Network ; September 2015" -- get_treasury
stellar contract invoke --send no --id "$ROUTER_ID" --source-account yolixa-admin --rpc-url https://soroban-testnet.stellar.org --network-passphrase "Test SDF Network ; September 2015" -- get_fee_bps
stellar contract invoke --send no --id "$ROUTER_ID" --source-account yolixa-admin --rpc-url https://soroban-testnet.stellar.org --network-passphrase "Test SDF Network ; September 2015" -- is_paused
stellar contract invoke --send no --id "$ROUTER_ID" --source-account yolixa-admin --rpc-url https://soroban-testnet.stellar.org --network-passphrase "Test SDF Network ; September 2015" -- is_token_enabled --token "$XLM_SAC_ID"
```

Expected values:

- `get_admin`: admin public G-address
- `get_treasury`: treasury public G-address
- `get_fee_bps`: `150`
- `is_paused`: `false`
- `is_token_enabled`: `true`

### 8. CLI Smoke-Test Tip

Use a unique `tip_id` for each smoke test. Amounts are in stroops for XLM SAC calls.

```bash
FAN_ADDRESS=$(stellar keys public-key yolixa-smoke-fan)
CREATOR_ADDRESS=$(stellar keys public-key yolixa-smoke-creator)
TIP_ID=$(date +%s)

stellar contract invoke \
  --id "$ROUTER_ID" \
  --source-account yolixa-smoke-fan \
  --rpc-url https://soroban-testnet.stellar.org \
  --network-passphrase "Test SDF Network ; September 2015" \
  -- tip \
  --sender "$FAN_ADDRESS" \
  --creator "$CREATOR_ADDRESS" \
  --token "$XLM_SAC_ID" \
  --amount 10000000 \
  --tip_id "$TIP_ID"
```

For a 1 XLM tip at 150 BPS, expected contract split:

- Gross: `10000000` stroops / `1.0000000` XLM
- Platform fee: `150000` stroops / `0.0150000` XLM
- Creator payout: `9850000` stroops / `0.9850000` XLM

### 9. Query Smoke-Test Receipt and Stats

```bash
stellar contract invoke --send no --id "$ROUTER_ID" --source-account yolixa-admin --rpc-url https://soroban-testnet.stellar.org --network-passphrase "Test SDF Network ; September 2015" -- tip_exists --sender "$FAN_ADDRESS" --tip_id "$TIP_ID"
stellar contract invoke --send no --id "$ROUTER_ID" --source-account yolixa-admin --rpc-url https://soroban-testnet.stellar.org --network-passphrase "Test SDF Network ; September 2015" -- get_tip --sender "$FAN_ADDRESS" --tip_id "$TIP_ID"
stellar contract invoke --send no --id "$ROUTER_ID" --source-account yolixa-admin --rpc-url https://soroban-testnet.stellar.org --network-passphrase "Test SDF Network ; September 2015" -- get_creator_stats --creator "$CREATOR_ADDRESS"
```

Fetch transaction details for public evidence:

```bash
stellar tx fetch --hash "<SMOKE_TEST_TX_HASH>" --output json-formatted --rpc-url https://soroban-testnet.stellar.org --network-passphrase "Test SDF Network ; September 2015"
stellar tx fetch events --hash "<SMOKE_TEST_TX_HASH>" --output json-formatted --rpc-url https://soroban-testnet.stellar.org --network-passphrase "Test SDF Network ; September 2015"
```

## Laravel Environment After Deployment

Set these in local `.env` or deployment secrets:

```env
YOLIXA_NETWORK=testnet
YOLIXA_TIP_EXECUTION_MODE=soroban
YOLIXA_PLATFORM_WALLET_PUBLIC=<TREASURY_PUBLIC_G_ADDRESS>
SOROBAN_ENABLED=true
SOROBAN_RPC_URL=https://soroban-testnet.stellar.org
SOROBAN_TIP_ROUTER_CONTRACT_ID=<ROUTER_ID>
SOROBAN_XLM_TOKEN_CONTRACT_ID=<XLM_SAC_ID>
SOROBAN_TIP_ROUTER_FEE_BPS=150
SOROBAN_VERIFIER_SOURCE_ACCOUNT=<FUNDED_PUBLIC_G_ADDRESS>
```

Run:

```bash
php artisan config:clear
php artisan migrate:fresh --seed
npm run build
php artisan test
```

## Browser Wallet E2E Checklist

Status: `MANUAL ACTION REQUIRED`

1. Use a funded Testnet creator wallet.
2. Use a different funded Testnet fan wallet.
3. Authenticate the fan using the Yolixa wallet challenge.
4. Open the creator referral/profile page.
5. Enter an XLM tip.
6. Create a server-side `TipIntent`.
7. Build the Soroban transaction in the browser.
8. Approve the transaction in Freighter or Rabet.
9. Submit it to Stellar Testnet.
10. Persist the tx hash against the intent with `/api/soroban/tip/submitted`.
11. Verify final successful transaction status through RPC.
12. Verify the invocation targets the configured `YolixaTipRouter`.
13. Verify sender, creator, XLM SAC, amount, and `contract_tip_id`.
14. Verify router receipt with `get_tip`.
15. Verify creator stats with `get_creator_stats`.
16. Mark the intent confirmed through `/api/soroban/tip/confirm`.
17. Confirm exactly one `tips` DB row exists for the intent.
18. Retry confirmation and prove it does not create another payment or `tips` row.

For a 1 XLM tip at 150 BPS, expected application-level split:

- Gross: `1.0000000` XLM
- Platform fee: `0.0150000` XLM
- Creator payout: `0.9850000` XLM

Actual final evidence must come from the real on-chain transaction.

## Wallet Test Matrix

Do not mark either wallet `VERIFIED` until a real Testnet browser run is completed and recorded.

| Capability | Freighter | Rabet |
| --- | --- | --- |
| Connect | Required; code path present; browser proof pending | Required; code path present; browser proof pending |
| Wallet ownership auth | Required; automated backend coverage; browser proof pending | Required; automated backend coverage for SEP-53-style signature; browser proof pending |
| Testnet validation | Required before signing | Required before signing; Rabet 1.8.0+ `getNetwork()` required |
| Soroban XLM tip signing | Required; code path present; browser proof pending | Required; code path present through `rabet.sign(xdr, testnet)`; browser proof pending |
| Wrong account rejection | Required; code path present | Required; code path present |
| Self-tip rejection | Required; backend/UI/contract coverage | Required; backend/UI/contract coverage |
| User-rejected signature handling | Required; code path present | Required; code path present |
| Successful Testnet transaction | MANUAL ACTION REQUIRED | MANUAL ACTION REQUIRED |
| Backend on-chain verification | Required; automated service coverage; browser proof pending | Required; automated service coverage; browser proof pending |
| Idempotent confirmation | Required; automated coverage; browser proof pending | Required; automated coverage; browser proof pending |

## Public Deployment Evidence Template

Fill this only after a real deployment.

| Field | Value |
| --- | --- |
| Network | Stellar Testnet |
| Router contract ID | `MANUAL ACTION REQUIRED` |
| XLM SAC ID | `CDLZFC3SYJYDZT7K67VZ75HPJVIEUVNIXF47ZG2FB2RMQQVU2HHGCYSC` |
| Admin public address | `MANUAL ACTION REQUIRED` |
| Treasury public address | `MANUAL ACTION REQUIRED` |
| Router WASM hash | `51e6fb33206894428e67afc01691e47823fd9867b35bbea935dc9edee8759d32` |
| Deploy transaction hash | `MANUAL ACTION REQUIRED` |
| Deploy ledger | `MANUAL ACTION REQUIRED` |
| Initialize transaction hash | `MANUAL ACTION REQUIRED` |
| Initialize ledger | `MANUAL ACTION REQUIRED` |
| Set-token transaction hash | `MANUAL ACTION REQUIRED` |
| Set-token ledger | `MANUAL ACTION REQUIRED` |
| Smoke-test tip ID | `MANUAL ACTION REQUIRED` |
| Smoke-test transaction hash | `MANUAL ACTION REQUIRED` |
| Smoke-test ledger | `MANUAL ACTION REQUIRED` |
| `get_admin` result | `MANUAL ACTION REQUIRED` |
| `get_treasury` result | `MANUAL ACTION REQUIRED` |
| `get_fee_bps` result | `MANUAL ACTION REQUIRED` |
| `is_paused` result | `MANUAL ACTION REQUIRED` |
| `is_token_enabled(XLM)` result | `MANUAL ACTION REQUIRED` |
| `tip_exists` result | `MANUAL ACTION REQUIRED` |
| `get_tip` result | `MANUAL ACTION REQUIRED` |
| `get_creator_stats` result | `MANUAL ACTION REQUIRED` |

## Browser Wallet E2E Evidence Template

Fill this only after a real browser run.

| Field | Value |
| --- | --- |
| Test date | `MANUAL ACTION REQUIRED` |
| App URL | `MANUAL ACTION REQUIRED` |
| Creator public address | `MANUAL ACTION REQUIRED` |
| Fan public address | `MANUAL ACTION REQUIRED` |
| TipIntent ID | `MANUAL ACTION REQUIRED` |
| Contract tip ID | `MANUAL ACTION REQUIRED` |
| Amount | `MANUAL ACTION REQUIRED` |
| Wallet used | `MANUAL ACTION REQUIRED` |
| Wallet network | `MANUAL ACTION REQUIRED` |
| Browser transaction hash | `MANUAL ACTION REQUIRED` |
| Ledger | `MANUAL ACTION REQUIRED` |
| Router contract ID verified | `MANUAL ACTION REQUIRED` |
| XLM SAC ID verified | `MANUAL ACTION REQUIRED` |
| Sender/creator/amount/tip ID verified | `MANUAL ACTION REQUIRED` |
| Router receipt verified | `MANUAL ACTION REQUIRED` |
| Creator stats verified | `MANUAL ACTION REQUIRED` |
| TipIntent confirmed at | `MANUAL ACTION REQUIRED` |
| Tip DB row count for intent | `MANUAL ACTION REQUIRED` |
| Idempotent retry result | `MANUAL ACTION REQUIRED` |

## Automated Test Commands

CI-relevant commands:

```bash
composer install --no-interaction --prefer-dist --no-progress
npm ci
npm run build
php artisan test

cd soroban
cargo fmt --check
cargo clippy --workspace --all-targets --locked -- -D warnings
cargo test --workspace --locked
stellar contract build --package yolixa-tip-router --locked
```

Latest exact local test results are reported in the final implementation report for this SCF readiness pass.
