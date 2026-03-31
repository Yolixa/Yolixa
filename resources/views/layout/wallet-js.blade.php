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
            walletButton.textContent = `${walletKey.slice(0, 5)}...${walletKey.slice(-4)}`;
            
            // Show dashboard link conditionally
            const role = localStorage.getItem("user_role");
            if (role === 'creator') {
                if(dashboardNavLink) dashboardNavLink.classList.remove('hidden');
                if(dashboardNavLink) dashboardNavLink.href = `/dashboard/${walletKey}`;
            } else {
                if(dashboardNavLink) dashboardNavLink.classList.add('hidden');
            }
        } else {
            walletButton.textContent = "Connect Wallet";
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
                fetch(`/get-wallets/${blockchainId}`)
                    .then(response => response.json())
                    .then(data => {
                        targetSelect.innerHTML = '<option value="">-- Select Wallet --</option>';
                        data.wallets.forEach(wallet => {
                            let option = document.createElement('option');
                            option.value = wallet.id;
                            option.textContent = wallet.name;
                            targetSelect.appendChild(option);
                        });
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
            console.error(err);
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
        const result = await window.freighterApi.requestAccess();
        if (result && result.address) {
            const publicKey = result.address;
            const res = await fetch("/save-wallet", {
                method: "POST",
                headers: {
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
            const data = await res.json();
            if (data.success) {
                handleWalletLoginSuccess(publicKey, 'freighter', data);
            }
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
                const data = await res.json();
                if (data.success) {
                  handleWalletLoginSuccess(publicKey, 'rabet', data);
                }
            }
        } catch (error) {
            console.error("Rabet connect error:", error);
            toastr.error("User denied permission or an error occurred.");
        }
    }

    async function disconnectWallet() {
        const connectedWallet = localStorage.getItem("connected_wallet");
        const publicKey = localStorage.getItem(`${connectedWallet}_wallet`);
        
        await fetch("/disconnect-wallet", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": "{{ csrf_token() }}",
            },
            body: JSON.stringify({ address: publicKey }),
        });
        
        localStorage.removeItem("connected_wallet");
        localStorage.removeItem("freighter_wallet");
        localStorage.removeItem("rabet_wallet");
        localStorage.removeItem("walletconnect_wallet");
        localStorage.removeItem("user_role");
        
        updateWalletUIStatus();
        closeDisconnectModal();
        toastr.info("Disconnected.");
        
        // If they were on dashboard, redirect to home
        if(window.location.pathname.includes('/dashboard')) {
            window.location.href = '/';
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

        statusDiv.innerText = "Registering...";

        try {
            const response = await fetch("{{ route('creator.store') }}", {
                method: "POST",
                headers: {
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

            const data = await response.json();
            if (data.status) {
                toastr.success(data.message);
                localStorage.setItem("user_role", 'creator');
                statusDiv.innerText = "Redirecting to dashboard...";
                setTimeout(() => {
                    window.location.href = `/dashboard/${publicKey}`;
                }, 1500);
            } else {
                statusDiv.innerText = "";
                toastr.error(data.message || "Registration failed.");
            }
        } catch (err) {
            console.error(err);
            statusDiv.innerText = "";
            toastr.error("An error occurred. check console.");
        }
    }
</script>
