# YolixaTipRouter

`YolixaTipRouter` is Yolixa's primary Soroban payment routing contract. It routes an authorized fan payment through a configured Stellar Asset Contract token to the creator and to the Yolixa treasury in one atomic transaction.

This contract is separate from the older `tip_registry` reference contract. The current Laravel Phase 2 standard-tip flow uses `YolixaTipRouter`.

## Status

### Contract Implementation

Implemented and tested.

### Current Laravel Standard-Tip Integration

Implemented in code for XLM on Stellar Testnet. Laravel creates a server-side `TipIntent`, the browser builds a wallet-approved Soroban invocation for Freighter or Rabet, and the backend verifies the resulting transaction and router receipt before recording a confirmed tip. Real browser validation evidence is still pending.

### `tip_split` Contract

Implemented and tested at contract level.

### Product-Level Split-Tipping Integration

Planned future funded scope unless separate application integration evidence is added. The current product UI/backend flow is standard one-creator tipping.

## Non-Custodial Architecture

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

The router never receives or stores user funds. The fan authorizes the `tip` or `tip_split` call, and the router invokes the token contract to transfer funds directly from the fan to the creator recipient(s) and treasury.

No private keys are required by the tipping contract, and no platform-controlled wallet signs fan payments.

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

Fees use deterministic integer basis-point math:

```text
platform_fee = amount * fee_bps / 10_000
creator_amount = amount - platform_fee
```

Integer division rounds the fee down. If the fee is zero, the router skips the treasury transfer. At the current application configuration of 150 BPS, a 1 XLM tip routes 0.9850000 XLM to the creator and 0.0150000 XLM to the treasury before network-related considerations.

## Token Allowlist

Only admin-enabled token contract addresses can be used:

```rust
set_token(admin, token, enabled)
is_token_enabled(token)
```

Phase 2 product support is XLM only. USDC or any other Stellar asset should be enabled only after explicit SAC validation and application-level verification work.

## Standard Tipping

```rust
tip(sender, creator, token, amount, tip_id)
```

The router rejects:

- uninitialized or paused state
- unsupported tokens
- duplicate `sender + tip_id`
- sender tipping themselves
- zero or negative amounts
- payouts that would be invalid after fee calculation
- math overflow

`tip_id` replay protection is scoped by sender. Two different senders may reuse the same numeric `tip_id`; the same sender may not.

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

Allocations must total exactly 10,000 BPS. Recipient count is capped at `MAX_SPLIT_RECIPIENTS = 10`. Duplicate recipients and sender-as-recipient are rejected. The platform fee is deducted first, then the creator payout is split by BPS. Any rounding remainder is assigned to the first recipient for deterministic exact-total payout.

## Receipts and Stats

The router stores compact tip receipts by `sender` and `tip_id`:

```rust
tip_exists(sender, tip_id)
get_tip(sender, tip_id)
```

Creator stats remain queryable on-chain:

```rust
get_creator_stats(creator)
```

Stats include `tip_count`, `gross_received`, and `net_received`.

## Events

The contract emits typed events:

- `TipEvent`
- `SplitTipEvent`
- `FeeUpdatedEvent`
- `TreasuryUpdatedEvent`
- `TokenStatusChangedEvent`
- `PauseStatusChangedEvent`

Events expose analytics-friendly data without storing large redundant payloads.

## Admin Controls

Admin-only functions require the stored admin address to authorize:

- `set_fee`
- `set_treasury`
- `set_token`
- `pause`
- `unpause`

Before Mainnet, Yolixa should define a production admin/governance strategy. The current contract does not include an upgrade function.

## Test Commands

From the Soroban workspace:

```bash
cd soroban
cargo fmt --check
cargo clippy --workspace --all-targets --locked -- -D warnings
cargo test --workspace --locked
stellar contract build --package yolixa-tip-router --locked
```
