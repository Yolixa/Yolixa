# Yolixa Architecture

Yolixa is a Laravel application for non-custodial creator tipping on Stellar Testnet. The current pre-SCF MVP focuses on native XLM payments signed by the supporter wallet and verified by the backend before a confirmed tip is stored.

## Current Runtime Flow

```text
Creator browser
  -> Laravel wallet challenge
  -> Freighter signs challenge
  -> Laravel session bound to creator public key
  -> Creator profile/referral link

Supporter browser
  -> Public creator tip page
  -> Freighter signs wallet challenge
  -> Laravel builds unsigned XLM payment XDR
  -> Supporter wallet signs transaction
  -> Laravel submits signed XDR to Horizon
  -> Laravel verifies transaction via Horizon
  -> MySQL/SQLite stores confirmed tip
  -> Creator dashboard shows history
```

## Main Components

- Browser UI: Blade templates plus Vite JavaScript for wallet connection and tip submission. Freighter access is centralized in `resources/js/wallet-adapter.js`.
- Laravel routes/controllers: wallet authentication, creator registration/profile, public referral pages, and current XLM tip APIs.
- `StellarConfigurationService`: central Testnet/Horizon/passphrase configuration.
- `StellarService`: XDR construction, Horizon submission, and transaction verification.
- XLM preflight: conservative spendable-balance check using account reserves, liabilities, sponsorship counts, and current fee/reserve data where Horizon provides it.
- `TipService`: idempotent persistence after backend verification.
- Database: users, wallets, tips, wallet types, blockchains, and historical future-scope tables.

## Current/Future Boundary

Current MVP:

- Stellar Testnet only.
- Native XLM only.
- Freighter as the default wallet.
- Browser-wallet signing.
- Backend Horizon verification.
- Direct payment to the creator wallet.

Future scope:

- USDC and custom Stellar assets.
- Trustline-aware UX.
- Soroban payment router. Current classic mode returns disabled responses for `/api/soroban/*`.
- Atomic sustainability-fee operations.
- YLX reward issuance/claims.
- Mainnet launch.
- Advanced analytics, campaigns, staking, DeFi, mobile apps, and cross-chain integrations.
