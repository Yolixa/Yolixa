@extends('layout.app')

@section('content')
@php($tipExecutionMode = config('yolixa.tip_execution_mode', 'soroban'))
<section class="min-h-screen flex items-center justify-center hero-bg pt-20 pb-10">
    <div class="max-w-4xl w-full mx-auto px-4 sm:px-6 lg:px-8">
        <div class="card-hover rounded-2xl p-8 md:p-12 border border-yolixa-purple/20 bg-gray-900/80 backdrop-blur-lg">
            <div class="text-center mb-10">
                <div class="w-24 h-24 gradient-bg rounded-full flex items-center justify-center mx-auto mb-6 shadow-glow">
                    <span class="text-3xl font-bold text-white">{{ strtoupper(substr($creator->name, 0, 1)) }}</span>
                </div>
                <h1 class="text-4xl font-black mb-2 gradient-text">{{ $creator->name }}</h1>
                <p class="text-gray-400 text-lg">
                    @if($tipExecutionMode === 'soroban')
                        Payment executed by YolixaTipRouter on Stellar Testnet.
                    @else
                        Support this creator by sending a Web3 tip.
                    @endif
                </p>
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
                        <div class="grid grid-cols-1 gap-4">
                            <button onclick="selectAsset('XLM')" id="assetXLM" class="asset-btn active-asset flex items-center justify-center gap-2 px-4 py-3 rounded-lg border border-gray-700 bg-gray-800 text-white hover:border-yolixa-purple transition-all">
                                <img src="{{ asset('assets/images/stellar-xlm-logo.png') }}" class="w-6 h-6" alt="XLM">
                                <span>XLM on {{ strtoupper(config('yolixa.network', 'testnet')) }}</span>
                            </button>
                            @if($tipExecutionMode === 'classic' && config('yolixa.assets.USDC.enabled') && config('yolixa.assets.USDC.issuer'))
                            <button onclick="selectAsset('USDC')" id="assetUSDC" class="asset-btn flex items-center justify-center gap-2 px-4 py-3 rounded-lg border border-gray-700 bg-gray-800 text-white hover:border-yolixa-purple transition-all">
                                <img src="{{ asset('assets/images/usd-coin-usdc-logo.png') }}" class="w-6 h-6" alt="USDC">
                                <span>USDC on {{ strtoupper(config('yolixa.network', 'testnet')) }}</span>
                            </button>
                            @endif
                        </div>
                    </div>

                    <div>
                        <label for="tipAmount" class="block text-gray-300 mb-2 text-sm">Amount</label>
                        <div class="relative">
                            <input type="number" id="tipAmount" placeholder="0.00" step="any" oninput="debouncePreview()"
                                   class="w-full px-4 py-4 rounded-xl bg-gray-800/50 text-white border border-gray-700 focus:border-yolixa-purple focus:ring focus:ring-yolixa-purple/30 outline-none text-2xl font-bold">
                            <span id="assetLabel" class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 font-bold">XLM</span>
                        </div>
                    </div>

                    <div id="conversionPreview" class="hidden bg-gray-800/60 border border-gray-700 rounded-xl p-4 mt-4 shadow-inner">
                        <div class="flex justify-between text-sm mb-2">
                            <span class="text-gray-400">Network:</span>
                            <span class="text-yolixa-blue font-bold" id="previewRate">{{ strtoupper(config('yolixa.network', 'testnet')) }}</span>
                        </div>
                        <div class="flex justify-between text-sm mb-2">
                            <span class="text-gray-400">Gross Tip:</span>
                            <span class="text-white font-bold" id="previewGross">-</span>
                        </div>
                        <div class="flex justify-between text-sm mb-2">
                            <span class="text-gray-400">Platform Fee:</span>
                            <span class="text-red-400 font-bold" id="previewFee">-</span>
                        </div>
                        <div class="flex justify-between text-base border-t border-gray-700 pt-2 mt-2">
                            <span class="text-gray-300 font-bold">Creator Receives On-Chain:</span>
                            <span class="text-green-400 font-black" id="previewPayout">-</span>
                        </div>
                    </div>

                    <button onclick="sendTip()" id="sendTipBtn"
                            class="w-full gradient-bg py-4 rounded-xl font-bold text-xl hover:scale-[1.02] transition-transform shadow-lg pulse-glow">
                        Send Tip Now
                    </button>

                    <p class="text-center text-xs text-gray-500 mt-4 leading-relaxed" id="feeDisclaimer">
                        @if($tipExecutionMode === 'soroban')
                            * The smart contract splits your XLM tip atomically between creator and Yolixa treasury.
                        @else
                            * The Stellar transaction pays the creator net amount and platform fee in the same signed transaction.
                        @endif
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
                                <span class="text-yolixa-purple text-sm font-bold">
                                    @if($tipExecutionMode === 'soroban')
                                        Router receipt and creator stats are verified on-chain.
                                    @else
                                        Transparent testnet transaction proof is recorded.
                                    @endif
                                </span>
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
                            <span class="text-white text-xs font-bold">Stellar ({{ strtoupper(config('yolixa.network', 'testnet')) }})</span>
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
        <p class="text-gray-400 mb-4">Your tip was confirmed on Stellar and recorded in Yolixa.</p>
        <a id="successExplorerLink" href="#" target="_blank" rel="noopener noreferrer" class="hidden block text-yolixa-blue text-sm hover:underline mb-6">View Stellar transaction</a>
        <p id="successSorobanStatus" class="text-xs text-gray-500 mb-4"></p>
        <div id="successProofDetails" class="hidden text-left text-xs bg-gray-800/70 border border-gray-700 rounded-lg p-3 mb-6 space-y-2"></div>
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
    const tipExecutionMode = "{{ $tipExecutionMode }}";

    function selectAsset(asset) {
        selectedAsset = asset;
        document.querySelectorAll('.asset-btn').forEach(btn => btn.classList.remove('active-asset'));
        document.getElementById('asset' + asset).classList.add('active-asset');
        document.getElementById('assetLabel').innerText = asset;
        
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
                document.getElementById('previewRate').innerText = String(data.network || window.config?.YOLIXA_NETWORK || 'testnet').toUpperCase();
                document.getElementById('previewGross').innerText = `${parseFloat(data.gross_amount).toFixed(7)} ${data.asset}`;
                document.getElementById('previewFee').innerText = `${parseFloat(data.platform_fee).toFixed(7)} ${data.asset}`;
                document.getElementById('previewPayout').innerText = `${parseFloat(data.creator_receives).toFixed(7)} ${data.asset}`;
                previewBox.classList.remove('hidden');
            } else {
                previewBox.classList.add('hidden');
            }
        } catch(e) {
            console.error('Preview error:', e);
            previewBox.classList.add('hidden');
        }
    }

    function copyToClipboard(text) {
        navigator.clipboard.writeText(text);
        toastr.success('Public key copied!');
    }

    async function sendTip() {
        const amount = document.getElementById('tipAmount').value;
        const receiver = "{{ $creator->public_key }}";
        const receiverId = "{{ $creator->id }}";
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

        const senderKey = localStorage.getItem(connectedWallet + '_wallet');
        if (receiver === senderKey) {
            toastr.warning('You cannot tip yourself.');
            return;
        }

        btn.disabled = true;
        btn.innerText = 'Initializing...';

        try {
            if (tipExecutionMode === 'soroban') {
                if (connectedWallet !== 'freighter') {
                    throw new Error('Phase 2 Soroban tipping supports Freighter only.');
                }

                if (!window.YolixaSorobanTip?.sendTip) {
                    throw new Error('Soroban tip client is not loaded. Please refresh and try again.');
                }

                const result = await window.YolixaSorobanTip.sendTip({
                    amount,
                    receiver,
                    receiverId,
                    csrf: '{{ csrf_token() }}',
                    onProgress: (status) => { btn.innerText = status; },
                });

                showSorobanSuccess(result);
                toastr.success('Tip verified on-chain.');
                return;
            }

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
                const tip = await recordTip(txHash, amount, selectedAsset);
                toastr.success('Tip confirmed on Stellar.');
                const explorerTemplate = window.config?.STELLAR_EXPLORER_TX_URL || '';
                if (explorerTemplate && txHash) {
                    const link = document.getElementById('successExplorerLink');
                    link.href = explorerTemplate.replace('{hash}', txHash);
                    link.classList.remove('hidden');
                }
                document.getElementById('successSorobanStatus').innerText = `Soroban receipt: ${tip.soroban_status || 'disabled'}`;
                document.getElementById('successProofDetails').classList.add('hidden');
                document.getElementById('successProofDetails').innerHTML = '';
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

    function showSorobanSuccess(result) {
        const txHash = result.txHash;
        const proof = result.confirmation?.proof || {};
        const explorerTemplate = window.config?.STELLAR_EXPLORER_TX_URL || '';

        if (explorerTemplate && txHash) {
            const link = document.getElementById('successExplorerLink');
            link.href = explorerTemplate.replace('{hash}', txHash);
            link.classList.remove('hidden');
        }

        document.getElementById('successSorobanStatus').innerText = 'Payment executed by YolixaTipRouter on Stellar Testnet. Verified on-chain.';
        const details = document.getElementById('successProofDetails');
        details.innerHTML = [
            ['Network', proof.network || 'testnet'],
            ['Tx hash', txHash],
            ['Router', abbreviate(proof.router_contract_id || '')],
            ['Contract tip ID', proof.contract_tip_id || result.intent?.contract_tip_id || ''],
            ['Creator payout', `${proof.creator_payout || result.intent?.creator_payout || '-'} XLM`],
            ['Platform fee', `${proof.platform_fee || result.intent?.platform_fee || '-'} XLM`],
            ['Status', 'Verified on-chain'],
        ].map(([label, value]) => (
            `<div class="flex justify-between gap-3"><span class="text-gray-400">${escapeHtml(label)}</span><span class="text-white text-right break-all">${escapeHtml(value)}</span></div>`
        )).join('');
        details.classList.remove('hidden');
        document.getElementById('successModal').classList.remove('hidden');
        document.getElementById('successModal').classList.add('flex');
    }

    function abbreviate(value) {
        return value && value.length > 16 ? `${value.slice(0, 8)}...${value.slice(-8)}` : value;
    }

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, (char) => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;',
        }[char]));
    }

    async function processFreighterTip(amount, destination, assetCode) {
        const freighter = getFreighterApi();
        if (!freighter) {
            throw new Error('Freighter API not loaded. Please hard refresh or check the Freighter extension/CDN.');
        }

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

        if (!response.ok) {
            const errorText = await response.text();
            let errorMessage = 'Server error occurred.';
            try { errorMessage = JSON.parse(errorText).message; } catch (e) {}
            throw new Error(errorMessage);
        }

        const data = await response.json();
        if (!data.success) throw new Error(data.message);

        // Ensure active address matches local storage to avoid tx_bad_auth
        if (typeof freighter.getPublicKey === 'function') {
            const activeKey = await freighter.getPublicKey();
            if (activeKey !== localStorage.getItem('freighter_wallet')) {
                localStorage.setItem('freighter_wallet', activeKey);
                throw new Error("Connected Freighter account changed. Please try sending the tip again.");
            }
        }

        const signResult = await freighter.signTransaction(data.xdr, {
            network: data.network || window.config?.STELLAR_NETWORK_LABEL || "TESTNET",
            networkPassphrase: data.network_passphrase || window.config?.STELLAR_PASSPHRASE
        });
        const signedTx = normalizeSignedXdr(signResult);

        // Submit to Horizon
        const submitRes = await fetch('/api/tip/submit', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ signedXdr: signedTx, sender_key: localStorage.getItem('freighter_wallet') })
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

        if (!response.ok) {
            const errorText = await response.text();
            let errorMessage = 'Server error occurred.';
            try { errorMessage = JSON.parse(errorText).message; } catch (e) {}
            throw new Error(errorMessage);
        }

        const data = await response.json();
        if (!data.success) throw new Error(data.message);

        const network = (window.config?.YOLIXA_NETWORK || 'testnet').toLowerCase();

        let signedTx;
        try {
            const result = await window.rabet.sign(data.xdr, network);
            signedTx = normalizeSignedXdr(result);
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
            body: JSON.stringify({ signedXdr: signedTx, sender_key: localStorage.getItem('rabet_wallet') })
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

    async function recordTip(txHash, amount, asset) {
        const response = await fetch('/api/tip/record', {
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

        const data = await response.json();
        if (!response.ok || !data.success) {
            throw new Error(data.message || 'Tip was submitted but could not be recorded.');
        }

        return data.tip;
    }

    function normalizeSignedXdr(response) {
        const signedXdr = response?.signedTxXdr
            || response?.signedTx
            || response?.signed_transaction
            || response?.signed_envelope_xdr
            || response?.xdr
            || response?.data?.signedTxXdr
            || response?.data?.signed_envelope_xdr
            || response?.data?.xdr
            || (typeof response === 'string' ? response : null);

        if (!signedXdr || typeof signedXdr !== 'string') {
            throw new Error('Wallet returned an invalid signed transaction.');
        }

        return signedXdr;
    }

    function getFreighterApi() {
        return window.freighterApi
            || window.stellarFreighter
            || window.StellarFreighter
            || window.freighter
            || null;
    }

</script>
@endpush
