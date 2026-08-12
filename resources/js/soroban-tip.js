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
        throw new Error('Wallet authentication session expired. Please connect Freighter again.');
    }

    return data.public_key;
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

async function assertFreighterNetwork(networkPassphrase) {
    const network = await getNetwork();
    if (network.error) {
        throw new Error(network.error.message || 'Could not read Freighter network.');
    }

    if (network.networkPassphrase !== networkPassphrase) {
        throw new Error('Freighter is on the wrong network. Switch Freighter to Stellar Testnet.');
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

function normalizeFreighterError(error) {
    const message = String(error?.message || error || '');
    const lower = message.toLowerCase();

    if (lower.includes('reject') || lower.includes('denied') || lower.includes('declin')) {
        return 'Signature rejected in Freighter.';
    }

    if (lower.includes('insufficient')) {
        return 'Insufficient XLM for this tip and network fees.';
    }

    if (lower.includes('simulation')) {
        return 'Soroban simulation failed. The router may be paused, unconfigured, or the token may be disabled.';
    }

    return message || 'Soroban tip failed.';
}

export async function sendSorobanTip({ amount, receiverId, receiver, csrf, onProgress }) {
    try {
        onProgress?.('Checking Freighter...');
        const freighterAddress = await activeFreighterAddress();
        const sessionAddress = await getSessionWallet();

        if (freighterAddress !== sessionAddress) {
            throw new Error('Freighter account changed. Reconnect the wallet used for this Yolixa session.');
        }

        if (receiver === freighterAddress) {
            throw new Error('You cannot tip yourself.');
        }

        onProgress?.('Preparing secure tip...');
        const intent = await postJson('/api/soroban/tip/intent', {
            amount: String(amount),
            asset: 'XLM',
            receiver_id: receiverId,
            sender: freighterAddress,
        }, csrf);

        await assertFreighterNetwork(intent.network_passphrase);

        const server = new rpc.Server(intent.rpc_url, {
            allowHttp: intent.rpc_url.startsWith('http://'),
        });

        onProgress?.('Simulating transaction...');
        const prepared = await buildTipTransaction(server, intent);

        onProgress?.('Awaiting wallet approval...');
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

        const signedTransaction = TransactionBuilder.fromXDR(signed.signedTxXdr, intent.network_passphrase);

        onProgress?.('Submitting to Stellar...');
        const sent = await server.sendTransaction(signedTransaction);
        if (sent.status === 'ERROR') {
            throw new Error('Stellar rejected the Soroban transaction.');
        }

        if (!['PENDING', 'DUPLICATE'].includes(sent.status)) {
            throw new Error('Soroban RPC did not accept the transaction.');
        }

        const txHash = sent.hash;
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
        error.message = normalizeFreighterError(error);
        throw error;
    }
}

window.YolixaSorobanTip = {
    sendTip: sendSorobanTip,
};
