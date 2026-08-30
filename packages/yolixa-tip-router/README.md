# yolixa-tip-router Contract Bindings

Generated TypeScript bindings for the `YolixaTipRouter` Soroban contract.

The Laravel frontend imports this package from `resources/js/soroban-tip.js` to build the browser wallet-signed `tip(sender, creator, token, amount, tip_id)` invocation for Freighter or Rabet. Rabet is current MVP scope; real Testnet browser validation evidence remains pending until a wallet run is recorded.

## Build

From this package directory:

```bash
npm install
npm run build
```

The root application build runs through Vite:

```bash
npm run build
```

## Notes

- These bindings do not contain contract IDs, secret keys, or deployment proof.
- Configure the deployed router and XLM SAC IDs through `.env`.
- Current product integration uses standard XLM tips. Contract-level `tip_split` support is generated here but product-level split tipping remains future scope until application integration is completed.
