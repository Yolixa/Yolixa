import {
    BASE_FEE,
    TransactionBuilder,
    rpc,
} from '@stellar/stellar-sdk';
import { Client as YolixaTipRouterClient } from '../../packages/yolixa-tip-router/src/index.ts';
import {
    getAddress,
    getNetwork,
    isConnected,
    requestAccess,
    signTransaction,
} from '@stellar/freighter-api';

const DEFAULT_TIMEOUT_SECONDS = 300;
const TESTNET_NETWORK_PASSPHRASE = 'Test SDF Network ; September 2015';
const SUPPORTED_WALLET_TYPES = ['freighter', 'rabet'];

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

async function postJson(url, payload, token = csrfToken()) {
    const response = await fetch(url, {
        method: 'POST',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': token,
        },
        credentials: 'same-origin',
        body: JSON.stringify(payload),
    });

    const data = await response.json().catch(() => ({}));
    if (!response.ok || data.success === false) {
        const error = new Error(data.message || 'Request failed.');
        error.response = response;
        error.data = data;
        throw error;
    }

    return data;
}

async function getSessionWallet() {
    const response = await fetch('/auth/session', {
        headers: { Accept: 'application/json' },
        credentials: 'same-origin',
    });
    const data = await response.json();

    if (!response.ok || !data.authenticated || !data.public_key) {
        throw new Error('Wallet authentication session expired. Please connect your wallet again.');
    }

    return data.public_key;
}

function normalizeWalletType(walletType) {
    const normalized = String(walletType || localStorage.getItem('connected_wallet') || '').trim().toLowerCase();

    if (SUPPORTED_WALLET_TYPES.includes(normalized)) {
        return normalized;
    }

    throw new Error('Soroban tipping supports Freighter and Rabet wallets.');
}

function walletLabel(walletType) {
    return String(walletType || '').toLowerCase() === 'rabet' ? 'Rabet' : 'Freighter';
}

function getRabetApi() {
    return window.rabet || null;
}

async function activeFreighterAddress() {
    const connected = await isConnected();
    if (connected.error || !connected.isConnected) {
        throw new Error('Freighter is not available. Install or unlock Freighter to send a Soroban tip.');
    }

    let addressResult = await getAddress();
    if (addressResult.error || !addressResult.address) {
        addressResult = await requestAccess();
    }

    if (addressResult.error || !addressResult.address) {
        throw new Error(addressResult.error?.message || 'Freighter is disconnected.');
    }

    return addressResult.address;
}

async function activeRabetAddress() {
    const rabet = getRabetApi();

    if (!rabet) {
        throw new Error('Rabet is not available. Install or unlock Rabet to send a Soroban tip.');
    }

    if (typeof rabet.isUnlocked === 'function') {
        const unlocked = await rabet.isUnlocked();
        if (unlocked === false) {
            throw new Error('Rabet is locked. Unlock Rabet and try again.');
        }
    }

    if (typeof rabet.connect !== 'function') {
        throw new Error('This Rabet version does not expose the connection API.');
    }

    const result = await rabet.connect();
    if (result?.error) {
        throw new Error(typeof result.error === 'string' ? result.error : result.error?.message || 'Rabet connection was rejected.');
    }

    const address = result?.publicKey || result?.address || (typeof result === 'string' ? result : null);
    if (!address) {
        throw new Error('Rabet did not return an active public key.');
    }

    return address;
}

async function assertFreighterNetwork(networkPassphrase) {
    if (networkPassphrase !== TESTNET_NETWORK_PASSPHRASE) {
        throw new Error('Phase 2 Soroban tipping only supports Stellar Testnet.');
    }

    const network = await getNetwork();
    if (network.error) {
        throw new Error(network.error.message || 'Could not read Freighter network.');
    }

    if (network.networkPassphrase !== networkPassphrase) {
        throw new Error('Freighter is on the wrong network. Switch Freighter to Stellar Testnet.');
    }
}

function networkNameFromPassphrase(networkPassphrase) {
    if (networkPassphrase !== TESTNET_NETWORK_PASSPHRASE) {
        throw new Error('Phase 2 Soroban tipping only supports Stellar Testnet.');
    }

    return 'testnet';
}

async function assertRabetNetwork(networkPassphrase) {
    const rabet = getRabetApi();

    if (typeof rabet?.getNetwork !== 'function') {
        throw new Error('Rabet 1.8.0 or newer is required so Yolixa can verify the active Stellar Testnet network before signing.');
    }

    const network = await rabet.getNetwork();
    if (network?.error) {
        throw new Error(typeof network.error === 'string' ? network.error : network.error?.message || 'Could not read Rabet network.');
    }

    const expectedNetwork = networkNameFromPassphrase(networkPassphrase);
    const walletPassphrase = network?.networkPassphrase || network?.network_passphrase || network?.passphrase;
    const walletNetwork = String(network?.network || network?.networkName || network?.networkId || '').toLowerCase();

    if (walletPassphrase && walletPassphrase !== networkPassphrase) {
        throw new Error(`Rabet is on the wrong network. Switch Rabet to Stellar ${expectedNetwork === 'mainnet' ? 'Mainnet' : 'Testnet'}.`);
    }

    if (!walletPassphrase && walletNetwork && walletNetwork !== expectedNetwork && !(expectedNetwork === 'mainnet' && walletNetwork === 'public')) {
        throw new Error(`Rabet is on the wrong network. Switch Rabet to Stellar ${expectedNetwork === 'mainnet' ? 'Mainnet' : 'Testnet'}.`);
    }
}

async function buildTipTransaction(server, intent) {
    const client = new YolixaTipRouterClient({
        contractId: intent.router_contract_id,
        publicKey: intent.sender,
        rpcUrl: intent.rpc_url,
        allowHttp: intent.rpc_url.startsWith('http://'),
        server,
        networkPassphrase: intent.network_passphrase,
    });

    const assembled = await client.tip({
        sender: intent.sender,
        creator: intent.creator,
        token: intent.token_contract_id,
        amount: BigInt(intent.amount_atomic),
        tip_id: BigInt(intent.contract_tip_id),
    }, {
        fee: BASE_FEE,
        timeoutInSeconds: DEFAULT_TIMEOUT_SECONDS,
        restore: false,
    });

    if (rpc.Api.isSimulationError(assembled.simulation)) {
        throw new Error(assembled.simulation.error || 'Soroban simulation failed.');
    }

    if (rpc.Api.isSimulationRestore(assembled.simulation)) {
        throw new Error('Router state requires restoration before this tip can be sent.');
    }

    if (!assembled.built) {
        throw new Error('Soroban transaction was not prepared.');
    }

    return assembled.built;
}

async function waitForFinalTransaction(server, hash, onProgress) {
    const startedAt = Date.now();
    const timeoutMs = 90_000;

    while (Date.now() - startedAt < timeoutMs) {
        const result = await server.getTransaction(hash);
        if (result.status === rpc.Api.GetTransactionStatus.SUCCESS) {
            return result;
        }

        if (result.status === rpc.Api.GetTransactionStatus.FAILED) {
            throw new Error('Stellar rejected the Soroban transaction.');
        }

        onProgress?.('Confirming on-chain...');
        await new Promise((resolve) => setTimeout(resolve, 2500));
    }

    const error = new Error('Soroban transaction is still pending. Do not retry with classic payment; confirmation can be checked again safely.');
    error.retryable = true;
    throw error;
}

function normalizeSignedXdr(response) {
    const signedXdr = response?.signedTxXdr
        || response?.signedTx
        || response?.signed_transaction
        || response?.signed_envelope_xdr
        || response?.xdr
        || response?.data?.signedTxXdr
        || response?.data?.signed_envelope_xdr
        || response?.data?.xdr
        || (typeof response === 'string' ? response : null);

    if (!signedXdr || typeof signedXdr !== 'string') {
        throw new Error('Wallet returned an invalid signed transaction.');
    }

    return signedXdr;
}

async function signWithFreighter(prepared, intent) {
    const signed = await signTransaction(prepared.toXDR(), {
        address: intent.sender,
        networkPassphrase: intent.network_passphrase,
    });

    if (signed.error || !signed.signedTxXdr) {
        throw new Error(signed.error?.message || 'Transaction signing was rejected.');
    }

    if (signed.signerAddress && signed.signerAddress !== intent.sender) {
        throw new Error('Freighter signed with a different account. Reconnect the expected wallet.');
    }

    return signed.signedTxXdr;
}

async function signWithRabet(prepared, intent) {
    const rabet = getRabetApi();

    if (!rabet || typeof rabet.sign !== 'function') {
        throw new Error('This Rabet version does not support transaction signing. Update Rabet and try again.');
    }

    const signed = await rabet.sign(prepared.toXDR(), networkNameFromPassphrase(intent.network_passphrase));
    if (signed?.error) {
        throw new Error(typeof signed.error === 'string' ? signed.error : signed.error?.message || 'Transaction signing was rejected.');
    }

    return normalizeSignedXdr(signed);
}

function walletAdapter(walletType) {
    const type = normalizeWalletType(walletType);

    if (type === 'rabet') {
        return {
            type,
            label: 'Rabet',
            activeAddress: activeRabetAddress,
            assertNetwork: assertRabetNetwork,
            sign: signWithRabet,
        };
    }

    return {
        type: 'freighter',
        label: 'Freighter',
        activeAddress: activeFreighterAddress,
        assertNetwork: assertFreighterNetwork,
        sign: signWithFreighter,
    };
}

function normalizeWalletError(error, walletName) {
    const message = String(error?.message || error || '');
    const lower = message.toLowerCase();

    if (lower.includes('reject') || lower.includes('denied') || lower.includes('declin')) {
        return `Signature rejected in ${walletName}.`;
    }

    if (lower.includes('insufficient')) {
        return 'Insufficient XLM for this tip and network fees.';
    }

    if (lower.includes('simulation')) {
        return 'Soroban simulation failed. The router may be paused, unconfigured, or the token may be disabled.';
    }

    return message || 'Soroban tip failed.';
}

export async function sendSorobanTip({ amount, receiverId, receiver, csrf, onProgress, walletType }) {
    let wallet = { label: walletLabel(walletType) };

    try {
        wallet = walletAdapter(walletType);

        onProgress?.(`Checking ${wallet.label}...`);
        const walletAddress = await wallet.activeAddress();
        const sessionAddress = await getSessionWallet();

        if (walletAddress !== sessionAddress) {
            throw new Error(`${wallet.label} account changed. Reconnect the wallet used for this Yolixa session.`);
        }

        if (receiver === walletAddress) {
            throw new Error('You cannot tip yourself.');
        }

        onProgress?.('Preparing secure tip...');
        const intent = await postJson('/api/soroban/tip/intent', {
            amount: String(amount),
            asset: 'XLM',
            receiver_id: receiverId,
            sender: walletAddress,
        }, csrf);

        await wallet.assertNetwork(intent.network_passphrase);

        const server = new rpc.Server(intent.rpc_url, {
            allowHttp: intent.rpc_url.startsWith('http://'),
        });

        onProgress?.('Simulating transaction...');
        const prepared = await buildTipTransaction(server, intent);

        onProgress?.('Awaiting wallet approval...');
        const signedTxXdr = await wallet.sign(prepared, intent);
        const signedTransaction = TransactionBuilder.fromXDR(signedTxXdr, intent.network_passphrase);
        if (signedTransaction.source && signedTransaction.source !== intent.sender) {
            throw new Error(`${wallet.label} signed a transaction for a different account. Reconnect the expected wallet.`);
        }

        onProgress?.('Submitting to Stellar...');
        const sent = await server.sendTransaction(signedTransaction);
        if (sent.status === 'ERROR') {
            throw new Error('Stellar rejected the Soroban transaction.');
        }

        if (!['PENDING', 'DUPLICATE'].includes(sent.status)) {
            throw new Error('Soroban RPC did not accept the transaction.');
        }

        const txHash = sent.hash;
        if (!txHash) {
            throw new Error('Soroban RPC accepted the transaction without returning a hash.');
        }

        onProgress?.('Recording submitted transaction...');
        await postJson('/api/soroban/tip/submitted', {
            intent_id: intent.intent_id,
            tx_hash: txHash,
        }, csrf);

        await waitForFinalTransaction(server, txHash, onProgress);

        onProgress?.('Recording proof...');
        const confirmation = await postJson('/api/soroban/tip/confirm', {
            intent_id: intent.intent_id,
            tx_hash: txHash,
        }, csrf);

        onProgress?.('Confirmed');
        return {
            txHash,
            intent,
            confirmation,
        };
    } catch (error) {
        error.message = normalizeWalletError(error, wallet.label);
        throw error;
    }
}

window.YolixaSorobanTip = {
    sendTip: sendSorobanTip,
};
