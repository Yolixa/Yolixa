# SCF V2 Phase 2 Evidence

Date: 2026-08-12

## Scope

Phase 2 integrates the Phase 1 `YolixaTipRouter` Soroban contract into the Laravel tipping flow. The intended on-chain path is:

```text
fan -> YolixaTipRouter -> creator net payout + Yolixa treasury platform fee
```

The router does not custody funds; it transfers the creator payout and platform fee atomically during the fan-authorized contract invocation.

## Public Deployment Evidence

Status: partially hardened locally; deployment remains blocked in this local workspace.

- Network: Stellar Testnet
- Router contract ID: not available
- Native XLM SAC contract ID: `CDLZFC3SYJYDZT7K67VZ75HPJVIEUVNIXF47ZG2FB2RMQQVU2HHGCYSC`
- Admin public address: not available
- Treasury public address: not available
- Fee BPS: expected `150`, not verified on-chain
- Paused status: not verified on-chain
- XLM allowlist status: not verified on-chain

Local blockers:

- `stellar` CLI is not installed on PATH.
- `rustc` and `cargo` are not installed on PATH.
- `winget` is present but inaccessible: `The file cannot be accessed by the system`.
- This Codex session cannot manually approve Freighter browser prompts.
- No funded local Testnet identities were available in the workspace for deployment or smoke testing.

No deployment, initialization, smoke-test, or Freighter evidence is fabricated.

The native XLM SAC address above was derived deterministically with the current Stellar JS SDK:

```bash
npx -y node@22 -e "import { Asset, Networks } from '@stellar/stellar-sdk'; console.log(Asset.native().contractId(Networks.TESTNET));"
```

The Stellar CLI cross-check command remains:

```bash
stellar contract id asset --network testnet --asset native
```

## Commands To Run After Tooling Is Available

Check current CLI syntax before running state-changing commands:

```bash
stellar --help
stellar contract --help
stellar contract invoke --help
stellar contract id asset --help
```

Contract validation and build:

```bash
cd soroban
cargo fmt --check
cargo clippy --workspace --all-targets --locked -- -D warnings
cargo test --workspace --locked
stellar contract build --package yolixa-tip-router --locked
```

Derive native XLM SAC:

```bash
stellar contract id asset --network testnet --asset native
```

Deployment command: pending exact current CLI syntax from `stellar contract deploy --help`.

Initialization command: pending exact current CLI syntax from `stellar contract invoke --help`.

Set-token command: pending exact current CLI syntax from `stellar contract invoke --help`.

Read checks required after initialization:

```text
get_admin()
get_treasury()
get_fee_bps()
is_paused()
is_token_enabled(real_xlm_sac)
```

## CLI Smoke Test Evidence

Status: not completed.

Required public values after completion:

- Transaction hash: not available
- Ledger: not available
- Sender public address: not available
- Creator public address: not available
- Treasury public address: not available
- Router contract ID: not available
- Native XLM SAC ID: not available
- Tip ID: not available
- `tip_exists(sender, tip_id)`: not available
- `get_tip(sender, tip_id)`: not available
- `get_creator_stats(creator)`: not available

For a 1 XLM tip at 150 BPS, the expected split is `150000` stroops platform fee and `9850000` stroops creator payout, but final evidence must come from the deployed contract result.

## Browser/Freighter Evidence

Status: not completed.

Manual Freighter approval is required. Required public values after completion:

- Browser/Freighter tx hash: not available
- Laravel `TipIntent` status: not available
- Exactly-one `tips` row proof: not available
- Idempotent retry proof for `/api/soroban/tip/confirm`: not available

## Automated Test Results

Local results from this workspace:

```text
php artisan test
Tests: 50 passed (165 assertions)
Duration: 1.72s
```

Generated bindings:

```text
packages/yolixa-tip-router/
```

Generated with:

```bash
npx -y node@22 node_modules/@stellar/stellar-sdk/bin/stellar-js generate --wasm soroban/target/wasm32v1-none/release/yolixa_tip_router.wasm --output-dir packages/yolixa-tip-router --contract-name yolixa-tip-router --overwrite
```

Frontend build:

```text
npx -y node@22 "$(npm root -g)/npm/bin/npm-cli.js" run build
success, 261 modules transformed, built in 2.76s
```

Dependency validation:

```text
composer install
failed: soneso/stellar-php-sdk 1.12.0 requires ext-gmp, which is missing locally.
```

```text
npx -y node@22 "$(npm root -g)/npm/bin/npm-cli.js" ci
success, 271 packages installed/audited.
```

Local Rust/Soroban validation:

```text
cargo fmt --check
failed: cargo is not installed on PATH.

cargo clippy --workspace --all-targets --locked -- -D warnings
failed: cargo is not installed on PATH.

cargo test --workspace --locked
failed: cargo is not installed on PATH.

stellar contract build --package yolixa-tip-router --locked
failed: stellar is not installed on PATH.
```

## Verification Architecture

The backend verifier proves:

- RPC transaction status is final `SUCCESS`.
- The transaction envelope invokes the configured router.
- The function is `tip`.
- Sender, creator, configured XLM SAC token, amount, and contract tip ID match the server-created `TipIntent`.
- Read-only router calls prove `tip_exists`, `get_tip`, `get_creator_stats`, `get_fee_bps`, `get_treasury`, `is_paused`, and `is_token_enabled`.
- Current event metadata is parsed when present, but storage-key reconstruction from `getTxChangesAfter()` is not required.

## Replay And Idempotency

The contract replay key is `sender + contract_tip_id`. The Laravel confirmation path stores a submitted intent safely, treats pending/not-found transaction states as retryable, and records one `tips` row per `TipIntent`.

## No Automatic Fallback

After a Soroban transaction is submitted, Yolixa does not create a classic payment for timeout, unknown, pending, not-found, or temporary RPC states. Classic mode is only available when selected before transaction construction.

## Phase 3 Candidates

- Production deployment automation and hosted observability.
- Mainnet readiness gates.
- Additional Stellar assets after explicit allowlisting.
- Creator analytics and export.
- Moderation/admin tooling.
