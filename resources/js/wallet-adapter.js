import {
    getAddress,
    getNetwork,
    isConnected,
    requestAccess,
    signMessage,
    signTransaction,
} from '@stellar/freighter-api';

const TESTNET_PASSPHRASE = 'Test SDF Network ; September 2015';

function errorMessage(error, fallback) {
    if (!error) return fallback;
    if (typeof error === 'string') return error;
    return error.message || error.name || fallback;
}

function assertFreighterResult(result, fallback) {
    if (result?.error) {
        throw new Error(errorMessage(result.error, fallback));
    }

    return result;
}

function normalizeSignature(value) {
    if (typeof value === 'string') {
        return value;
    }

    if (value instanceof Uint8Array || Array.isArray(value)) {
        return btoa(String.fromCharCode(...value));
    }

    if (value && typeof value === 'object') {
        if (value.type === 'Buffer' && Array.isArray(value.data)) {
            return btoa(String.fromCharCode(...value.data));
        }

        if (Array.isArray(value.data)) {
            return btoa(String.fromCharCode(...value.data));
        }
    }

    throw new Error('Freighter returned an unsupported signature format.');
}

async function detectFreighter() {
    const result = await isConnected();

    return Boolean(result?.isConnected && !result?.error);
}

async function requestFreighterAccess() {
    const result = assertFreighterResult(
        await requestAccess(),
        'Freighter permission request failed.'
    );

    if (!result.address) {
        throw new Error('Freighter did not return an active account.');
    }

    return result.address;
}

async function getFreighterPublicKey() {
    const result = assertFreighterResult(
        await getAddress(),
        'Could not read the active Freighter account.'
    );

    if (!result.address) {
        throw new Error('Freighter is locked or not connected.');
    }

    return result.address;
}

async function getFreighterNetwork() {
    return assertFreighterResult(
        await getNetwork(),
        'Could not read the active Freighter network.'
    );
}

async function assertFreighterTestnet(expectedPassphrase = TESTNET_PASSPHRASE) {
    const network = await getFreighterNetwork();
    const passphrase = network.networkPassphrase || '';
    const label = String(network.network || '').toLowerCase();

    if (passphrase && passphrase !== expectedPassphrase) {
        throw new Error('Freighter is on the wrong network. Switch Freighter to Stellar Testnet.');
    }

    if (!passphrase && label && label !== 'testnet') {
        throw new Error('Freighter is on the wrong network. Switch Freighter to Stellar Testnet.');
    }

    return network;
}

async function signFreighterChallenge(challenge, address, networkPassphrase = TESTNET_PASSPHRASE) {
    const result = assertFreighterResult(
        await signMessage(challenge, { address, networkPassphrase }),
        'Freighter message signing failed.'
    );

    if (result.signerAddress && address && result.signerAddress !== address) {
        throw new Error('Freighter signed with a different account. Reconnect the expected wallet.');
    }

    if (!result.signedMessage) {
        throw new Error('Freighter did not return a signature.');
    }

    return normalizeSignature(result.signedMessage);
}

async function signFreighterTransactionXdr(xdr, address, networkPassphrase = TESTNET_PASSPHRASE) {
    const result = assertFreighterResult(
        await signTransaction(xdr, { address, networkPassphrase }),
        'Freighter transaction signing failed.'
    );

    if (result.signerAddress && address && result.signerAddress !== address) {
        throw new Error('Freighter signed with a different account. Reconnect the expected wallet.');
    }

    if (!result.signedTxXdr) {
        throw new Error('Freighter returned an invalid signed transaction.');
    }

    return result.signedTxXdr;
}

function getRabetApi() {
    return window.rabet || null;
}

window.YolixaWallets = {
    freighter: {
        detect: detectFreighter,
        requestAccess: requestFreighterAccess,
        publicKey: getFreighterPublicKey,
        network: getFreighterNetwork,
        assertTestnet: assertFreighterTestnet,
        signAuthenticationMessage: signFreighterChallenge,
        signTransactionXdr: signFreighterTransactionXdr,
    },
    rabet: {
        experimental: true,
        detect: () => Boolean(getRabetApi()),
        api: getRabetApi,
    },
};
