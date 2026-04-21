@extends('layout.app')

@section('content')
<section class="min-h-screen py-24 hero-bg">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header Section -->
        <div class="flex flex-col md:flex-row md:items-center justify-between mb-12 gap-6">
            <div>
                <h1 class="text-4xl font-black text-white mb-2">Creator <span class="gradient-text">Dashboard</span></h1>
                <p class="text-gray-400">Welcome back, {{ $creator->name }}. Track your earnings and rewards.</p>
            </div>
            
            <div class="flex items-center gap-4 bg-gray-900/50 p-4 rounded-2xl border border-gray-700">
                <div class="text-right">
                    <p class="text-xs text-gray-500 uppercase tracking-wider font-bold">Public Profile</p>
                    <p class="text-yolixa-blue text-sm">{{ $creator->username ? route('creator.profile', ['username' => $creator->username]) : route('creator.referral', ['code' => $creator->referral_key]) }}</p>
                </div>
                <button data-url="{{ $creator->username ? route('creator.profile', ['username' => $creator->username]) : route('creator.referral', ['code' => $creator->referral_key]) }}" onclick="copyToClipboard(this.getAttribute('data-url'))" class="p-2 hover:bg-gray-800 rounded-lg transition-colors">
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                </button>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-12">
            <div class="card-hover rounded-2xl p-8 border border-gray-800 flex flex-col justify-center">
                <p class="text-gray-500 font-bold mb-2 uppercase text-xs">Total Payouts Received (YLX)</p>
                <h3 class="text-4xl font-black text-white">{{ number_format($tips->sum('creator_payout_amount') + $tips->sum('bonus'), 2) }} <span class="text-lg text-gray-500">YLX</span></h3>
            </div>
            
            <div class="card-hover rounded-2xl p-8 border border-yolixa-purple/30 bg-yolixa-purple/5 flex flex-col justify-center relative overflow-hidden">
                <div class="absolute -right-4 -top-4 w-24 h-24 bg-yolixa-purple/10 rounded-full blur-2xl"></div>
                <p class="text-yolixa-purple font-bold mb-2 uppercase text-xs">Total Tips Processed</p>
                <h3 class="text-4xl font-black text-white">{{ $tips->count() }} <span class="text-lg text-yolixa-purple">Tips</span></h3>
            </div>

            <div class="card-hover rounded-2xl p-8 border border-gray-800 flex flex-col justify-center">
                <p class="text-gray-500 font-bold mb-2 uppercase text-xs">Claimable YLX Bonus</p>
                <div class="flex items-end justify-between">
                    <h3 class="text-4xl font-black text-white">{{ number_format($creator->ylx_claimable_balance, 0) }} <span class="text-lg text-gray-500">YLX</span></h3>
                    @if($creator->ylx_claimable_balance >= 1)
                        <button onclick="claimRewards()" class="bg-yolixa-purple hover:bg-yolixa-purple/80 text-white font-bold py-2 px-4 rounded-xl text-sm transition-colors">Claim</button>
                    @endif
                </div>
                <p class="text-xs text-gray-400 mt-2">Total Claimed: {{ number_format($creator->ylx_claimed_total, 0) }} YLX</p>
            </div>
        </div>

        <!-- Profile Settings & Trustline -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-12">
            
            <!-- Settings Form -->
            <div class="bg-gray-900/50 rounded-3xl border border-gray-800 p-8 backdrop-blur-md">
                <h3 class="text-xl font-bold text-white mb-6">Profile Settings</h3>
                <form id="profileForm" onsubmit="updateProfile(event)" class="space-y-4">
                    <div>
                        <label class="block text-gray-400 text-sm mb-1">Username (Profile URL)</label>
                        <input type="text" id="username" value="{{ $creator->username }}" placeholder="your-unique-slug" class="w-full px-4 py-3 rounded-xl bg-gray-800/80 text-white border border-gray-700 outline-none focus:border-yolixa-purple transition-colors">
                    </div>
                    <div>
                        <label class="block text-gray-400 text-sm mb-1">Bio</label>
                        <textarea id="bio" rows="2" class="w-full px-4 py-3 rounded-xl bg-gray-800/80 text-white border border-gray-700 outline-none focus:border-yolixa-purple transition-colors">{{ $creator->bio }}</textarea>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-gray-400 text-sm mb-1">Goal Title</label>
                            <input type="text" id="goal_title" value="{{ $creator->goal_title }}" placeholder="e.g. New Equipment" class="w-full px-4 py-3 rounded-xl bg-gray-800/80 text-white border border-gray-700 outline-none focus:border-yolixa-purple">
                        </div>
                        <div>
                            <label class="block text-gray-400 text-sm mb-1">Goal Amount (YLX)</label>
                            <input type="number" id="goal_amount" value="{{ $creator->goal_amount }}" placeholder="500" class="w-full px-4 py-3 rounded-xl bg-gray-800/80 text-white border border-gray-700 outline-none focus:border-yolixa-purple">
                        </div>
                    </div>
                    <div>
                        <label class="block text-gray-400 text-sm mb-1">Custom Thank You Message</label>
                        <input type="text" id="thank_you" value="{{ $creator->custom_thank_you_message }}" placeholder="Thanks for your support!" class="w-full px-4 py-3 rounded-xl bg-gray-800/80 text-white border border-gray-700 outline-none focus:border-yolixa-purple transition-colors">
                    </div>
                    <button type="submit" id="saveProfileBtn" class="w-full gradient-bg py-3 rounded-xl font-bold hover:scale-[1.01] transition-transform text-white">Save Changes</button>
                </form>
            </div>

            <!-- Trustline Status -->
            <div class="bg-gray-900/50 rounded-3xl border border-gray-800 p-8 backdrop-blur-md flex flex-col items-center justify-center text-center">
                <div class="w-20 h-20 bg-gray-800 rounded-full flex items-center justify-center mb-6" id="trustlineIcon">
                    <svg class="w-10 h-10 text-gray-500 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                </div>
                <h3 class="text-xl font-bold text-white mb-2" id="trustlineTitle">Checking YLX Support...</h3>
                <p class="text-gray-400 text-sm mb-6" id="trustlineDesc">We are verifying if your wallet is configured to receive YLX payouts.</p>
                
                <button onclick="createTrustline()" id="dashboardTrustlineBtn" class="hidden gradient-bg text-white font-bold py-3 px-8 rounded-xl hover:scale-[1.02] transition-transform shadow-lg shadow-yolixa-purple/20">
                    Enable YLX Payouts
                </button>
            </div>
            
        </div>

        <!-- Transactions Table -->
        <div class="bg-gray-900/50 rounded-3xl border border-gray-800 overflow-hidden backdrop-blur-md">
            <div class="px-8 py-6 border-b border-gray-800 flex items-center justify-between">
                <h3 class="text-xl font-bold text-white">Recent Transactions</h3>
                <a href="https://stellar.expert/explorer/testnet/account/{{ $creator->public_key }}" target="_blank" class="text-yolixa-blue text-sm hover:underline">View on Explorer</a>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-gray-800/20">
                        <tr>
                            <th class="px-8 py-4 text-xs font-bold text-gray-500 uppercase">Sender</th>
                            <th class="px-8 py-4 text-xs font-bold text-gray-500 uppercase">Tipped Amount</th>
                            <th class="px-8 py-4 text-xs font-bold text-gray-500 uppercase">Converted (YLX)</th>
                            <th class="px-8 py-4 text-xs font-bold text-gray-500 uppercase">Payout Status</th>
                            <th class="px-8 py-4 text-xs font-bold text-gray-500 uppercase">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-800">
                        @forelse($tips as $tip)
                        <tr class="hover:bg-gray-800/10 transition-colors">
                            <td class="px-8 py-4">
                                <span class="text-gray-400 text-sm font-mono">{{ $tip->sender ? substr($tip->sender->public_key, 0, 6).'...'.substr($tip->sender->public_key, -4) : 'Anonymous Supporter' }}</span>
                            </td>
                            <td class="px-8 py-4">
                                <span class="text-white font-bold">{{ number_format($tip->amount, 2) }} {{ $tip->asset }}</span>
                            </td>
                            <td class="px-8 py-4 text-green-400 font-bold">
                                {{ number_format($tip->creator_payout_amount ?? $tip->bonus ?? 0, 2) }}
                            </td>
                            <td class="px-8 py-4">
                                <span class="px-2 py-1 rounded-full text-[10px] font-black {{ $tip->payout_status == 'completed' ? 'bg-green-500/10 text-green-500' : ($tip->payout_status == 'failed' ? 'bg-red-500/10 text-red-500' : 'bg-yellow-500/10 text-yellow-500') }}">
                                    {{ strtoupper($tip->payout_status ?? $tip->status) }}
                                </span>
                                @if($tip->payout_status === 'failed')
                                    <div class="text-[10px] text-red-400 mt-1 max-w-[150px] truncate" title="{{ $tip->payout_error }}">{{ $tip->payout_error }}</div>
                                @endif
                            </td>
                            <td class="px-8 py-4 text-gray-500 text-sm">
                                {{ $tip->created_at->format('M d, Y') }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-8 py-12 text-center text-gray-500 italic">No tips received yet. Share your referral link!</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

@endsection

@push('js')
<script>
    function copyToClipboard(text) {
        navigator.clipboard.writeText(text);
        toastr.success('Referral link copied!');
    }

    async function claimRewards() {
        if(!confirm('Are you sure you want to claim your YLX rewards?')) return;
        
        try {
            const formData = new FormData();
            formData.append('amount', '{{ $creator->ylx_claimable_balance }}');
            
            const req = await fetch('{{ route('creator.claim_rewards') }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: formData
            });
            const res = await req.json();
            
            if(res.success) {
                toastr.success(res.message);
                setTimeout(() => window.location.reload(), 2000);
            } else {
                toastr.error(res.message);
            }
        } catch(e) {
            toastr.error('Error submitting claim.');
        }
    }

    async function updateProfile(e) {
        e.preventDefault();
        const btn = document.getElementById('saveProfileBtn');
        btn.innerText = 'Saving...';
        btn.disabled = true;

        try {
            const res = await fetch('{{ route('creator.update_profile') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    username: document.getElementById('username').value,
                    bio: document.getElementById('bio').value,
                    goal_title: document.getElementById('goal_title').value,
                    goal_amount: document.getElementById('goal_amount').value,
                    custom_thank_you_message: document.getElementById('thank_you').value,
                })
            });
            const data = await res.json();
            if(data.success) {
                toastr.success('Profile updated successfully!');
            } else {
                toastr.error(data.message || 'Failed to update profile.');
            }
        } catch(err) {
            toastr.error('Network error during profile update.');
        } finally {
            btn.innerText = 'Save Changes';
            btn.disabled = false;
        }
    }

    document.addEventListener("DOMContentLoaded", async function () {
        // Check Trustline
        try {
            const serverUrl = window.config?.STELLAR_HORIZON || "https://horizon-testnet.stellar.org";
            const publicKey = "{{ $creator->public_key }}";
            const assetCode = window.config?.YLX_ASSET_CODE || "YLX";
            
            const req = await fetch(`${serverUrl}/accounts/${publicKey}`);
            if(!req.ok) throw new Error("Wallet not funded on network.");
            
            const data = await req.json();
            const ylxBalance = data.balances.find(b => b.asset_code === assetCode);
            
            const icon = document.getElementById('trustlineIcon');
            const title = document.getElementById('trustlineTitle');
            const desc = document.getElementById('trustlineDesc');
            const btn = document.getElementById('dashboardTrustlineBtn');

            if(ylxBalance) {
                icon.innerHTML = '<svg class="w-10 h-10 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>';
                icon.className = 'w-20 h-20 bg-green-500/10 border border-green-500/30 rounded-full flex items-center justify-center mb-6';
                title.innerText = 'YLX Ready';
                title.className = 'text-xl font-bold text-green-400 mb-2';
                desc.innerText = 'Your wallet is fully configured to receive automated YLX payouts from tips.';
            } else {
                icon.innerHTML = '<svg class="w-10 h-10 text-yolixa-purple" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>';
                icon.className = 'w-20 h-20 bg-yolixa-purple/10 border border-yolixa-purple/30 rounded-full flex items-center justify-center mb-6';
                title.innerText = 'Trustline Required';
                title.className = 'text-xl font-bold text-yolixa-purple mb-2';
                desc.innerText = 'You will not receive your payouts until you add the YLX trustline to your wallet.';
                btn.classList.remove('hidden');
            }
        } catch(e) {
            console.error(e);
            document.getElementById('trustlineTitle').innerText = 'Wallet Not Found';
            document.getElementById('trustlineDesc').innerText = 'Ensure your wallet is funded with XLM on the testnet first.';
        }
    });

    async function createTrustline() {
        const btn = document.getElementById('dashboardTrustlineBtn');
        const connectedWallet = localStorage.getItem("connected_wallet");
        if (!connectedWallet) {
            toastr.info('Please reconnect your wallet to perform this action.');
            return;
        }

        const senderKey = localStorage.getItem(connectedWallet + '_wallet');
        if(senderKey !== "{{ $creator->public_key }}") {
            toastr.error('Connected wallet does not match dashboard account.');
            return;
        }
        
        btn.disabled = true;
        btn.innerText = 'Building...';

        try {
            const response = await fetch('/api/tip/build-trustline', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ sender: senderKey })
            });

            if (!response.ok) throw new Error("Server error configuring trustline.");
            const data = await response.json();
            if (!data.success) throw new Error(data.message);

            btn.innerText = 'Signing...';
            
            let signedTx;
            if (connectedWallet === 'freighter') {
                signedTx = await window.freighterApi.signTransaction(data.xdr, { network: "TESTNET", networkPassphrase: "Test SDF Network ; September 2015" });
            } else if (connectedWallet === 'rabet') {
                const res = await window.rabet.sign(data.xdr, 'testnet');
                signedTx = res.xdr;
            } else {
                throw new Error('Wallet not supported.');
            }

            btn.innerText = 'Submitting...';
            const submitRes = await fetch('/api/tip/submit', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ signedXdr: signedTx })
            });

            if (!submitRes.ok) throw new Error("Transaction submission failed.");
            const subData = await submitRes.json();
            if (!subData.success) throw new Error(subData.message);

            toastr.success('Success! YLX trustline initialized.');
            setTimeout(() => window.location.reload(), 1500);

        } catch (err) {
            console.error(err);
            toastr.error(err.message || 'Failed to create trustline.');
            btn.disabled = false;
            btn.innerText = 'Enable YLX Payouts';
        }
    }
</script>
@endpush
