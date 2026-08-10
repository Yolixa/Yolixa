# YolixaTipRouter

`YolixaTipRouter` is Yolixa's Soroban payment execution contract. It routes an authorized fan payment directly through a Stellar Asset Contract token to creators and to the Yolixa treasury for the platform fee.

This contract is intentionally separate from the older `tip_registry` contract. The registry remains available as a backward-compatible record-only reference while this router becomes the production-oriented payment layer.

## Architecture

The router is non-custodial:

1. The fan invokes `tip` or `tip_split`.
2. The fan address authorizes the Soroban invocation.
3. The router calls the configured Stellar Asset Contract token.
4. The token transfers creator payout directly from the fan to creator recipients.
5. The token transfers the platform fee directly from the fan to the Yolixa treasury.

The router never receives or stores user funds.

## Initialization

```rust
initialize(admin, treasury, fee_bps)
```

Initialization can run only once. The `admin` must authorize it. The contract stores:

- `admin`
- `treasury`
- `fee_bps`
- `paused = false`

`fee_bps` is capped by `MAX_FEE_BPS = 300`, which is 3%.

## Fee Model

Fees use basis points:

```text
platform_fee = amount * fee_bps / 10_000
creator_amount = amount - platform_fee
```

Integer division rounds the fee down. If the fee is zero, the router skips the treasury transfer.

## Token Allowlist

Only admin-enabled token contract addresses can be used:

```rust
set_token(admin, token, enabled)
is_token_enabled(token)
```

This is the guardrail for future XLM and USDC Stellar Asset Contract setup.

## Tipping

```rust
tip(sender, creator, token, amount, tip_id)
```

The sender must authorize the invocation. The router rejects paused state, unsupported tokens, duplicate `tip_id`, self-tips, and non-positive amounts.

## Split Tipping

```rust
tip_split(sender, recipients, token, amount, tip_id)
```

`recipients` is a vector of:

```rust
SplitRecipient {
    recipient: Address,
    bps: u32,
}
```

Allocations must total exactly 10,000 BPS. Recipient count is capped at `MAX_SPLIT_RECIPIENTS = 10`. Duplicate recipients and sender-as-recipient are rejected. The platform fee is deducted first, then creator payout is split by BPS. Any rounding remainder is assigned to the first recipient for deterministic exact-total payout.

## Events

The contract emits typed events:

- `TipEvent` for standard tips.
- `SplitTipEvent` for collaborative payouts, including per-recipient gross and net amounts.

Events carry analytics-friendly payment data without storing large redundant payloads.

## Creator Stats

Compact creator stats are stored on-chain:

```rust
get_creator_stats(creator)
```

Stats include `tip_count`, `gross_received`, and `net_received`.

## Replay State and Receipts

The router stores compact tip receipts by `tip_id`:

```rust
tip_exists(tip_id)
get_tip(tip_id)
```

This prevents duplicate payment execution while keeping persistent storage small.

## Security Assumptions

- Admin keys are controlled by Yolixa governance or deployment operations.
- Only trusted Stellar Asset Contract addresses are enabled.
- Fans authorize payments from their own wallet addresses.
- Token transfers are performed atomically in the same Soroban transaction.
- Private keys and wallet secrets must never be committed or embedded in deployment config.

## Test Commands

From the Soroban workspace:

```bash
cd soroban
cargo fmt
cargo clippy --workspace --all-targets
cargo test --workspace
```

To build deployable Wasm with the Stellar CLI, use the official contract build flow instead of a plain Cargo wasm build.

## Future Laravel Integration

Laravel integration is intentionally out of scope for this phase. A later phase should:

- Store the deployed router contract ID in Laravel config.
- Build Soroban invocations for `tip` and `tip_split`.
- Let connected fan wallets sign the invocation.
- Index router events for dashboard analytics.
- Preserve the existing classic XLM tipping path as fallback until cutover is complete.

## Future XLM/USDC SAC Setup

Deployment should enable only audited/expected Stellar Asset Contract addresses. On testnet this will likely include test XLM and test USDC SAC addresses. On mainnet, production USDC/XLM configuration should be validated before enabling.
