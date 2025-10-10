@extends('layout.app')
@section('content')
<!-- Hero Section -->
<section class="relative min-h-screen flex items-center justify-center hero-bg pt-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <div class="floating">
            <h1 class="text-5xl md:text-7xl font-black mb-6">
                Empower Your <span class="gradient-text">Influence</span>
            </h1>
            <p class="text-xl md:text-2xl text-gray-300 mb-8 max-w-3xl mx-auto">
                Yolixa is the first Web3 tipping platform on Stellar, powered by the YLX token.
                Creators earn instant crypto tips + bonus rewards, while fans also get rewarded for their support.
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <button onclick="openCreatorModal()" class="gradient-bg px-8 py-4 rounded-lg font-semibold text-lg hover:scale-105 transition-transform pulse-glow">
                    Join as Creator
                </button>
                <a href="{{ route('whitepaper') }}" target="_blank" rel="noopener noreferrer"
                    class="border border-yolixa-purple px-8 py-4 rounded-lg font-semibold text-lg hover:bg-yolixa-purple/10 transition-colors">
                    Read Whitepaper
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Wallet Modal -->
<div id="walletModal" class="fixed inset-0 bg-black/60 hidden items-center justify-center z-50">
    <div class="bg-gray-900 rounded-2xl shadow-xl max-w-md w-full p-6 relative">

        <!-- Close Button -->
        <button onclick="closeWalletModal()"
            class="absolute top-2 right-4 text-gray-400 hover:text-white">
            ✕
        </button>

        <!-- Wallet Connect Section -->
        <div id="walletConnect">
            <!-- Modal Header -->
            <h2 class="text-2xl font-bold mb-4 gradient-text text-center">Connect Your Wallet</h2>
            <p class="text-gray-400 text-center mb-6">
                Select your blockchain and wallet to connect instantly.
            </p>

            <!-- Select Blockchain -->
            <div class="mb-4">
                <label for="walletBlockchainSelect" class="block text-gray-300 mb-2">Select Blockchain</label>
                <select id="walletBlockchainSelect"
                    class="w-full px-4 py-3 rounded-lg bg-gray-800 text-white border border-gray-700 focus:border-yolixa-purple focus:ring focus:ring-yolixa-purple/30 outline-none walletBlockchainSelect">
                    <option value="">-- Choose Blockchain --</option>
                    @foreach($blockchains as $blockchain)
                    <option value="{{ $blockchain->id }}">
                        {{ $blockchain->name }} ({{ $blockchain->symbol }})
                    </option>
                    @endforeach
                </select>
            </div>

            <!-- Select Wallet -->
            <div class="mb-4">
                <label for="walletWalletSelect" class="block text-gray-300 mb-2">Select Wallet</label>
                <select id="walletWalletSelect"
                    class="walletWalletSelect w-full mt-4 px-4 py-3 rounded-lg bg-gray-800 text-white border border-gray-700">
                    <option value="">-- Select Wallet --</option>
                </select>
            </div>

            <!-- Connect Button -->
            <button id="connectWalletBtn" onclick="connectWallet()"
                class="w-full gradient-bg px-6 py-3 rounded-lg font-semibold text-lg hover:scale-105 transition-transform">
                Connect
            </button>
        </div>

        <!-- Wallet Disconnect Section -->
        <div id="walletDisconnect" class="mt-5 hidden">
            <button id="disconnectWalletBtn" onclick="disconnectWallet()" class="w-full gradient-bg px-6 py-3 rounded-lg font-semibold text-lg hover:scale-105 transition-transform">
                Disconnect Wallet
            </button>
        </div>
    </div>
</div>

<!-- Creator Join Modal -->
<div id="creatorModal" class="fixed inset-0 bg-black/60 hidden items-center justify-center z-50">
    <div class="bg-gray-900 rounded-2xl shadow-xl max-w-md w-full p-6 relative">

        <!-- Close Button -->
        <button onclick="closeCreatorModal()"
            class="absolute top-4 right-4 text-gray-400 hover:text-white">
            ✕
        </button>

        <!-- Modal Header -->
        <h2 class="text-2xl font-bold mb-4 gradient-text text-center">Join as Creator</h2>
        <p class="text-gray-400 text-center mb-6">
            Fill the details below to register as a creator.
        </p>

        <!-- Name Input -->
        <div class="mb-4">
            <label for="creatorName" class="block text-gray-300 mb-2">Full Name</label>
            <input type="text" id="creatorName" placeholder="John Doe"
                class="w-full px-4 py-3 rounded-lg bg-gray-800 text-white border border-gray-700 focus:border-yolixa-purple focus:ring focus:ring-yolixa-purple/30 outline-none" />
        </div>

        <!-- Email Input -->
        <div class="mb-4">
            <label for="creatorEmail" class="block text-gray-300 mb-2">Email</label>
            <input type="email" id="creatorEmail" placeholder="example@email.com"
                class="w-full px-4 py-3 rounded-lg bg-gray-800 text-white border border-gray-700 focus:border-yolixa-purple focus:ring focus:ring-yolixa-purple/30 outline-none" />
        </div>

        <!-- Select Blockchain -->
        <div class="mb-4">
            <label for="creatorBlockchainSelect" class="block text-gray-300 mb-2">Select Blockchain</label>
            <select id="creatorBlockchainSelect"
                class="w-full px-4 py-3 rounded-lg bg-gray-800 text-white border border-gray-700 focus:border-yolixa-purple focus:ring focus:ring-yolixa-purple/30 outline-none creatorBlockchainSelect">
                <option value="">-- Choose Blockchain --</option>
                @foreach($blockchains as $blockchain)
                <option value="{{ $blockchain->id }}">
                    {{ $blockchain->name }} ({{ $blockchain->symbol }})
                </option>
                @endforeach
            </select>
        </div>


        <!-- Select Wallet -->
        <div class="mb-4">
            <label for="creatorWalletSelect" class="block text-gray-300 mb-2">Select Wallet</label>
            <select class="creatorWalletSelect w-full mt-4 px-4 py-3 rounded-lg bg-gray-800 text-white border border-gray-700">
                <option value="">-- Select Wallet --</option>
            </select>
        </div>

        <!-- Join Button -->
        <button onclick="saveCreatorDetails()"
            class="w-full gradient-bg px-6 py-3 rounded-lg font-semibold text-lg hover:scale-105 transition-transform">
            Join as Creator
        </button>
    </div>
</div>

<!-- Features Section -->
<section id="features" class="py-20 bg-gray-900/50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <h2 class="text-4xl md:text-5xl font-bold mb-4">
                Why Choose <span class="gradient-text">Yolixa</span>?
            </h2>
            <p class="text-xl text-gray-300 max-w-2xl mx-auto">
                The first Web3 tipping ecosystem where both creators and supporters earn. Powered by Stellar & YLX token.
            </p>
        </div>

        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
            <!-- Feature 1 -->
            <div class="card-hover rounded-xl p-8">
                <div class="w-16 h-16 gradient-bg rounded-lg flex items-center justify-center mb-6">
                    <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                    </svg>
                </div>
                <h3 class="text-2xl font-bold mb-4">Instant Tips</h3>
                <p class="text-gray-300">
                    Get crypto tips instantly with Stellar-powered wallet-to-wallet transfers. No delays, no hidden cuts.
                </p>
            </div>

            <!-- Feature 2 -->
            <div class="card-hover rounded-xl p-8">
                <div class="w-16 h-16 gradient-bg rounded-lg flex items-center justify-center mb-6">
                    <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h3 class="text-2xl font-bold mb-4">Secure & Transparent</h3>
                <p class="text-gray-300">
                    Built on Stellar blockchain with automatic trustline setup. Every transaction is secure and verifiable.
                </p>
            </div>

            <!-- Feature 3 -->
            <div class="card-hover rounded-xl p-8">
                <div class="w-16 h-16 gradient-bg rounded-lg flex items-center justify-center mb-6">
                    <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
                <h3 class="text-2xl font-bold mb-4">YLX Rewards</h3>
                <p class="text-gray-300">
                    Every tip converts into YLX tokens. Creators earn bonus rewards, and fans also get incentives for tipping.
                </p>
            </div>

            <!-- Feature 4 -->
            <div class="card-hover rounded-xl p-8">
                <div class="w-16 h-16 gradient-bg rounded-lg flex items-center justify-center mb-6">
                    <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.367 2.684 3 3 0 00-5.367-2.684z" />
                    </svg>
                </div>
                <h3 class="text-2xl font-bold mb-4">One-Click Sharing</h3>
                <p class="text-gray-300">
                    Each creator gets a unique referral link to share anywhere. Fans can tip directly without signup.
                </p>
            </div>

            <!-- Feature 5 -->
            <div class="card-hover rounded-xl p-8">
                <div class="w-16 h-16 gradient-bg rounded-lg flex items-center justify-center mb-6">
                    <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h3 class="text-2xl font-bold mb-4">No Withdraw Hassle</h3>
                <p class="text-gray-300">
                    Tips go straight to your Stellar wallet. No withdrawal requests, no waiting periods—your funds are yours instantly.
                </p>
            </div>

            <!-- Feature 6 -->
            <div class="card-hover rounded-xl p-8">
                <div class="w-16 h-16 gradient-bg rounded-lg flex items-center justify-center mb-6">
                    <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                    </svg>
                </div>
                <h3 class="text-2xl font-bold mb-4">Beginner-Friendly</h3>
                <p class="text-gray-300">
                    From signup to first tip, the process is simple and intuitive. No technical knowledge required.
                </p>
            </div>

            <!-- Feature 7 -->
            <div class="card-hover rounded-xl p-8">
                <div class="w-16 h-16 gradient-bg rounded-lg flex items-center justify-center mb-6">
                    <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 1C5.925 1 1 5.925 1 12s4.925 11 11 11 11-4.925 11-11S18.075 1 12 1zm0 2c4.963 0 9 4.037 9 9s-4.037 9-9 9-9-4.037-9-9 4.037-9 9-9zm-1 4v5H7v2h6V7h-2z" />
                    </svg>
                </div>
                <h3 class="text-2xl font-bold mb-4">Minimal Fees</h3>
                <p class="text-gray-300">
                    Creators keep more of what they earn. Yolixa charges only 1–2% for sustainability—far lower than traditional platforms.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- How It Works Section -->
<section id="how-it-works" class="py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <h2 class="text-4xl md:text-5xl font-bold mb-4">
                How It <span class="gradient-text">Works</span>
            </h2>
            <p class="text-xl text-gray-300 max-w-2xl mx-auto">
                Start earning crypto tips in just three simple steps
            </p>
        </div>

        <div class="grid md:grid-cols-3 gap-8">
            <!-- Step 1 -->
            <div class="text-center">
                <div class="w-20 h-20 gradient-bg rounded-full flex items-center justify-center mx-auto mb-6 text-2xl font-bold">
                    1
                </div>
                <h3 class="text-2xl font-bold mb-4">Create Your Account</h3>
                <p class="text-gray-300">
                    Sign up on Yolixa and link your Stellar wallet. Our platform
                    automatically sets up trustlines so you’re ready to receive tips.
                </p>
            </div>

            <!-- Step 2 -->
            <div class="text-center">
                <div class="w-20 h-20 gradient-bg rounded-full flex items-center justify-center mx-auto mb-6 text-2xl font-bold">
                    2
                </div>
                <h3 class="text-2xl font-bold mb-4">Share Your Tip Link</h3>
                <p class="text-gray-300">
                    Get your unique tipping link and share it with your fans across
                    Twitter (X), Telegram, Reddit, Discord, or any platform you use.
                </p>
            </div>

            <!-- Step 3 -->
            <div class="text-center">
                <div class="w-20 h-20 gradient-bg rounded-full flex items-center justify-center mx-auto mb-6 text-2xl font-bold">
                    3
                </div>
                <h3 class="text-2xl font-bold mb-4">Earn Instantly</h3>
                <p class="text-gray-300">
                    Followers send tips directly to your Stellar wallet—no delays, no
                    withdrawal requests. Plus, both you and your fans earn YLX rewards.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Roadmap Section -->
<section id="roadmap" class="py-20 bg-gray-900/50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <h2 class="text-4xl md:text-5xl font-bold mb-4">
                Our <span class="gradient-text">Roadmap</span>
            </h2>
            <p class="text-xl text-gray-300 max-w-2xl mx-auto">
                Step-by-step milestones to scale Yolixa and empower creators globally
            </p>
        </div>

        <div class="grid md:grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Roadmap Item 1 -->
            <div class="card-hover rounded-xl p-8">
                <div class="flex items-center mb-4">
                    <div class="w-3 h-3 bg-yolixa-blue rounded-full mr-3"></div>
                    <span class="text-yolixa-purple font-semibold">Q1 2025</span>
                </div>
                <h3 class="text-xl font-bold mb-3">Core Tipping Platform</h3>
                <p class="text-gray-300">
                    Launch of Yolixa tipping platform with Stellar integration, referral
                    links, and seamless wallet-to-wallet transfers.
                </p>
            </div>

            <!-- Roadmap Item 2 -->
            <div class="card-hover rounded-xl p-8">
                <div class="flex items-center mb-4">
                    <div class="w-3 h-3 bg-yolixa-blue rounded-full mr-3"></div>
                    <span class="text-yolixa-blue font-semibold">Q3 2025</span>
                </div>
                <h3 class="text-xl font-bold mb-3">Rewards & Liquidity</h3>
                <p class="text-gray-300">
                    YLX rewards system launch. Users earn bonuses on every tip. Liquidity
                    pools introduced for token stability and trading incentives.
                </p>
            </div>

            <!-- Roadmap Item 3 -->
            <div class="card-hover rounded-xl p-8">
                <div class="flex items-center mb-4">
                    <div class="w-3 h-3 bg-yolixa-purple rounded-full mr-3"></div>
                    <span class="text-yolixa-purple font-semibold">Q1 2026</span>
                </div>
                <h3 class="text-xl font-bold mb-3">Staking & Analytics</h3>
                <p class="text-gray-300">
                    Launch of staking to earn passive rewards. Advanced creator analytics
                    to track tips, growth, and audience engagement in real time.
                </p>
            </div>

            <!-- Roadmap Item 4 -->
            <div class="card-hover rounded-xl p-8">
                <div class="flex items-center mb-4">
                    <div class="w-3 h-3 bg-gray-500 rounded-full mr-3"></div>
                    <span class="text-gray-500 font-semibold">Q3–Q4 2026</span>
                </div>
                <h3 class="text-xl font-bold mb-3">Global Expansion</h3>
                <p class="text-gray-300">
                    Cross-chain support, AI-driven personalization, DeFi-based features,
                    and enterprise partnerships to scale Yolixa worldwide.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="py-20">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-4xl md:text-5xl font-bold mb-6">
            Ready to <span class="gradient-text">Boost Your Earnings</span>?
        </h2>
        <p class="text-xl text-gray-300 mb-8">
            Be among the first creators to join Yolixa, connect your Stellar wallet,
            and start receiving tips, bonuses, and rewards directly from your fans.
        </p>
        <button onclick="openCreatorModal()" class="gradient-bg px-12 py-4 rounded-lg font-semibold text-xl hover:scale-105 transition-transform glow-effect">
            Create Your Account
        </button>
    </div>
</section>
@endsection
@push('js')
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const walletKey = localStorage.getItem("freighter_wallet");
        const walletButton = document.getElementById("connectWalletBtn");
        const walletConnect = document.getElementById("walletConnect");
        const walletDisconnect = document.getElementById("walletDisconnect");

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

    document.addEventListener('change', function(e) {
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

                        // Wallet dropdown ko populate karna
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

    async function connectWallet() {
        const blockchainSelect = document.querySelector('.walletBlockchainSelect');
        const walletSelect = document.querySelector('.walletWalletSelect');
        const connectWalletBtn = document.getElementById("connectWalletBtn");

        const blockchainId = blockchainSelect.value;
        const walletId = walletSelect.value;

        if (!blockchainId || !walletId) {
            alert("Please select both blockchain and wallet first.");
            return;
        }

        // connectWalletBtn.classList.textContent("")

        // backend se wallet name get karo (agar backend already wallet ka name send karta hai to directly use karo)
        let walletName = walletSelect.options[walletSelect.selectedIndex].text.toLowerCase();

        try {
            if (walletName.includes("freighter")) {
                await connectFreighterWallet();
            } else if (walletName.includes("rabet")) {
                await connectRabetWallet();
            } else {
                alert("Wallet not supported yet.");
            }
        } catch (err) {
            console.error("Wallet connection error:", err);
            alert("Failed to connect wallet. See console for details.");
        }
    }

    //Freighter Start
    async function connectFreighterWallet() {
        const walletBtn = document.getElementById("connectWalletBtn");
        const walletModal = document.getElementById("walletModal");
        const walletConnect = document.getElementById("walletConnect");
        const walletDisconnect = document.getElementById("walletDisconnect");

        try {
            if (typeof window.freighterApi === "undefined") {
                toastr.error("Freighter wallet extension not installed.", "Error");
                return;
            }

            const isConnected = await window.freighterApi.isConnected();
            if (!isConnected) {
                toastr.info("Opening Freighter... Please login and connect.", "Connecting");
            }

            const result = await window.freighterApi.requestAccess();

            if (!result || !result.address) {
                toastr.warning("User cancelled wallet connection.", "Freighter Wallet");
                return;
            }

            const publicKey = result.address;
            const isActive = await checkStellarAccountStatus(publicKey);

            const res = await fetch("/save-wallet", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({
                    address: publicKey,
                    status: isActive ? 1 : 0,
                }),
            });

            const data = await res.json();

            if (data.success) {
                localStorage.setItem("freighter_wallet", publicKey);
                walletModal.classList.add("hidden");
                walletBtn.innerText = `${publicKey.slice(0, 5)}...${publicKey.slice(-4)}`;
                walletConnect.classList.add("hidden");
                walletDisconnect.classList.remove("hidden");
                toastr.success(data.message || "Wallet connected successfully!", "Freighter Wallet");
            } else {
                toastr.error(data.message || "Failed to save wallet.", "Error");
            }

        } catch (error) {
            console.error("Wallet connection error:", error);

            if (error.message?.includes("locked") || error.message?.includes("User declined")) {
                toastr.error("Please unlock or login to Freighter first.", "Freighter Locked");
            } else if (error.name === "TypeError") {
                toastr.error("Connection failed — make sure Freighter is unlocked.", "Error");
            } else {
                toastr.error("Failed to connect wallet. Try again.", "Error");
            }
        }
    }

    async function checkStellarAccountStatus(publicKey) {
        try {
            const response = await fetch(`https://horizon.stellar.org/accounts/${publicKey}`);
            if (response.status === 404) {
                return false; // account not found
            }
            const data = await response.json();
            return data?.balances?.length > 0; // account exists
        } catch (error) {
            console.error("Error checking account status:", error);
            toastr.error("Unable to verify account status. Check internet connection.", "Network Error");
            return false;
        }
    }
    //Freighter End

    // ✅ Rabet
    async function connectRabetWallet() {
        const rabet = window.rabet || window.Rabet;
        if (!rabet) {
            alert("Rabet not installed.");
            return;
        }

        let result;
        if (typeof rabet.connect === "function") {
            result = await rabet.connect();
        } else if (typeof rabet.requestAccount === "function") {
            result = await rabet.requestAccount();
        }

        const publicKey = result?.publicKey || result;
        if (!publicKey) {
            alert("Could not get public key from Rabet.");
            return;
        }

        console.log("✅ Rabet Connected:", publicKey);
        alert("Rabet Connected: " + publicKey);
    }

    // Wallet Disconnect
    async function disconnectWallet() {
        try {
            // Local storage se wallet key delete karo
            localStorage.removeItem("freighter_wallet");

            // UI update karo
            const walletButton = document.getElementById("connectWalletBtn");
            const walletConnect = document.getElementById("walletConnect");
            const walletDisconnect = document.getElementById("walletDisconnect");
            const walletModal = document.getElementById("walletModal");

            walletButton.textContent = "Connect Wallet";
            walletConnect.classList.remove("hidden");
            walletDisconnect.classList.add("hidden");
            walletModal.classList.add("hidden");

            // Success message
            toastr.success("Wallet disconnected successfully.");

        } catch (error) {
            console.error("Error disconnecting wallet:", error);
            toastr.error("Failed to disconnect wallet. Try again.", "Error");
        }
    }
</script>
@endpush
