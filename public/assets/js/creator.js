// public/js/creator.js
async function saveCreatorDetails() {
    const name = $("#creatorName").val().trim();
    const email = $("#creatorEmail").val().trim();
    const blockchain = $("#creatorBlockchainSelect").val();
    const walletType = $(".creatorWalletSelect").val();
    const statusBox = $("#creatorStatus");

    if (!name || !email || !blockchain || !walletType) {
        toastr.warning("Please fill all required fields.");
        return;
    }
    
    let publicKey = null;
   if (parseInt(walletType) === 1) {
        // ID 1 = Freighter
        publicKey = await connectFreighter();
    } 
    else if (parseInt(walletType) === 2) {
        // ID 2 = Rabet
        publicKey = await connectRabet();
    } 
    else {
        return toastr.error("Unsupported wallet type selected.");
    }
    
    if (!publicKey) return;
    
    statusBox.text("Creating trustline...");
    const trustHash = await createTrustline(publicKey);
    if (!trustHash) return toastr.error("Trustline failed!");

    statusBox.text("Saving creator...");

    const payload = {
        name,
        email,
        blockchain_id: blockchain,
        wallet_type: walletType,
        public_key: publicKey,
        trust_tx_hash: trustHash,
    };

    try {
        const res = await fetch("/creator/register", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify(payload),
        });

        const data = await res.json();
        if (data.status) {
            toastr.success("Creator registered!");
            $("#creatorReferralLink").text(data.referral_url);
            $("#creatorSuccessToast").removeClass("hidden");
        } else {
            toastr.error(data.message || "Registration failed!");
        }
    } catch (err) {
        console.error(err);
        toastr.error("Server error while saving creator.");
    }
}

document.addEventListener('change', function(e) {
    console.log('Changed element:', e.target); // Debug line
    if (e.target.classList.contains('creatorBlockchainSelect')) {
        let blockchainId = e.target.value;

        if (blockchainId) {
            fetch(`/get-wallets/${blockchainId}`, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                console.log('Wallets:', data);

                let walletSelect = document.querySelector('.creatorWalletSelect');
                walletSelect.innerHTML = '<option value="">-- Select Wallet --</option>';

                if (data.wallets && data.wallets.length > 0) {
                    data.wallets.forEach(wallet => {
                        let option = document.createElement('option');
                        option.value = wallet.id;
                        option.textContent = wallet.name;
                        walletSelect.appendChild(option);
                    });
                } else {
                    walletSelect.innerHTML = '<option value="">No wallets available</option>';
                }
            })
            .catch(error => console.error('Error fetching wallets:', error));
        }
    } else if (e.target.classList.contains('walletBlockchainSelect')) {
        let blockchainId = e.target.value;

        if (blockchainId) {
            fetch(`/get-wallets/${blockchainId}`, {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    console.log('Wallets:', data);

                    // Wallet dropdown ko populate karna
                    let walletSelect = document.querySelector('.walletWalletSelect');
                    walletSelect.innerHTML = '<option value="">-- Select Wallet --</option>';

                    if (data.wallets && data.wallets.length > 0) {
                        data.wallets.forEach(wallet => {
                            let option = document.createElement('option');
                            option.value = wallet.id;
                            option.textContent = wallet.name;
                            walletSelect.appendChild(option);
                        });
                    } else {
                        walletSelect.innerHTML = '<option value="">No wallets available</option>';
                    }
                })
                .catch(error => console.error('Error fetching wallets:', error));
        }
    }
});
