# Yolixa

Yolixa is a creator-focused micro-tipping MVP built with Laravel and Stellar. Creators connect a Stellar wallet, generate a public referral/tip link, and receive XLM testnet tips directly from fans through Freighter or Rabet.

## Problem

Creators often lose small payments to platform custody, withdrawal delays, and high fees. Yolixa uses Stellar for fast, low-cost, transparent settlement so fans can support creators with direct wallet-to-wallet micro-tips.

## Why Stellar

- Fast finality suitable for micro-tips.
- Very low transaction fees.
- Public Horizon API for transaction proof and history.
- Mature browser wallet ecosystem through Freighter and Rabet.
- Testnet support for safe reviewer demos.

## Current Working MVP

- Wallet-based fan and creator onboarding.
- Freighter wallet connection.
- Rabet wallet connection when the extension is installed.
- Connected public key saved to `users` and `wallets`.
- Creator registration and dashboard.
- Unique creator referral link at `/r/{code}`.
- Public creator tip page.
- Direct XLM payment XDR built by Laravel with Soneso Stellar SDK.
- Transaction signed by the fan wallet in the browser.
- Signed transaction submitted to Stellar testnet Horizon.
- Backend verification of tx hash, amount, asset, sender, and receiver.
- Tip record saved with tx hash, amount, asset, sender wallet, receiver wallet, network fee, platform fee, and status.
- Creator dashboard with total tips, wallet address, referral link, and transaction history.
- Self-tipping prevention.
- CSRF, validation, duplicate tx hash protection, and clear UI success/error messages.

## Stellar Configuration

Yolixa currently uses Stellar testnet only.

```env
STELLAR_HORIZON=https://horizon-testnet.stellar.org
STELLAR_PASSPHRASE="Test SDF Network ; September 2015"
YOLIXA_FEE_PERCENTAGE=0.015
YOLIXA_SUPPORTED_TIP_ASSETS=XLM
```

No secret keys are required for the current direct-tip MVP. Do not put wallet seed phrases in `.env`.

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

Open the app at the URL printed by `php artisan serve`, usually `http://127.0.0.1:8000`.

## Wallet Setup

1. Install Freighter or Rabet.
2. Switch the wallet to Stellar testnet.
3. Create or import a testnet account.
4. Fund testnet accounts with Friendbot.
5. Use different accounts for creator and fan to verify self-tip prevention.

## Demo Flow For Reviewers

1. Open Yolixa locally.
2. Select Stellar and Freighter or Rabet from the wallet modal.
3. Connect and sign the login challenge.
4. Click `Join as Creator`.
5. Enter creator name and email.
6. Submit registration.
7. Copy the referral link from the dashboard.
8. Open the referral link in another browser profile or after switching to a different wallet account.
9. Connect the fan wallet.
10. Enter an XLM testnet tip amount.
11. Sign the transaction in Freighter or Rabet.
12. Wait for success confirmation.
13. Return to the creator dashboard and verify the transaction history.
14. Open the tx hash on Stellar Expert testnet.

## SCF Reviewer Notes

- Live MVP status: functional local Laravel MVP with real Stellar testnet XLM transactions.
- Stellar transaction proof: dashboard stores and links the testnet tx hash for each confirmed tip.
- Settlement model: fan wallet sends XLM directly to creator wallet; Yolixa records fee metadata for reporting.
- Review setup: seeders create Stellar, Freighter, and Rabet wallet options.

## Current Limitations

- Mainnet is intentionally disabled.
- MVP supports direct XLM tips only.
- Platform fee is recorded for reporting; it is not collected on-chain in the current direct-payment MVP.
- YLX rewards, trustlines, automated payouts, analytics, and admin moderation are roadmap features.
- Wallet extensions must be available in the browser; the app cannot sign transactions itself.

## Roadmap

- Optional on-chain fee split or claimable balance flow.
- USDC support through Stellar assets and trustline checks.
- Creator analytics and exportable transaction reports.
- Public profile customization and verified creator badges.
- Production deployment hardening, observability, and SCF demo video.
