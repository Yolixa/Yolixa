// public/js/wallets.js
// const STELLAR_HORIZON = "{{ env('STELLAR_HORIZON') }}";
// const STELLAR_PASSPHRASE = "{{ env('STELLAR_PASSPHRASE') }}";
// const YLX_ASSET_CODE = "{{ env('YLX_ASSET_CODE', 'YLX') }}";
// const YLX_ISSUER_PUBLIC = "{{ env('YLX_ISSUER_PUBLIC') }}";
// ✅ get values from window.config
const { STELLAR_HORIZON, STELLAR_PASSPHRASE, YLX_ASSET_CODE, YLX_ISSUER_PUBLIC } = window.config;

// ✅ connect to Stellar
const server = new window.StellarSdk.Server(STELLAR_HORIZON);

console.log("✅ SDK connected:", window.StellarSdk, server);

document.addEventListener("DOMContentLoaded", function ()
{
    const connectedWallet = localStorage.getItem("connected_wallet");
    const walletButton = document.getElementById("connectWalletBtn");
    const walletConnect = document.getElementById("walletConnect");
    const walletDisconnect = document.getElementById("walletDisconnect");
    let walletKey = null;

    // Check kis wallet ka key stored hai
    if (connectedWallet === "freighter") {
        walletKey = localStorage.getItem("freighter_wallet");
    } else if (connectedWallet === "walletconnect") {
        walletKey = localStorage.getItem("walletconnect_wallet");
    } else if (connectedWallet === "rabet") {
        walletKey = localStorage.getItem("rabet_wallet");
    }

    if (walletKey) {
        walletButton.textContent = `${walletKey.slice(0, 5)}...${walletKey.slice(-4)}`;
        walletConnect.classList.add("hidden");
        walletDisconnect.classList.remove("hidden");
    } else {
        walletButton.textContent = "Connect Wallet";
        walletConnect.classList.remove("hidden");
        walletDisconnect.classList.add("hidden");
    }
});

// Connect Wallet (main trigger)
async function connectWallet()
{
    const blockchainSelect = document.querySelector('.walletBlockchainSelect');
    const walletSelect = document.querySelector('.walletWalletSelect');
    const connectWalletBtn = document.getElementById("connectWalletBtn");

    const blockchainId = blockchainSelect.value;
    const walletId = walletSelect.value;

    if (!blockchainId || !walletId) {
        alert("Please select both blockchain and wallet first.");
        return;
    }

    let walletName = walletSelect.options[walletSelect.selectedIndex].text.toLowerCase();

    try {
        if (walletName.includes("freighter")) {
            await connectFreighter();
        } else if (walletName.includes("rabet")) {
            await connectRabet();
        } else if (walletName.includes("walletconnect")) {
            await connectWalletConnect();
        } else {
            alert("Wallet not supported yet.");
        }
    } catch (err) {
        console.error("Wallet connection error:", err);
        alert("Failed to connect wallet. See console for details.");
    }
}

async function connectFreighter () {
    const walletBtn = document.getElementById("connectWalletBtn");
    const modalWalletBtn = document.getElementById("modalWalletBtn");
    const walletModal = document.getElementById("walletModal");
    const walletConnect = document.getElementById("walletConnect");
    const walletDisconnect = document.getElementById("walletDisconnect");
    const blockchainSelect = document.querySelector('.walletBlockchainSelect');
    const walletSelect = document.querySelector('.walletWalletSelect');

    try {
        modalWalletBtn.disabled = true;
        modalWalletBtn.innerText = "Connecting...";

        // Check if Freighter wallet is installed
        if (typeof window.freighterApi === "undefined") {
            toastr.error("Freighter wallet not detected! Please install it from freighter.app");
            return null;
        }

        // Ask permission (this also unlocks wallet if locked)
        const access = await window.freighterApi.requestAccess();

        if (!access || !access.address) {
            toastr.warning("User cancelled or Freighter locked.");
            return null;
        }

        const publicKey = access.address;
        console.log("Freighter Connected:", publicKey);

        // Save to localStorage
        localStorage.setItem("freighter_wallet", publicKey);
        localStorage.setItem("connected_wallet", "freighter");

        walletModal.classList.add("hidden");
        walletBtn.innerText = `${publicKey.slice(0, 5)}...${publicKey.slice(-4)}`;
        walletConnect.classList.add("hidden");
        walletDisconnect.classList.remove("hidden");

        toastr.success("Freighter connected successfully!");
        return publicKey;
    } catch (err) {
        console.error("Freighter connection error:", err);

        if (err?.message?.includes("locked")) {
            toastr.error("Freighter wallet is locked. Please unlock it and try again.");
        } else if (err?.message?.includes("User declined")) {
            toastr.info("Connection cancelled by user.");
        } else {
            toastr.error("Freighter connection failed! Check console for details.");
        }

        return null;
    } finally {
        modalWalletBtn.disabled = false;
        modalWalletBtn.innerText = "Connect";
    }
};

async function connectRabet() {
    const walletBtn = document.getElementById("connectWalletBtn");
    const modalWalletBtn = document.getElementById("modalWalletBtn");
    const walletModal = document.getElementById("walletModal");
    const walletConnect = document.getElementById("walletConnect");
    const walletDisconnect = document.getElementById("walletDisconnect");
    const blockchainSelect = document.querySelector('.walletBlockchainSelect');
    const walletSelect = document.querySelector('.walletWalletSelect');

    try {
        modalWalletBtn.disabled = true;
        modalWalletBtn.innerText = "Connecting...";

        const rabet = window.rabet || window.Rabet;
        if (!rabet) {
            toastr.error("Rabet Wallet not detected. Please install it from https://rabet.io/", "Error");
            return;
        }

        // Attempt to connect wallet
        let result;
        if (typeof rabet.connect === "function") {
            result = await rabet.connect();
        } else if (typeof rabet.requestAccount === "function") {
            result = await rabet.requestAccount();
        } else {
            toastr.warning("Rabet API not supported by this version.", "Warning");
            return;
        }

        const publicKey = result?.publicKey || result;
        if (!publicKey) {
            toastr.error("Failed to retrieve public key from Rabet Wallet.", "Error");
            return;
        }
        console.log("Freighter Connected:", publicKey);

        // Save wallet info to local storage
        localStorage.setItem("rabet_wallet", publicKey);
        localStorage.setItem("connected_wallet", "rabet");

        // Update UI
        walletModal.classList.add("hidden");
        walletBtn.innerText = `${publicKey.slice(0, 5)}...${publicKey.slice(-4)}`;
        walletConnect.classList.add("hidden");
        walletDisconnect.classList.remove("hidden");

        toastr.success("Rabet connected successfully!");
        return publicKey;

    } catch (error) {
        console.error("Rabet Wallet connection error:", error);

        if (error.message?.includes("User rejected")) {
            toastr.warning("User cancelled wallet connection.", "Rabet Wallet");
        } else {
            toastr.error("Failed to connect Rabet Wallet. Try again.", "Error");
        }
    } finally {
        modalWalletBtn.disabled = false;
        modalWalletBtn.innerText = "Connect";
    }
}

async function createTrustline(publicKey) {
    try {
        const passphrase = window.config.STELLAR_PASSPHRASE?.replace(/"/g, "");
        const server = new StellarSdk.Server(window.config.STELLAR_HORIZON);
        const asset = new StellarSdk.Asset(window.config.YLX_ASSET_CODE, window.config.YLX_ISSUER_PUBLIC);

        const account = await server.loadAccount(publicKey);
        
        // 🔹 Check if trustline already exists
        if (account.balances.some(b => b.asset_code === asset.code && b.asset_issuer === asset.issuer)) {
            toastr.info("YLX trustline already exists!");
            return null;
        }

        const fee = await server.fetchBaseFee();

        const tx = new StellarSdk.TransactionBuilder(account, {
            fee: fee.toString(),
            networkPassphrase: passphrase,
        })
            .addOperation(StellarSdk.Operation.changeTrust({ asset }))
            .setTimeout(180)
            .build();

        const xdr = tx.toXDR();

        let signed;
        if (typeof window.freighterApi !== "undefined") {
            signed = await window.freighterApi.signTransaction(xdr, { networkPassphrase: passphrase });
        } else if (typeof window.rabet !== "undefined") {
            signed = await window.rabet.sign(xdr, { network: "TESTNET" });
        } else {
            throw new Error("No compatible wallet detected!");
        }

        const signedTx = StellarSdk.TransactionBuilder.fromXDR(signed, passphrase);
        const result = await server.submitTransaction(signedTx);

        toastr.success("YLX Trustline created!");
        return result.hash;
    } catch (err) {
        console.error(err);
        toastr.error("Trustline creation failed! Check console for details.");
        return null;
    }
}

async function disconnectWallet() {
    const walletButton = document.getElementById("connectWalletBtn");
    const walletConnect = document.getElementById("walletConnect");
    const walletDisconnect = document.getElementById("walletDisconnect");
    const walletModal = document.getElementById("walletModal");
    const disconnectBtn = document.getElementById("disconnectWalletBtn");

    try {
        disconnectBtn.disabled = true;
        disconnectBtn.innerText = "Disconnecting...";

        await new Promise((resolve) => setTimeout(resolve, 800));

        const connectedWallet = localStorage.getItem("connected_wallet");
        if (!connectedWallet) {
            toastr.warning("No wallet is currently connected.", "Warning");
            return;
        }

        let publicKey = null;

        // Freighter
        if (connectedWallet === "freighter") {
            publicKey = localStorage.getItem("freighter_wallet");
            localStorage.removeItem("freighter_wallet");
        }

        // Rabet
        else if (connectedWallet === "rabet") {
            publicKey = localStorage.getItem("rabet_wallet");
            localStorage.removeItem("rabet_wallet");
        }

        // WalletConnect
        else if (connectedWallet === "walletconnect") {
            publicKey = localStorage.getItem("walletconnect_wallet");
            try {
                if (window.WalletConnect && window.QRCodeModal) {
                    const connector = new WalletConnect.default({
                        bridge: "https://bridge.walletconnect.org",
                        qrcodeModal: QRCodeModal.default,
                    });
                    if (connector.connected) {
                        await connector.killSession();
                    }
                }
            } catch (wcError) {
                console.warn("WalletConnect session close error:", wcError);
            }
            localStorage.removeItem("walletconnect_wallet");
        }

        // Clear main wallet indicator
        localStorage.removeItem("connected_wallet");

        // Backend: Update user status
        // if (publicKey) {
        //     try {
        //         const response = await fetch("/disconnect-wallet", {
        //             method: "POST",
        //             headers: {
        //                 "Content-Type": "application/json",
        //                 "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
        //             },
        //             body: JSON.stringify({ address: publicKey }),
        //         });

        //         const data = await response.json();

        //         if (!response.ok || !data.success) {
        //             toastr.error(data.message || "Failed to update wallet status on server.", "Error");
        //         }
        //     } catch (apiError) {
        //         console.error("Error updating wallet status:", apiError);
        //         toastr.error("Server error while disconnecting wallet.", "Error");
        //     }
        // }

        // Reset UI
        walletButton.textContent = "Connect Wallet";
        walletConnect.classList.remove("hidden");
        walletDisconnect.classList.add("hidden");
        walletModal.classList.add("hidden");

        toastr.success("Wallet disconnected successfully.", "Success");

    } catch (error) {
        console.error("Error disconnecting wallet:", error);
        toastr.error("Failed to disconnect wallet. Try again.", "Error");
    } finally {
        disconnectBtn.disabled = false;
        disconnectBtn.innerText = "Disconnect Wallet";
    }
}
