<script>
    let currentCsrfToken = "{{ csrf_token() }}";

    document.addEventListener("DOMContentLoaded", function () {
        updateWalletUIStatus();
        bindDashboardReconnect();
    });

    async function fetchSessionStatus() {
        const response = await fetch("/auth/session", {
            headers: { "Accept": "application/json" },
            credentials: "same-origin",
        });
        const data = await response.json();

        if (data.csrf_token) {
            currentCsrfToken = data.csrf_token;
        }

        return { response, data };
    }

    async function postJsonWithFreshCsrf(url, payload) {
        const request = () => fetch(url, {
            method: "POST",
            headers: {
                "Accept": "application/json",
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": currentCsrfToken,
            },
            credentials: "same-origin",
            body: JSON.stringify(payload),
        });

        let response = await request();

        if (response.status === 419) {
            await fetchSessionStatus();
            response = await request();
        }

        return response;
    }

    function bindDashboardReconnect() {
        const dashboardNavLink = document.getElementById("dashboardNavLink");
        if (!dashboardNavLink) return;

        dashboardNavLink.addEventListener("click", async function (event) {
            const connectedWallet = localStorage.getItem("connected_wallet");
            const publicKey = connectedWallet ? localStorage.getItem(`${connectedWallet}_wallet`) : null;

            if (!publicKey) return;

            event.preventDefault();
            await goToCreatorDashboard(publicKey, connectedWallet);
        });
    }

    function updateWalletUIStatus() {
        const connectedWallet = localStorage.getItem("connected_wallet");
        const walletButton = document.getElementById("connectWalletBtn");
        const dashboardNavLink = document.getElementById("dashboardNavLink");
        
        let walletKey = null;
        if (connectedWallet) {
            walletKey = localStorage.getItem(`${connectedWallet}_wallet`);
        }

        if (walletKey) {
            if (walletButton) {
                walletButton.textContent = `${walletKey.slice(0, 5)}...${walletKey.slice(-4)}`;
            }
            
            // Show dashboard link conditionally
            const role = localStorage.getItem("user_role");
            if (role === 'creator') {
                if(dashboardNavLink) dashboardNavLink.classList.remove('hidden');
                if(dashboardNavLink) dashboardNavLink.href = `/dashboard/${walletKey}`;
            } else {
                if(dashboardNavLink) dashboardNavLink.classList.add('hidden');
            }
        } else {
            if (walletButton) {
                walletButton.textContent = "Connect Wallet";
            }
            if(dashboardNavLink) dashboardNavLink.classList.add('hidden');
        }
    }

    // Modal control functions
    function openWalletModal() {
        if (localStorage.getItem("connected_wallet")) {
            openDisconnectModal();
            return;
        }
        const modal = document.getElementById('walletModal');
        if(modal){
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
    }

    function closeWalletModal() {
        const modal = document.getElementById('walletModal');
        if(modal) modal.classList.add('hidden');
    }

    function openCreatorModal() {
        const connectedWallet = localStorage.getItem("connected_wallet");
        if (!connectedWallet) {
            toastr.warning("Please connect your wallet first before registering as a creator.");
            openWalletModal();
            return;
        }

        const modal = document.getElementById('creatorModal');
        if(modal){
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
    }

    function closeCreatorModal() {
        const modal = document.getElementById('creatorModal');
        if(modal) modal.classList.add('hidden');
    }

    function openDisconnectModal () {
        const modal = document.getElementById("disconnectModal");
        if(modal){
            modal.classList.remove("hidden");
            modal.classList.add("flex");
        }
    }

    function closeDisconnectModal () {
        const modal = document.getElementById("disconnectModal");
        if(modal) modal.classList.add("hidden");
    }

    // Get Wallet Types
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('creatorBlockchainSelect') || e.target.classList.contains('walletBlockchainSelect')) {
            let blockchainId = e.target.value;
            let targetSelect = e.target.classList.contains('creatorBlockchainSelect') 
                               ? document.querySelector('.creatorWalletSelect') 
                               : document.querySelector('.walletWalletSelect');

            if (blockchainId && targetSelect) {
                fetch(`/get-wallets/${blockchainId}`, {
                    headers: { 'Accept': 'application/json' }
                })
                .then(async (response) => {
                    if (!response.ok) throw new Error("Invalid response");
                    return response.json();
                })
                .then(data => {
                    targetSelect.innerHTML = '<option value="">-- Select Wallet --</option>';
                    if(data.wallets && data.wallets.length > 0) {
                        data.wallets.forEach(wallet => {
                            let option = document.createElement('option');
                            option.value = wallet.id;
                            option.textContent = wallet.name;
                            targetSelect.appendChild(option);
                        });
                    }
                })
                .catch(err => {
                    console.error("Wallet loading error:", err);
                    toastr.error("Could not fetch wallet options.");
                });
            }
        }
    });

    // Wallet Connect logic
    async function connectWallet() {
        const blockchainSelect = document.querySelector('.walletBlockchainSelect');
        const walletSelect = document.querySelector('.walletWalletSelect');
        const blockchainId = blockchainSelect ? blockchainSelect.value : null;
        const walletId = walletSelect ? walletSelect.value : null;

        if (!blockchainId || !walletId) {
            toastr.warning("Please select blockchain and wallet.");
            return;
        }

        const modalBtn = document.getElementById('modalWalletBtn');
        if(modalBtn) modalBtn.innerText = "Connecting...";

        let walletName = walletSelect.options[walletSelect.selectedIndex].text.toLowerCase();
        
        try {
            if (walletName.includes("freighter")) {
                await connectFreighter();
            } else if (walletName.includes("rabet")) {
                await connectRabet();
            } else {
                toastr.info("Wallet not fully supported in this demo.");
            }
        } catch (err) {
            console.error("Master connect error:", err);
        } finally {
            if(modalBtn) modalBtn.innerText = "Connect";
        }
    }

    function rememberWalletSession(publicKey, walletType, data) {
        localStorage.setItem(`${walletType}_wallet`, publicKey);
        localStorage.setItem("connected_wallet", walletType);
        localStorage.setItem("user_role", data.role || 'fan');
        if (data.blockchain_id) localStorage.setItem("wallet_blockchain_id", data.blockchain_id);
        if (data.wallet_type_id) localStorage.setItem("wallet_type_id", data.wallet_type_id);

        updateWalletUIStatus();
    }

    async function handleWalletLoginSuccess(publicKey, walletType, data) {
        rememberWalletSession(publicKey, walletType, data);
        closeWalletModal();
        
        if (data.role === 'creator') {
            toastr.success("Welcome back Creator! Redirecting to dashboard...");
            setTimeout(() => {
                window.location.href = `/dashboard/${publicKey}`;
            }, 1000);
        } else {
            toastr.success("Wallet Connected! You can now send tips.");
        }
    }

    async function goToCreatorDashboard(publicKey, walletType) {
        try {
            const { response: statusRes, data: status } = await fetchSessionStatus();

            if (statusRes.ok && status.authenticated && status.role === 'creator' && status.public_key === publicKey) {
                window.location.href = `/dashboard/${publicKey}`;
                return;
            }
        } catch (error) {
            console.warn("Could not verify current dashboard session. Reconnecting wallet.", error);
        }

        toastr.warning("Your app session expired. Please connect your wallet again.");
        localStorage.removeItem("connected_wallet");
        localStorage.removeItem("freighter_wallet");
        localStorage.removeItem("rabet_wallet");
        localStorage.removeItem("walletconnect_wallet");
        localStorage.removeItem("user_role");
        updateWalletUIStatus();
    }

    function normalizeWalletSignature(response) {
        const signature = response?.signature
            || response?.signedMessage
            || response?.signed_message
            || response?.signedPayload
            || response?.data?.signature
            || response?.data?.signedMessage
            || response?.data?.signed_message
            || (typeof response === 'string' ? response : null);

        if (typeof signature === 'string') {
            return signature;
        }

        if (signature instanceof Uint8Array || Array.isArray(signature)) {
            return btoa(String.fromCharCode(...signature));
        }

        if (signature && typeof signature === 'object') {
            if (signature.type === 'Buffer' && Array.isArray(signature.data)) {
                return btoa(String.fromCharCode(...signature.data));
            }

            if (Array.isArray(signature.data)) {
                return btoa(String.fromCharCode(...signature.data));
            }

            const nested = signature.signature
                || signature.signedMessage
                || signature.signed_message
                || signature.data?.signature;

            if (typeof nested === 'string') {
                return nested;
            }

            if (nested instanceof Uint8Array || Array.isArray(nested)) {
                return btoa(String.fromCharCode(...nested));
            }
        }

        throw new Error("Wallet returned an unsupported signature format.");
    }

    function getFreighterApi() {
        return window.freighterApi
            || window.stellarFreighter
            || window.StellarFreighter
            || window.freighter
            || null;
    }

    async function signFreighterChallenge(challenge, publicKey) {
        const freighter = getFreighterApi();

        if (!freighter || typeof freighter.signMessage !== "function") {
            throw new Error("This Freighter version does not support message signing.");
        }

        const attempts = [
            () => freighter.signMessage(challenge, {
                address: publicKey,
                networkPassphrase: window.config?.STELLAR_PASSPHRASE || "Test SDF Network ; September 2015",
            }),
            () => freighter.signMessage(challenge, {
                network: "TESTNET",
                networkPassphrase: window.config?.STELLAR_PASSPHRASE || "Test SDF Network ; September 2015",
            }),
            () => freighter.signMessage(challenge),
        ];

        let lastError = null;

        for (const attempt of attempts) {
            try {
                const response = await attempt();
                if (response?.error) {
                    throw new Error(response.error);
                }

                return normalizeWalletSignature(response);
            } catch (error) {
                lastError = error;

                const message = String(error?.message || error || '').toLowerCase();
                if (message.includes('reject') || message.includes('declin') || message.includes('denied')) {
                    throw error;
                }
            }
        }

        throw lastError || new Error("Freighter did not return a valid signature.");
    }

    async function connectFreighter() {
        const freighter = getFreighterApi();

        if (!freighter) {
            toastr.error("Freighter API not loaded. Please hard refresh or check the Freighter extension/CDN.");
            return;
        }
        try {
            const result = typeof freighter.requestAccess === "function"
                ? await freighter.requestAccess()
                : { address: await freighter.getPublicKey() };
            const publicKey = result?.address || result?.publicKey || result;

            if (publicKey) {

                // 1. Fetch Challenge
                const chalRes = await fetch("/auth/challenge", {
                    method: "POST",
                    headers: { "Content-Type": "application/json", "X-CSRF-TOKEN": "{{ csrf_token() }}" },
                    body: JSON.stringify({ address: publicKey })
                });
                const chalData = await chalRes.json();
                if (!chalData.success) throw new Error("Could not acquire challenge");

                // 2. Sign Challenge (Use signMessage or fallback)
                let signature = "";
                try {
                    signature = await signFreighterChallenge(chalData.challenge, publicKey);
                } catch (signErr) {
                    console.error("Freighter signMessage failed:", signErr);
                    throw new Error(signErr?.message || "Signature rejected or failed in Freighter.");
                }

                // 3. Authenticate
                const res = await fetch("/save-wallet", {
                    method: "POST",
                    headers: {
                        "Accept": "application/json",
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": "{{ csrf_token() }}",
                    },
                    body: JSON.stringify({
                        address: publicKey,
                        blockchainId: document.querySelector('.walletBlockchainSelect').value,
                        walletId: document.querySelector('.walletWalletSelect').value,
                        status: 1,
                        signature: signature
                    }),
                });
                
                let data;
                try {
                    data = await res.json();
                } catch (parseError) {
                    console.error("JSON parse error on saving freighter connection:", parseError);
                    toastr.error("Received unexpected format from server.");
                    return;
                }

                if (!res.ok || !data.success) {
                    console.error("Server API logic error:", data);
                    toastr.error(data.message || "Failed to finalize connection. Please try again.");
                    return;
                }

                handleWalletLoginSuccess(publicKey, 'freighter', data);
            }
        } catch (error) {
            console.error("Freighter flow interrupted:", error);
            toastr.error(error?.message || "User denied permission or connection failed.");
        }
    }

    async function connectRabet() {
        if (typeof window.rabet === "undefined") {
            toastr.error("Rabet not found.");
            return;
        }
        
        try {
            const result = await window.rabet.connect();
            if (result && result.publicKey) {
                const publicKey = result.publicKey;

                // 1. Fetch Challenge
                const chalRes = await fetch("/auth/challenge", {
                    method: "POST",
                    headers: { "Content-Type": "application/json", "X-CSRF-TOKEN": "{{ csrf_token() }}" },
                    body: JSON.stringify({ address: publicKey })
                });
                const chalData = await chalRes.json();
                if (!chalData.success) throw new Error("Could not acquire challenge");

                // 2. Sign Challenge
                let signature = "";
                try {
                    const signRes = await window.rabet.signMessage(chalData.challenge, 'testnet');
                    signature = normalizeWalletSignature(signRes);
                } catch (signErr) {
                    throw new Error("Signature rejected securely by Rabet.");
                }

                // 3. Authenticate
                const res = await fetch("/save-wallet", {
                    method: "POST",
                    headers: {
                        "Accept": "application/json",
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": "{{ csrf_token() }}",
                    },
                    body: JSON.stringify({
                        address: publicKey,
                        blockchainId: document.querySelector('.walletBlockchainSelect').value,
                        walletId: document.querySelector('.walletWalletSelect').value,
                        status: 1,
                        signature: signature
                    }),
                });
                
                let data;
                try {
                    data = await res.json();
                } catch (parseError) {
                    console.error("JSON parse error on saving rabet connection:", parseError);
                    toastr.error("Received unexpected server response format.");
                    return;
                }

                if (!res.ok || !data.success) {
                    console.error("Server API logical error:", data);
                    toastr.error(data.message || "Failed to finalize connection.");
                    return;
                }

                handleWalletLoginSuccess(publicKey, 'rabet', data);
            }
        } catch (error) {
            console.error("Rabet connect error:", error);
            toastr.error("User denied permission or an error occurred.");
        }
    }

    async function disconnectWallet() {
        const connectedWallet = localStorage.getItem("connected_wallet");
        const publicKey = localStorage.getItem(`${connectedWallet}_wallet`);
        const disconnectBtn = document.getElementById("disconnectWalletBtn");
        
        if (!publicKey) {
            closeDisconnectModal();
            return;
        }

        if(disconnectBtn) disconnectBtn.innerText = "Disconnecting...";

        try {
            const res = await fetch("/disconnect-wallet", {
                method: "POST",
                headers: {
                    "Accept": "application/json",
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": "{{ csrf_token() }}",
                },
                body: JSON.stringify({ address: publicKey }),
            });
            
            let data;
            try {
                data = await res.json();
            } catch (p) {
                console.error("JSON parser failed on disconnect", p);
            }

            if (!res.ok) {
                console.error("Disconnect rejected by server", data);
                toastr.error(data?.message || "Could not cleanly disconnect session.");
            }
        } catch(err) {
            console.error("Disconnect networking error", err);
        } finally {
            if(disconnectBtn) disconnectBtn.innerText = "Disconnect Wallet";
            
            // Clean client side state safely regardless of server error in this context
            localStorage.removeItem("connected_wallet");
            localStorage.removeItem("freighter_wallet");
            localStorage.removeItem("rabet_wallet");
            localStorage.removeItem("walletconnect_wallet");
            localStorage.removeItem("user_role");
            
            updateWalletUIStatus();
            closeDisconnectModal();
            toastr.info("Wallet Disconnected.");
            
            if(window.location.pathname.includes('/dashboard')) {
                window.location.href = '/';
            }
        }
    }

    // Save Creator Details
    async function saveCreatorDetails() {
        const name = document.getElementById('creatorName').value;
        const email = document.getElementById('creatorEmail').value;
        const blockchainId = document.getElementById('creatorBlockchainSelect').value;
        const walletTypeSelect = document.querySelector('.creatorWalletSelect');
        const walletType = walletTypeSelect.options[walletTypeSelect.selectedIndex]?.text || '';
        
        const connectedWallet = localStorage.getItem("connected_wallet");
        if (!connectedWallet) {
            toastr.warning("Please connect your wallet first.");
            openWalletModal();
            return;
        }

        const publicKey = localStorage.getItem(`${connectedWallet}_wallet`);
        const statusDiv = document.getElementById('creatorStatus');

        if (!name || !email || !blockchainId || !walletType) {
            toastr.error("Please fill all details.");
            return;
        }

        statusDiv.innerText = "Registering securely...";

        try {
            const response = await fetch("{{ route('creator.store') }}", {
                method: "POST",
                headers: {
                    "Accept": "application/json",
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": "{{ csrf_token() }}",
                },
                body: JSON.stringify({
                    name,
                    email,
                    blockchain_id: blockchainId,
                    wallet_type: walletType,
                    public_key: publicKey
                })
            });

            let data;
            try {
                data = await response.json();
            } catch (err) {
                console.error("Invalid response from creator registration:", err);
                statusDiv.innerText = "";
                toastr.error("Invalid server response. Please try again.");
                return;
            }

            if (!response.ok || !data.status) {
                statusDiv.innerText = "";
                console.warn("Creator rejection:", data);
                toastr.error(data.message || "Registration validation failed.");
                return;
            }

            toastr.success(data.message);
            localStorage.setItem("user_role", 'creator');
            statusDiv.innerText = "Redirecting to dashboard...";
            setTimeout(() => {
                window.location.href = `/dashboard/${publicKey}`;
            }, 1000);
            
        } catch (err) {
            console.error("Networking error on creator join:", err);
            statusDiv.innerText = "";
            toastr.error("A network error occurred. Please check your console.");
        }
    }
</script>
