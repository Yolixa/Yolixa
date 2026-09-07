# Local Setup

## Requirements

- PHP 8.2 or newer with common Laravel extensions.
- Composer.
- Node.js 22 or newer and npm.
- SQLite for quick local setup, or MySQL/MariaDB if preferred.
- Freighter browser wallet configured for Stellar Testnet.

## Install

```bash
composer install
npm install
copy .env.example .env
php artisan key:generate
```

For SQLite:

```bash
New-Item -ItemType Directory -Force database
New-Item -ItemType File -Force database/database.sqlite
```

Confirm `.env` contains:

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

Run migrations and seeders:

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

For frontend development:

```bash
npm run dev
```

## Wallet Setup

Install Freighter, switch it to Stellar Testnet, and fund separate creator/supporter Testnet accounts. Do not use the same wallet for creator and supporter during a demo because self-tipping is blocked.
