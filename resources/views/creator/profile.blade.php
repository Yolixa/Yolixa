@extends('layout.app')

@section('content')
<section class="min-h-screen hero-bg pt-24 pb-16">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Creator Header -->
        <div class="relative bg-gray-900/60 backdrop-blur-lg border border-yolixa-purple/20 rounded-2xl overflow-hidden shadow-2xl mb-10">
            <!-- Cover Image (Placeholder for now) -->
            <div class="h-48 w-full bg-gradient-to-r from-yolixa-blue/40 to-yolixa-purple/40"></div>
            
            <div class="px-8 pb-8 flex flex-col md:flex-row items-center md:items-end gap-6 -mt-16 relative z-10">
                <div class="w-32 h-32 gradient-bg rounded-full flex items-center justify-center shadow-glow border-4 border-gray-900 flex-shrink-0">
                    <span class="text-5xl font-bold text-white">{{ strtoupper(substr($creator->name, 0, 1)) }}</span>
                </div>
                
                <div class="flex-grow text-center md:text-left">
                    <h1 class="text-4xl font-black mb-1 text-white">{{ $creator->name }}</h1>
                    <p class="text-yolixa-purple font-medium text-lg">{{ '@' . $creator->username }}</p>
                    <p class="text-gray-400 mt-2 max-w-2xl">{{ $creator->bio ?? 'Welcome to my creator page! Support my work with a Web3 tip.' }}</p>
                </div>
                
                <div class="flex items-center gap-3">
                    <span class="text-xs bg-gray-800 px-3 py-1.5 rounded-full text-gray-400 border border-gray-700 flex items-center gap-2">
                        <svg class="w-4 h-4 text-yolixa-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" /></svg>
                        {{ substr($creator->public_key, 0, 6) }}...{{ substr($creator->public_key, -4) }}
                    </span>
                </div>
            </div>
        </div>

        <div class="grid lg:grid-cols-12 gap-8">
            
            <!-- Left Column: Tipping & Goals -->
            <div class="lg:col-span-7 space-y-8">
                
                <!-- Support Goal Widget -->
                @if($creator->goal_amount > 0)
                <div class="bg-gray-900/80 backdrop-blur-lg border border-yolixa-purple/20 rounded-2xl p-8 shadow-lg">
                    <div class="flex justify-between items-end mb-4">
                        <div>
                            <h3 class="text-xl font-bold text-white mb-1">{{ $creator->goal_title ?? 'Creator Goal' }}</h3>
                            <p class="text-sm text-gray-400">Help me reach my target!</p>
                        </div>
                        <div class="text-right">
                            <span class="text-2xl font-bold gradient-text">{{ number_format($convertedTotal, 0) }} YLX</span>
                            <span class="text-sm text-gray-500">/ {{ number_format($creator->goal_amount, 0) }} YLX</span>
                        </div>
                    </div>
                    
                    <div class="w-full bg-gray-800 rounded-full h-3 overflow-hidden">
                        @php
                            $progress = min(100, ($convertedTotal / max(1, $creator->goal_amount)) * 100);
                        @endphp
                        <div class="gradient-bg h-3 rounded-full relative" style="width: {{ $progress }}%">
                            <div class="absolute inset-0 bg-white/20 animate-pulse"></div>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Tipping Widget -->
                <div class="card-hover rounded-2xl p-8 border border-yolixa-purple/20 bg-gray-900/80 backdrop-blur-lg">
                    <h3 class="text-2xl font-bold text-white mb-6">Send Support</h3>

                    <div class="space-y-6">
                        <!-- Asset Selection -->
                        <div>
                            <label class="block text-gray-300 mb-3 text-sm font-semibold">Select Currency</label>
                            <div class="grid grid-cols-3 gap-3">
                                <button onclick="selectAsset('XLM')" id="assetXLM" class="asset-btn active-asset flex items-center justify-center gap-2 px-3 py-3 rounded-xl border border-gray-700 bg-gray-800 text-white hover:border-yolixa-purple transition-all">
                                    <img src="{{ asset('assets/images/stellar-xlm-logo.png') }}" class="w-5 h-5" alt="XLM">
                                    <span class="font-bold">XLM</span>
                                </button>
                                <button onclick="selectAsset('USDC')" id="assetUSDC" class="asset-btn flex items-center justify-center gap-2 px-3 py-3 rounded-xl border border-gray-700 bg-gray-800 text-white hover:border-yolixa-purple transition-all">
                                    <img src="{{ asset('assets/images/usd-coin-usdc-logo.png') }}" class="w-5 h-5" alt="USDC">
                                    <span class="font-bold">USDC</span>
                                </button>
                                <button onclick="selectAsset('YLX')" id="assetYLX" class="relative asset-btn flex items-center justify-center gap-2 px-3 py-3 rounded-xl border border-gray-700 bg-gray-800 text-white hover:border-yolixa-purple transition-all">
                                    <span class="absolute -top-2 -right-2 bg-gradient-to-r from-yolixa-purple to-yolixa-blue text-[9px] font-bold px-1.5 py-0.5 rounded shadow-lg pulse-glow">0% Fee</span>
                                    <div class="w-5 h-5 rounded-full flex items-center justify-center shadow-glow text-[9px] font-bold text-white">🔥</div>
                                    <span class="font-bold">YLX</span>
                                </button>
                            </div>
                        </div>

                        <!-- Amount -->
                        <div>
                            <label for="tipAmount" class="block text-gray-300 mb-3 text-sm font-semibold">Amount</label>
                            <div class="grid grid-cols-4 gap-2 mb-3 hidden" id="quickAmounts">
                                <!-- Could populate dynamically based on asset later -->
                            </div>
                            <div class="relative">
                                <input type="number" id="tipAmount" placeholder="0.00" step="any" oninput="debouncePreview()"
                                       class="w-full px-4 py-4 rounded-xl bg-gray-800/50 text-white border border-gray-700 focus:border-yolixa-purple focus:ring focus:ring-yolixa-purple/30 outline-none text-2xl font-bold">
                                <span id="assetLabel" class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 font-bold">XLM</span>
                            </div>
                        </div>

                        <!-- Custom Message -->
                        <div>
                            <label for="tipMessage" class="block text-gray-300 mb-2 text-sm font-semibold">Message (Optional)</label>
                            <textarea id="tipMessage" rows="2" placeholder="Say something nice..." class="w-full px-4 py-3 rounded-xl bg-gray-800/50 text-white border border-gray-700 focus:border-yolixa-purple focus:ring focus:ring-yolixa-purple/30 outline-none resize-none"></textarea>
                        </div>
                        
                        <div class="flex items-center gap-4">
                            <div class="flex-grow">
                                <input type="text" id="senderName" placeholder="Your Name (Optional)" class="w-full px-4 py-3 rounded-xl bg-gray-800/50 text-white border border-gray-700 focus:border-yolixa-purple outline-none">
                            </div>
                            <label class="flex items-center gap-2 cursor-pointer text-sm text-gray-400 hover:text-white transition-colors">
                                <input type="checkbox" id="isAnonymous" class="w-4 h-4 rounded border-gray-600 outline-none accent-yolixa-purple">
                                Make Anonymous
                            </label>
                        </div>

                        <!-- Preview Details -->
                        <div id="conversionPreview" class="hidden bg-gray-800/60 border border-gray-700 rounded-xl p-4 mt-4 shadow-inner">
                            <div class="flex justify-between text-sm mb-2">
                                <span class="text-gray-400">Conversion Rate:</span>
                                <span class="text-yolixa-blue font-bold" id="previewRate">-</span>
                            </div>
                            <div class="flex justify-between text-sm mb-2">
                                <span class="text-gray-400">Platform Fee:</span>
                                <span class="text-red-400 font-bold" id="previewFee">-</span>
                            </div>
                            <div class="flex justify-between text-base border-t border-gray-700 pt-2 mt-2">
                                <span class="text-gray-300 font-bold">Creator Receives:</span>
                                <span class="text-green-400 font-black" id="previewPayout">-</span>
                            </div>
                        </div>

                        <button onclick="sendTip()" id="sendTipBtn"
                                class="w-full gradient-bg py-4 rounded-xl font-bold text-xl hover:scale-[1.02] transition-transform shadow-lg pulse-glow">
                            Support {{ $creator->name }}
                        </button>

                        <p class="text-center text-xs text-gray-500 mt-2 leading-relaxed" id="feeDisclaimer">
                            * A small platform fee applies to support the Yolixa ecosystem.
                        </p>
                        
                        <div id="trustlineAlert" class="hidden mt-4 bg-gray-800/80 border border-yolixa-purple/30 rounded-xl p-4 text-center">
                            <p class="text-sm text-gray-300 mb-3">You need the YLX trustline to use Yolixa tokens!</p>
                            <button onclick="createTrustline()" id="trustlineBtn" class="text-xs bg-yolixa-purple/20 hover:bg-yolixa-purple/40 text-yolixa-purple border border-yolixa-purple/50 px-4 py-2 rounded-lg transition-colors font-bold">
                                Initialize Trustline
                            </button>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Right Column: Supporter Wall -->
            <div class="lg:col-span-5 space-y-6">
                <div class="bg-gray-900/40 rounded-2xl p-6 border border-gray-800 backdrop-blur-sm h-full max-h-[800px] overflow-y-auto">
                    <h3 class="text-xl font-bold text-white mb-6 flex items-center gap-2">
                        <svg class="w-5 h-5 text-yolixa-purple" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd" /></svg>
                        Recent Supporters
                    </h3>
                    
                    @if($recentTips->isEmpty())
                        <div class="text-center py-10 opacity-50">
                            <div class="w-16 h-16 mx-auto bg-gray-800 rounded-full flex items-center justify-center mb-3">
                                <svg class="w-8 h-8 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" /></svg>
                            </div>
                            <p class="text-gray-400">No supporters yet. Be the first!</p>
                        </div>
                    @else
                        <div class="space-y-4">
                            @foreach($recentTips as $tip)
                            <div class="bg-gray-800/50 border border-gray-700/50 p-4 rounded-xl flex items-start gap-4 hover:border-yolixa-purple/30 transition-colors">
                                <div class="w-10 h-10 rounded-full bg-gradient-to-tr from-gray-700 to-gray-600 flex items-center justify-center flex-shrink-0">
                                    <span class="font-bold text-white text-sm">
                                        {{ $tip->is_anonymous ? 'A' : strtoupper(substr($tip->sender_name ?? 'S', 0, 1)) }}
                                    </span>
                                </div>
                                <div class="flex-grow min-w-0">
                                    <div class="flex justify-between items-baseline mb-1">
                                        <p class="font-bold text-gray-200 truncate">
                                            {{ $tip->is_anonymous ? 'Anonymous' : ($tip->sender_name ?? 'Supporter') }}
                                        </p>
                                        <p class="text-xs font-bold text-yolixa-purple whitespace-nowrap ml-2">
                                            +{{ number_format($tip->converted_ylx_amount ?? $tip->amount, 2) }} YLX
                                        </p>
                                    </div>
                                    @if($tip->message)
                                        <p class="text-sm text-gray-400 break-words mt-1">{{ '"' . $tip->message . '"' }}</p>
                                    @endif
                                    <p class="text-[10px] text-gray-500 mt-2">{{ $tip->created_at->diffForHumans() }}</p>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
            
        </div>
    </div>
</section>

<!-- Success Modal -->
<div id="successModal" class="fixed inset-0 bg-black/80 hidden items-center justify-center z-[100] backdrop-blur-sm">
    <div class="bg-gray-900 border border-yolixa-purple/30 p-8 rounded-2xl max-w-sm w-full text-center shadow-2xl">
        <div class="w-20 h-20 bg-green-500/20 rounded-full flex items-center justify-center mx-auto mb-6">
            <svg class="w-10 h-10 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
        </div>
        <h2 class="text-3xl font-bold text-white mb-2">Thank You!</h2>
        <p class="text-gray-400 mb-6">{{ $creator->custom_thank_you_message ?? "Your transaction was successful. Thanks for the support!" }}</p>
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
<script>
    let selectedAsset = 'XLM';

    function selectAsset(asset) {
        selectedAsset = asset;
        document.querySelectorAll('.asset-btn').forEach(btn => btn.classList.remove('active-asset'));
        document.getElementById('asset' + asset).classList.add('active-asset');
        document.getElementById('assetLabel').innerText = asset;

        if (asset === 'YLX') {
            document.getElementById('feeDisclaimer').innerHTML = '<span class="text-yolixa-purple font-bold">0% Platform Fee!</span> You keep 100% of the value.';
        } else {
            document.getElementById('feeDisclaimer').innerText = '* A small platform fee applies for non-native assets to support the Yolixa ecosystem.';
        }
        
        updatePreview();
    }

    let previewTimeout;
    function debouncePreview() {
        clearTimeout(previewTimeout);
        previewTimeout = setTimeout(updatePreview, 500);
    }

    async function updatePreview() {
        const amount = document.getElementById('tipAmount').value;
        const previewBox = document.getElementById('conversionPreview');
        const trustlineAlert = document.getElementById('trustlineAlert');
        
        trustlineAlert.classList.add('hidden');

        if (!amount || amount <= 0) {
            previewBox.classList.add('hidden');
            return;
        }

        try {
            const res = await fetch('/api/tip/preview', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ amount, asset: selectedAsset })
            });
            const data = await res.json();
            if (data.success) {
                document.getElementById('previewRate').innerText = `1 ${selectedAsset} = ${data.rate} YLX`;
                document.getElementById('previewFee').innerText = `- ${parseFloat(data.fee_ylx).toFixed(2)} YLX`;
                document.getElementById('previewPayout').innerText = `${parseFloat(data.creator_payout_ylx).toFixed(2)} YLX`;
                previewBox.classList.remove('hidden');
            } else {
                previewBox.classList.add('hidden');
            }
        } catch(e) {
            console.error('Preview error:', e);
            previewBox.classList.add('hidden');
        }
    }

    async function sendTip() {
        const amount = document.getElementById('tipAmount').value;
        const message = document.getElementById('tipMessage').value;
        const senderName = document.getElementById('senderName').value;
        const isAnonymous = document.getElementById('isAnonymous').checked;
        const receiver = "{{ $creator->public_key }}";
        const btn = document.getElementById('sendTipBtn');

        if (!amount || amount <= 0) {
            toastr.warning('Please enter valid amount.');
            return;
        }

        const connectedWallet = localStorage.getItem("connected_wallet");
        if (!connectedWallet) {
            toastr.info('Please connect your wallet first.');
            openWalletModal();
            return;
        }

        const senderKey = localStorage.getItem(connectedWallet + '_wallet');
        if (receiver === senderKey) {
            toastr.warning('You cannot tip yourself.');
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
                throw new Error('Wallet not supported for direct tips yet.');
            }

            if (txHash) {
                btn.innerText = 'Recording...';
                await recordTip(txHash, amount, selectedAsset, message, senderName, isAnonymous);
                document.getElementById('successModal').classList.remove('hidden');
                document.getElementById('successModal').classList.add('flex');
            }

        } catch (err) {
            console.error(err);
            toastr.error(err.message || 'Transaction failed.');
        } finally {
            if(!document.getElementById('successModal').classList.contains('flex')) {
                btn.disabled = false;
                btn.innerText = 'Support {{ $creator->name }}';
            }
        }
    }

    async function processFreighterTip(amount, destination, assetCode) {
        const response = await fetch('/api/tip/build-xdr', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({ amount, destination, asset: assetCode, sender: localStorage.getItem('freighter_wallet') })
        });

        if (!response.ok) {
            const errorText = await response.text();
            let errorMessage = 'Server error occurred.';
            try { errorMessage = JSON.parse(errorText).message; } catch (e) {}
            throw new Error(errorMessage);
        }

        const data = await response.json();
        if (!data.success) throw new Error(data.message);

        const signedTx = await window.freighterApi.signTransaction(data.xdr, { 
            network: "TESTNET", 
            networkPassphrase: "Test SDF Network ; September 2015" 
        });

        const submitRes = await fetch('/api/tip/submit', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({ signedXdr: signedTx })
        });

        if (!submitRes.ok) {
            const errorText = await submitRes.text();
            let errorMessage = 'Transaction submission failed.';
            try { errorMessage = JSON.parse(errorText).message; } catch (e) {}
            throw new Error(errorMessage);
        }

        const subData = await submitRes.json();
        if (!subData.success) throw new Error(subData.message);

        return subData.hash;
    }

    async function processRabetTip(amount, destination, assetCode) {
        const response = await fetch('/api/tip/build-xdr', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({ amount, destination, asset: assetCode, sender: localStorage.getItem('rabet_wallet') })
        });

        if (!response.ok) {
            const errorText = await response.text();
            let errorMessage = 'Server error occurred.';
            try { errorMessage = JSON.parse(errorText).message; } catch (e) {}
            throw new Error(errorMessage);
        }

        const data = await response.json();
        if (!data.success) throw new Error(data.message);

        const result = await window.rabet.sign(data.xdr, 'testnet');
        
        const submitRes = await fetch('/api/tip/submit', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({ signedXdr: result.xdr })
        });

        if (!submitRes.ok) {
            const errorText = await submitRes.text();
            let errorMessage = 'Transaction submission failed.';
            try { errorMessage = JSON.parse(errorText).message; } catch (e) {}
            throw new Error(errorMessage);
        }

        const subData = await submitRes.json();
        if (!subData.success) throw new Error(subData.message);

        return subData.hash;
    }

    async function recordTip(txHash, amount, asset, message, name, isAnon) {
        const wallet = localStorage.getItem('connected_wallet');
        await fetch('/api/tip/record', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({
                tx_hash: txHash,
                amount: amount,
                asset: asset,
                receiver_id: "{{ $creator->id }}",
                sender_key: localStorage.getItem(wallet + '_wallet'),
                message: message,
                sender_name: name,
                is_anonymous: isAnon ? 1 : 0
            })
        });
    }

    async function createTrustline() {
       // Using the exact implementation of trustline as seen before for MVP compatibility
       // ...
    }
</script>
@endpush
