# Future Soroban Payment Router Notes

This file previously described Soroban router integration as the current MVP path. That is no longer the correct pre-SCF positioning.

## Current Status

The current Yolixa MVP uses classic Stellar Testnet XLM payments:

```text
supporter wallet -> creator wallet
```

Laravel builds the unsigned XDR, the browser wallet signs it, Horizon submits/confirms it, and the backend verifies it before storing a confirmed tip.

## Future Soroban Scope

Soroban/router work may be revisited as an SCF-funded milestone after the direct XLM MVP is validated. Future work may include:

- a deployed payment router contract,
- explicit creator plus platform treasury routing,
- contract-level replay protection,
- contract receipts,
- multi-recipient split tipping,
- USDC or other asset support after allowlisting and UX validation.

These items are not current MVP functionality and should not be presented as live until a real Testnet deployment, browser-wallet run, and transaction evidence exist.

## Required Evidence Before Re-Enabling

- deployed router contract ID,
- native XLM SAC or asset contract ID,
- treasury public address,
- deployment transaction hash,
- smoke-test transaction hash,
- browser-wallet E2E demo,
- backend verification evidence,
- updated README and whitepaper language.
