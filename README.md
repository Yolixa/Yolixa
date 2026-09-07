# Yolixa

Yolixa is a non-custodial creator micro-tipping MVP built with Laravel and Stellar. The current pre-SCF product runs on Stellar Testnet and supports direct native XLM tips from supporters to creators.

Creators connect a Stellar wallet, create a unique tipping link, and receive XLM directly in their wallet. Supporters sign transactions in their browser wallet. Laravel submits signed transactions to Horizon and verifies them before storing confirmed tips.

## Current Scope

Implemented current MVP:

- Stellar Testnet configuration.
- Native XLM tipping only.
- Freighter wallet connection as the default supported wallet.
- Signed wallet challenge authentication.
- Creator registration bound to the authenticated wallet.
- Unique referral/profile tipping links.
- Public creator tipping page.
- Unsigned XLM XDR construction.
- Browser-wallet transaction signing.
- Horizon submission.
- Backend Horizon verification before tip persistence.
- Duplicate transaction prevention through validation and database uniqueness.
- Creator dashboard with paginated tip history.
- Basic admin dashboard with real current-MVP fields.

Not current scope:

- USDC payments.
- Custom Stellar assets or trustlines.
- YLX token issuance, rewards, or claim flows.
- Platform fee collection.
- Soroban router payments.
- Staking, liquidity pools, DeFi, cross-chain integrations.
- Mainnet launch.
- Advanced creator analytics or moderation.

## Current Stellar Integration

The current classic XLM flow is:

```text
Supporter wallet
-> Laravel builds unsigned XLM payment XDR
-> Freighter signs in browser
-> Laravel submits signed XDR to Horizon
-> Laravel verifies transaction source, receiver, asset, amount, and success
-> confirmed tip is stored
```

The generated XDR contains one native XLM payment operation from the supporter to the creator. Yolixa does not request, transmit, store, or log private keys.

## Architecture Summary

- `routes/web.php`: public pages, wallet auth, creator routes, current tip APIs.
- `app/Http/Controllers/WalletController.php`: wallet challenge and session auth.
- `app/Http/Controllers/CreatorController.php`: creator registration, profile, dashboard.
- `app/Http/Controllers/TipController.php`: preview, XDR build, submit, record.
- `app/Services/StellarConfigurationService.php`: network, Horizon, passphrase, explorer URLs.
- `app/Services/StellarService.php`: XDR construction, Horizon submission, transaction verification.
- `app/Services/TipService.php`: idempotent confirmed-tip storage.
- `resources/views`: Blade UI.
- `docs`: architecture, security, API, demo, and SCF draft documentation.
- `soroban`: future/experimental contract work, not the current default MVP payment path.

## Requirements

- PHP 8.2 or newer.
- Composer.
- Node.js 22 or newer and npm.
- SQLite for local setup, or MySQL/MariaDB.
- Freighter browser wallet set to Stellar Testnet.

## Local Setup

```bash
composer install
npm install
copy .env.example .env
php artisan key:generate
```

For SQLite on Windows PowerShell:

```powershell
New-Item -ItemType Directory -Force database
New-Item -ItemType File -Force database/database.sqlite
```

Set the current MVP environment values:

```env
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite
STELLAR_NETWORK=testnet
STELLAR_HORIZON_URL=https://horizon-testnet.stellar.org
STELLAR_NETWORK_PASSPHRASE="Test SDF Network ; September 2015"
YOLIXA_TIP_EXECUTION_MODE=classic
YOLIXA_ENABLED_WALLETS=freighter
SOROBAN_ENABLED=false
```

Run database setup:

```bash
php artisan migrate --seed
```

Build frontend assets:

```bash
npm run build
```

Run the app:

```bash
php artisan serve
```

For local frontend development:

```bash
npm run dev
```

## Demo Flow

1. Open the homepage.
2. Connect a creator Freighter wallet on Stellar Testnet.
3. Register as a creator.
4. Copy the referral/tip URL from the dashboard.
5. Open the tip URL in a supporter session.
6. Connect a different funded Freighter Testnet wallet.
7. Enter an XLM amount.
8. Approve the transaction in Freighter.
9. Wait for Horizon confirmation.
10. Show the transaction hash/explorer link.
11. Return to the creator dashboard and show the confirmed tip.

Do not use the same wallet for creator and supporter. Self-tipping is blocked.

## Security Notes

- Private keys remain in the user's wallet.
- Wallet login requires signed challenge proof.
- Challenges expire and are single-use.
- Creator registration must match the authenticated wallet.
- Mutable tip endpoints require an authenticated wallet session.
- The backend verifies every recorded transaction through Horizon.
- `tips.tx_hash` is unique.
- Admin routes use role middleware.
- No secrets belong in `.env.example`, docs, tests, or source code.

## Documentation

- [Architecture](docs/ARCHITECTURE.md)
- [Stellar Integration](docs/STELLAR_INTEGRATION.md)
- [Security Model](docs/SECURITY_MODEL.md)
- [API](docs/API.md)
- [Local Setup](docs/LOCAL_SETUP.md)
- [Demo Guide](docs/DEMO_GUIDE.md)
- [Known Limitations](docs/KNOWN_LIMITATIONS.md)
- [SCF Submission Draft](docs/SCF_SUBMISSION_DRAFT.md)

## Future Roadmap

Future SCF-funded work may include:

- production-grade Stellar payment expansion,
- planned USDC support on Stellar,
- trustline-aware payment UX,
- transparent sustainability-fee architecture,
- optional Soroban payment router integration,
- utility-focused creator rewards after validation,
- improved analytics/admin tooling,
- public APIs and embeddable tipping widgets,
- production Mainnet launch after Testnet validation.

These items are not presented as current functionality.

## Manual Evidence Needed

Before final SCF submission, the project owner should add:

- public GitHub repository URL,
- deployed demo URL,
- demo video,
- real Stellar Testnet transaction hash,
- founder/team details,
- final budget and milestone dates.
