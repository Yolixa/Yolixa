# Demo Guide

1. Start Laravel with `php artisan serve`.
2. Start Vite with `npm run dev` or use `npm run build` for compiled assets.
3. Open the homepage.
4. Connect a creator Freighter wallet on Stellar Testnet.
5. Register as a creator.
6. Copy the referral/tip URL from the creator dashboard.
7. Open the referral URL in a supporter browser/session.
8. Connect a different funded Freighter Testnet wallet.
9. Enter an XLM amount.
10. Approve the unsigned transaction in the wallet.
11. Wait for Horizon confirmation.
12. Show the returned transaction hash and Stellar explorer link.
13. Return to the creator dashboard and show the confirmed tip history.

Do not reuse the same public key for creator and supporter. Do not invent a transaction hash in demos; only show a real hash produced by the wallet/Horizon flow.
