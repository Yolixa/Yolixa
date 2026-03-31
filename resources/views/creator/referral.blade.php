@extends('layout.app')

@section('content')
<section class="min-h-screen flex items-center justify-center hero-bg pt-20 pb-10">
    <div class="max-w-4xl w-full mx-auto px-4 sm:px-6 lg:px-8">
        <div class="card-hover rounded-2xl p-8 md:p-12 border border-yolixa-purple/20 bg-gray-900/80 backdrop-blur-lg">
            <div class="text-center mb-10">
                <div class="w-24 h-24 gradient-bg rounded-full flex items-center justify-center mx-auto mb-6 shadow-glow">
                    <span class="text-3xl font-bold text-white">{{ strtoupper(substr($creator->name, 0, 1)) }}</span>
                </div>
                <h1 class="text-4xl font-black mb-2 gradient-text">{{ $creator->name }}</h1>
                <p class="text-gray-400 text-lg">Support this creator by sending a Web3 tip.</p>
                <div class="mt-4 flex items-center justify-center gap-2">
                    <span class="text-xs bg-gray-800 px-3 py-1 rounded-full text-gray-400 border border-gray-700">
                        {{ substr($creator->public_key, 0, 8) }}...{{ substr($creator->public_key, -8) }}
                    </span>
                    <button onclick="copyToClipboard('{{ $creator->public_key }}')" class="text-yolixa-blue hover:text-white transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                    </button>
                </div>
            </div>

            <div class="grid md:grid-cols-2 gap-10">
                <!-- Tip Form -->
                <div class="space-y-6">
                    <h3 class="text-2xl font-bold text-white mb-4">Send a Tip</h3>

                    <div>
                        <label class="block text-gray-300 mb-2 text-sm">Select Asset</label>
                        <div class="grid grid-cols-2 gap-4">
                            <button onclick="selectAsset('XLM')" id="assetXLM" class="asset-btn active-asset flex items-center justify-center gap-2 px-4 py-3 rounded-lg border border-gray-700 bg-gray-800 text-white hover:border-yolixa-purple transition-all">
                                <img src="{{ asset('assets/images/stellar-xlm-logo.png') }}" class="w-6 h-6" alt="XLM">
                                <span>XLM</span>
                            </button>
                            <button onclick="selectAsset('USDC')" id="assetUSDC" class="asset-btn flex items-center justify-center gap-2 px-4 py-3 rounded-lg border border-gray-700 bg-gray-800 text-white hover:border-yolixa-purple transition-all">
                                <img src="{{ asset('assets/images/usd-coin-usdc-logo.png') }}" class="w-6 h-6" alt="USDC">
                                <span>USDC</span>
                            </button>
                        </div>
                    </div>

                    <div>
                        <label for="tipAmount" class="block text-gray-300 mb-2 text-sm">Amount</label>
                        <div class="relative">
                            <input type="number" id="tipAmount" placeholder="0.00" step="any"
                                   class="w-full px-4 py-4 rounded-xl bg-gray-800/50 text-white border border-gray-700 focus:border-yolixa-purple focus:ring focus:ring-yolixa-purple/30 outline-none text-2xl font-bold">
                            <span id="assetLabel" class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 font-bold">XLM</span>
                        </div>
                    </div>

                    <button onclick="sendTip()" id="sendTipBtn"
                            class="w-full gradient-bg py-4 rounded-xl font-bold text-xl hover:scale-[1.02] transition-transform shadow-lg pulse-glow">
                        Send Tip Now
                    </button>

                    <p class="text-center text-xs text-gray-500 mt-4">
                        * A small platform fee of 1.5% applies to support the Yolixa ecosystem.
                    </p>
                </div>

                <!-- Info Card -->
                <div class="bg-gray-800/30 rounded-2xl p-6 border border-gray-700/50 flex flex-col justify-between">
                    <div>
                        <h4 class="text-xl font-bold mb-4 gradient-text">Why Tip with Yolixa?</h4>
                        <ul class="space-y-4">
                            <li class="flex items-start gap-3">
                                <div class="mt-1 w-5 h-5 bg-green-500/20 rounded-full flex items-center justify-center flex-shrink-0">
                                    <svg class="w-3 h-3 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                                </div>
                                <span class="text-gray-300 text-sm">Funds go directly to the creator's wallet.</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <div class="mt-1 w-5 h-5 bg-green-500/20 rounded-full flex items-center justify-center flex-shrink-0">
                                    <svg class="w-3 h-3 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                                </div>
                                <span class="text-gray-300 text-sm">Instant settlement (3-5 seconds).</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <div class="mt-1 w-5 h-5 bg-yolixa-purple/20 rounded-full flex items-center justify-center flex-shrink-0">
                                    <svg class="w-3 h-3 text-yolixa-purple" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" /></svg>
                                </div>
                                <span class="text-yolixa-purple text-sm font-bold">Bonus: You get YLX token rewards!</span>
                            </li>
                        </ul>
                    </div>

                    <div class="mt-8 pt-6 border-t border-gray-700">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-gray-400 text-sm">Creator Status</span>
                            <span class="text-green-500 text-xs font-bold px-2 py-0.5 bg-green-500/10 rounded-full">VERIFIED</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-400 text-sm">Network</span>
                            <span class="text-white text-xs font-bold">Stellar (Testnet)</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Tip Success Modal -->
<div id="successModal" class="fixed inset-0 bg-black/80 hidden items-center justify-center z-[100] backdrop-blur-sm">
    <div class="bg-gray-900 border border-yolixa-purple/30 p-8 rounded-2xl max-w-sm w-full text-center shadow-2xl">
        <div class="w-20 h-20 bg-green-500/20 rounded-full flex items-center justify-center mx-auto mb-6">
            <svg class="w-10 h-10 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
        </div>
        <h2 class="text-3xl font-bold text-white mb-2">Tip Sent!</h2>
        <p class="text-gray-400 mb-6">Your support has been sent to the creator. YLX rewards are on their way!</p>
        <button onclick="location.reload()" class="w-full gradient-bg py-3 rounded-lg font-bold">Awesome!</button>
    </div>
</div>

<style>
    .active-asset {
        border-color: #8b5cf6 !important;
        background-color: rgba(139, 92, 246, 0.1) !important;
    }
</style>

@endsection

@push('js')
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">

<script>
    let selectedAsset = 'XLM';

    function selectAsset(asset) {
        selectedAsset = asset;
        document.querySelectorAll('.asset-btn').forEach(btn => btn.classList.remove('active-asset'));
        document.getElementById('asset' + asset).classList.add('active-asset');
        document.getElementById('assetLabel').innerText = asset;
    }

    function copyToClipboard(text) {
        navigator.clipboard.writeText(text);
        toastr.success('Public key copied!');
    }

    async function sendTip() {
        const amount = document.getElementById('tipAmount').value;
        const receiver = "{{ $creator->public_key }}";
        const btn = document.getElementById('sendTipBtn');

        if (!amount || amount <= 0) {
            toastr.warning('Please enter valid amount.');
            return;
        }

        // Check wallet
        const connectedWallet = localStorage.getItem("connected_wallet");
        if (!connectedWallet) {
            toastr.info('Please connect your wallet first.');
            openWalletModal();
            return;
        }

        btn.disabled = true;
        btn.innerText = 'Initializing...';

        try {
            let txHash = null;

            if (connectedWallet === 'freighter') {
                txHash = await processFreighterTip(amount, receiver, selectedAsset);
            } else if (connectedWallet === 'rabet') {
                txHash = await processRabetTip(amount, receiver, selectedAsset);
            } else {
                toastr.error('Wallet not supported for direct tips yet.');
                btn.disabled = false;
                btn.innerText = 'Send Tip Now';
                return;
            }

            if (txHash) {
                await recordTip(txHash, amount, selectedAsset);
                document.getElementById('successModal').classList.remove('hidden');
                document.getElementById('successModal').classList.add('flex');
            }

        } catch (err) {
            console.error(err);
            toastr.error(err.message || 'Transaction failed.');
        } finally {
            btn.disabled = false;
            btn.innerText = 'Send Tip Now';
        }
    }

    async function processFreighterTip(amount, destination, assetCode) {
        // Build transaction logic usually happens via backend or a bridge
        // For simplicity in this demo, we'll call a backend endpoint to get XDR to sign
        const response = await fetch('/api/tip/build-xdr', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                amount,
                destination,
                asset: assetCode,
                sender: localStorage.getItem('freighter_wallet')
            })
        });

        const data = await response.json();
        if (!data.success) throw new Error(data.message);

        // Sign with Freighter (Requires 'TESTNET' inside an object for proper testnet signing)
        const signedTx = await window.freighterApi.signTransaction(data.xdr, { network: "TESTNET" });

        // Submit to Horizon
        const submitRes = await fetch('/api/tip/submit', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ signedXdr: signedTx })
        });

        const subData = await submitRes.json();
        if (!subData.success) throw new Error(subData.message);

        return subData.hash;
    }

    async function processRabetTip(amount, destination, assetCode) {
        if (typeof window.rabet === 'undefined') {
            throw new Error("Rabet wallet not found.");
        }

        const response = await fetch('/api/tip/build-xdr', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                amount,
                destination,
                asset: assetCode,
                sender: localStorage.getItem('rabet_wallet')
            })
        });

        const data = await response.json();
        if (!data.success) throw new Error(data.message);

        // Rabet expects 'mainnet' or 'testnet' in most versions
        const network = 'testnet'; 

        let signedTx;
        try {
            const result = await window.rabet.sign(data.xdr, network);
            signedTx = result.xdr;
        } catch (error) {
            console.error(error);
            throw new Error("Transaction signing was rejected.");
        }

        // Submit to Horizon
        const submitRes = await fetch('/api/tip/submit', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ signedXdr: signedTx })
        });

        const subData = await submitRes.json();
        if (!subData.success) throw new Error(subData.message);

        return subData.hash;
    }

    async function recordTip(txHash, amount, asset) {
        await fetch('/api/tip/record', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                tx_hash: txHash,
                amount: amount,
                asset: asset,
                receiver_id: "{{ $creator->id }}",
                sender_key: localStorage.getItem(localStorage.getItem('connected_wallet') + '_wallet')
            })
        });
    }
</script>
@endpush
