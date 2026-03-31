<!-- Wallet Modal -->
<div id="walletModal" class="fixed inset-0 bg-black/60 hidden items-center justify-center z-[100]">
    <div class="bg-gray-900 rounded-2xl shadow-xl max-w-md w-full p-6 relative backdrop-blur-md">

        <!-- Close Button -->
        <button onclick="closeWalletModal()"
            class="absolute top-2 right-4 text-gray-400 hover:text-white">
            ✕
        </button>

        <!-- Wallet Connect Section -->
        <div id="walletConnect">
            <h2 class="text-xl font-bold mb-4 gradient-text text-center">Connect Your Wallet</h2>
            <p class="text-gray-400 text-center mb-6 text-sm">Select your blockchain and wallet to connect instantly.</p>

            <div class="mb-4">
                <label for="walletBlockchainSelect" class="block text-gray-300 mb-2 text-sm">Select Blockchain</label>
                <select id="walletBlockchainSelect" class="walletBlockchainSelect w-full px-4 py-3 rounded-lg bg-gray-800 text-white border border-gray-700 focus:border-yolixa-purple outline-none">
                    <option value="">-- Choose Blockchain --</option>
                    @foreach($blockchains as $blockchain)
                    <option value="{{ $blockchain->id }}">
                        {{ $blockchain->name }} ({{ $blockchain->symbol }})
                    </option>
                    @endforeach
                </select>
            </div>

            <div class="mb-4">
                <label for="walletWalletSelect" class="block text-gray-300 mb-2 text-sm">Select Wallet</label>
                <select id="walletWalletSelect" class="walletWalletSelect w-full px-4 py-3 rounded-lg bg-gray-800 text-white border border-gray-700 outline-none">
                    <option value="">-- Select Wallet --</option>
                </select>
            </div>

            <button id="modalWalletBtn" onclick="connectWallet()" class="w-full gradient-bg px-6 py-3 rounded-lg font-semibold text-lg hover:scale-105 transition-transform">
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

<!-- Disconnect Modal Confirmation -->
<div id="disconnectModal" class="fixed inset-0 bg-black/60 hidden items-center justify-center z-[110]">
    <div class="bg-gray-900 rounded-2xl shadow-xl max-w-sm w-full p-6 relative">
        <button onclick="closeDisconnectModal()" class="absolute top-3 right-4 text-gray-400 hover:text-white">✕</button>
        <h2 class="text-xl font-bold mb-4 gradient-text text-center">Disconnect Wallet</h2>
        <p class="text-gray-400 text-center mb-6">Are you sure you want to disconnect your wallet?</p>
        <div class="flex gap-4 justify-center">
            <button onclick="disconnectWallet()" class="gradient-bg px-6 py-2 rounded-lg font-semibold hover:scale-105 transition">Yes</button>
            <button onclick="closeDisconnectModal()" class="border border-gray-600 px-6 py-2 rounded-lg font-semibold hover:bg-gray-700 transition">Cancel</button>
        </div>
    </div>
</div>

<!-- Creator Join Modal -->
<div id="creatorModal" class="fixed inset-0 bg-black/60 hidden items-center justify-center z-[100]">
    <div class="bg-gray-900 rounded-2xl shadow-2xl max-w-md w-full p-6 relative border border-yolixa-purple/20">
        <button onclick="closeCreatorModal()" class="absolute top-4 right-4 text-gray-400 hover:text-white">✕</button>
        
        <div class="text-center mb-6">
            <h2 class="text-2xl font-bold gradient-text mb-2">Join as Creator</h2>
            <p class="text-gray-400 text-sm">Start receiving Web3 tips instantly.</p>
        </div>

        <div class="space-y-4">
            <div>
                <label class="block text-gray-300 mb-2 text-sm">Full Name</label>
                <input type="text" id="creatorName" class="w-full px-4 py-3 rounded-lg bg-gray-800 text-white border border-gray-700 outline-none">
            </div>
            <div>
                <label class="block text-gray-300 mb-2 text-sm">Email</label>
                <input type="email" id="creatorEmail" class="w-full px-4 py-3 rounded-lg bg-gray-800 text-white border border-gray-700 outline-none">
            </div>
            <div>
                <label class="block text-gray-300 mb-2 text-sm">Blockchain</label>
                <select id="creatorBlockchainSelect" class="creatorBlockchainSelect w-full px-4 py-3 rounded-lg bg-gray-800 text-white border border-gray-700 outline-none">
                    <option value="">-- Choose Blockchain --</option>
                    @foreach($blockchains as $blockchain)
                        <option value="{{ $blockchain->id }}">{{ $blockchain->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-gray-300 mb-2 text-sm">Wallet</label>
                <select class="creatorWalletSelect w-full px-4 py-3 rounded-lg bg-gray-800 text-white border border-gray-700 outline-none">
                    <option value="">-- Choose Wallet --</option>
                </select>
            </div>
        </div>

        <div id="creatorStatus" class="text-center text-sm text-gray-400 mt-4"></div>

        <button onclick="saveCreatorDetails()" class="w-full gradient-bg mt-6 py-3 rounded-lg font-semibold text-lg hover:scale-105 transition-transform pulse-glow">
            Join as Creator
        </button>
    </div>
</div>
