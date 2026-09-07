# Security Model

## Non-Custodial Design

Yolixa never takes custody of supporter or creator funds. Private keys stay inside the user's wallet. The app builds unsigned payment XDR, the wallet signs it in the browser, and Stellar/Horizon confirms settlement.

## Wallet Ownership Proof

Wallet login uses a random session challenge:

- Laravel issues a unique challenge for the submitted Stellar public key.
- The challenge expires.
- The wallet signs the challenge.
- Laravel verifies the signature against the expected public key.
- The challenge is removed after use.
- The authenticated session is bound to the verified wallet.

Creator registration uses the authenticated session wallet. Submitting another public key in the request body does not grant creator access.

## Trust Boundaries

The browser is not trusted for payment truth. It may submit a transaction hash, amount, sender, receiver, or status, but Laravel verifies the transaction independently through Horizon before storing a confirmed tip.

## Duplicate Prevention

`tips.tx_hash` is unique. `TipService` also checks for existing hashes before and during insertion to make retries idempotent and avoid duplicate credit.

## Authorization

- Creator dashboard access requires an authenticated creator session.
- Dashboard public-key URL must match the authenticated creator wallet.
- Mutable tip endpoints require an authenticated wallet session.
- Admin routes are protected by role middleware.

## Rate Limiting

Wallet challenge and wallet authentication endpoints use the `wallet-auth` limiter. Tip APIs use the `tip-api` limiter.

## Secret Handling

No Stellar seeds or wallet private keys should be committed to `.env.example`, source code, docs, logs, tests, or issue trackers. Future issuer/distribution secrets must be provisioned outside the current MVP path.
