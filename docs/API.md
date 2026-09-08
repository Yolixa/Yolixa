# Current API

All endpoints are web routes and use Laravel CSRF protection.

## Wallet Auth

`GET /auth/session`

Returns current authenticated wallet state and a CSRF token.

`POST /auth/challenge`

Body:

```json
{ "address": "G..." }
```

Returns a one-time challenge for a valid Stellar public key.

`POST /save-wallet`

Body:

```json
{
  "address": "G...",
  "blockchainId": 1,
  "walletId": 1,
  "status": true,
  "signature": "base64-or-hex-signature"
}
```

Verifies the signed challenge and creates or resumes the wallet session.

`POST /disconnect-wallet`

Requires authentication. Disconnects only the wallet authenticated in the current session.

## Creator

`POST /creator/register`

Requires authentication. Creates or upgrades the authenticated wallet user into a creator. The submitted `public_key` must match the session wallet.

`GET /r/{code}`

Public creator tipping page by referral key.

`GET /{username}`

Public creator tipping page by username.

`GET /dashboard/{publicKey}`

Requires creator authentication and matching wallet public key.

`POST /creator/update-profile`

Requires creator authentication. Updates basic public profile fields.

## Tips

`POST /api/tip/preview`

Public. Validates XLM amount and returns current direct-payment preview. Current platform fee is `0.0000000`.

`POST /api/tip/build-xdr`

Requires authenticated wallet session.

Body:

```json
{
  "amount": "1.0000000",
  "destination": "G...",
  "asset": "XLM",
  "sender": "G..."
}
```

Returns unsigned XDR for one native XLM payment from the authenticated supporter wallet to the creator wallet after a conservative spendable-XLM preflight.

`POST /api/tip/submit`

Requires authenticated wallet session.

Body:

```json
{
  "signedXdr": "...",
  "sender_key": "G..."
}
```

Submits the wallet-signed transaction to Horizon and returns the transaction hash.

`POST /api/tip/record`

Requires authenticated wallet session.

Body:

```json
{
  "tx_hash": "64-char-hex-hash",
  "amount": "1.0000000",
  "asset": "XLM",
  "receiver_id": 123,
  "sender_key": "G..."
}
```

Verifies the transaction through Horizon before storing a confirmed tip.

## Future/Experimental Endpoints

`/api/soroban/*` routes exist for future Soroban/router experiments. In the current classic MVP configuration they return a disabled response. They are usable only when the app is deliberately configured for future Soroban mode and valid contract IDs are supplied.
