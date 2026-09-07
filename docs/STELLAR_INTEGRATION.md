# Stellar Integration

## Current Implementation

Yolixa currently uses Stellar Testnet native XLM payments.

Environment-driven configuration:

```env
STELLAR_NETWORK=testnet
STELLAR_HORIZON_URL=https://horizon-testnet.stellar.org
STELLAR_NETWORK_PASSPHRASE="Test SDF Network ; September 2015"
YOLIXA_TIP_EXECUTION_MODE=classic
```

The backend builds an unsigned classic Stellar transaction containing one native XLM payment operation:

```text
supporter wallet -> creator wallet
```

Yolixa does not request, transmit, store, or log wallet secret keys. The user signs the XDR inside Freighter, then the signed XDR is submitted to Horizon.

## Verification

Before recording a confirmed tip, the backend verifies:

- the transaction hash exists on configured Horizon,
- the transaction succeeded,
- the transaction source matches the authenticated supporter wallet,
- there is a native XLM payment from supporter to creator,
- the amount matches the requested amount exactly at Stellar's 7-decimal precision,
- the receiver wallet is the creator wallet,
- the transaction hash has not already been recorded.

## Current Asset Support

Only native `XLM` is accepted by current validators. USDC, custom assets, trustlines, and token reward flows are intentionally not active in this MVP.

## Future Architecture

A future SCF-funded payment expansion may introduce an atomic Stellar transaction or Soroban router that includes:

- creator payment,
- transparent Yolixa sustainability-fee operation,
- asset-aware validation,
- USDC support on Stellar,
- trustline-aware UX where required,
- stronger monitoring and production deployment controls.

That architecture is planned, not part of the current live MVP.
