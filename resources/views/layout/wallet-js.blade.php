<script>
    document.addEventListener("DOMContentLoaded", function () {
        updateWalletUIStatus();
    });

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

    async function handleWalletLoginSuccess(publicKey, walletType, data) {
        localStorage.setItem(`${walletType}_wallet`, publicKey);
        localStorage.setItem("connected_wallet", walletType);
        localStorage.setItem("user_role", data.role || 'fan');
        
        updateWalletUIStatus();
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

    async function connectFreighter() {
        if (typeof window.freighterApi === "undefined") {
            toastr.error("Freighter not found.");
            return;
        }
        try {
            const result = await window.freighterApi.requestAccess();
            if (result && result.address) {
                const publicKey = result.address;
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
            toastr.error("User denied permission or connection failed.");
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
