@extends('layout.app')

@section('content')
<section class="min-h-screen py-24 hero-bg">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header Section -->
        <div class="flex flex-col md:flex-row md:items-center justify-between mb-12 gap-6">
            <div>
                <h1 class="text-4xl font-black text-white mb-2">Creator <span class="gradient-text">Dashboard</span></h1>
                <p class="text-gray-400">Welcome back, {{ $creator->name }}. Track your Stellar testnet tips.</p>
            </div>
            
            <div class="flex items-center gap-4 bg-gray-900/50 p-4 rounded-2xl border border-gray-700">
                <div class="text-right">
                <p class="text-xs text-gray-500 uppercase tracking-wider font-bold">Referral Tip Link</p>
                    <p id="referralLinkText" class="text-yolixa-blue text-sm break-all">{{ $creator->username ? route('creator.profile', ['username' => $creator->username]) : route('creator.referral', ['code' => $creator->referral_key]) }}</p>
                </div>
                <button id="copyReferralBtn" type="button" title="Copy referral link" aria-label="Copy referral link" data-url="{{ $creator->username ? route('creator.profile', ['username' => $creator->username]) : route('creator.referral', ['code' => $creator->referral_key]) }}" onclick="copyReferralLink(this)" class="p-2 hover:bg-gray-800 rounded-lg transition-colors">
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                </button>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-12">
            <div class="card-hover rounded-2xl p-8 border border-gray-800 flex flex-col justify-center">
                <p class="text-gray-500 font-bold mb-2 uppercase text-xs">Total XLM Received</p>
                <h3 class="text-4xl font-black text-white">{{ number_format($tips->where('status', 'confirmed')->sum('amount'), 7) }} <span class="text-lg text-gray-500">XLM</span></h3>
            </div>
            
            <div class="card-hover rounded-2xl p-8 border border-yolixa-purple/30 bg-yolixa-purple/5 flex flex-col justify-center relative overflow-hidden">
                <div class="absolute -right-4 -top-4 w-24 h-24 bg-yolixa-purple/10 rounded-full blur-2xl"></div>
                <p class="text-yolixa-purple font-bold mb-2 uppercase text-xs">Total Tips Processed</p>
                <h3 class="text-4xl font-black text-white">{{ $tips->count() }} <span class="text-lg text-yolixa-purple">Tips</span></h3>
            </div>

            <div class="card-hover rounded-2xl p-8 border border-gray-800 flex flex-col justify-center">
                <p class="text-gray-500 font-bold mb-2 uppercase text-xs">Platform Fee Tracked</p>
                <h3 class="text-4xl font-black text-white">{{ number_format($tips->where('status', 'confirmed')->sum('platform_fee'), 7) }} <span class="text-lg text-gray-500">XLM</span></h3>
                <p class="text-xs text-gray-400 mt-2">Recorded for reporting; MVP transfers are direct wallet-to-wallet.</p>
            </div>
        </div>

        <!-- Profile Settings & Wallet -->
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

            <!-- Wallet Status -->
            <div class="bg-gray-900/50 rounded-3xl border border-gray-800 p-8 backdrop-blur-md flex flex-col items-center justify-center text-center">
                <div class="w-20 h-20 bg-green-500/10 border border-green-500/30 rounded-full flex items-center justify-center mb-6">
                    <svg class="w-10 h-10 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                </div>
                <h3 class="text-xl font-bold text-green-400 mb-2">Wallet Connected</h3>
                <p class="text-gray-400 text-sm mb-4">Tips are paid directly to this Stellar testnet address.</p>
                <p class="text-xs text-gray-300 font-mono break-all bg-gray-800/70 border border-gray-700 rounded-xl p-4">{{ $creator->public_key }}</p>
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
                            <th class="px-8 py-4 text-xs font-bold text-gray-500 uppercase">Tx Hash</th>
                            <th class="px-8 py-4 text-xs font-bold text-gray-500 uppercase">Sender</th>
                            <th class="px-8 py-4 text-xs font-bold text-gray-500 uppercase">Amount</th>
                            <th class="px-8 py-4 text-xs font-bold text-gray-500 uppercase">Fee</th>
                            <th class="px-8 py-4 text-xs font-bold text-gray-500 uppercase">Status</th>
                            <th class="px-8 py-4 text-xs font-bold text-gray-500 uppercase">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-800">
                        @forelse($tips as $tip)
                        <tr class="hover:bg-gray-800/10 transition-colors">
                            <td class="px-8 py-4">
                                <a href="https://stellar.expert/explorer/testnet/tx/{{ $tip->tx_hash }}" target="_blank" class="text-yolixa-blue text-sm font-mono hover:underline">{{ substr($tip->tx_hash, 0, 10) }}...{{ substr($tip->tx_hash, -8) }}</a>
                            </td>
                            <td class="px-8 py-4">
                                <span class="text-gray-400 text-sm font-mono">{{ $tip->sender_wallet ? substr($tip->sender_wallet, 0, 6).'...'.substr($tip->sender_wallet, -4) : 'Unknown' }}</span>
                            </td>
                            <td class="px-8 py-4">
                                <span class="text-white font-bold">{{ number_format($tip->amount, 7) }} {{ $tip->asset }}</span>
                            </td>
                            <td class="px-8 py-4 text-gray-400 text-sm">
                                Platform {{ number_format($tip->platform_fee, 7) }} XLM<br>
                                Network {{ number_format($tip->network_fee, 7) }} XLM
                            </td>
                            <td class="px-8 py-4">
                                <span class="px-2 py-1 rounded-full text-[10px] font-black {{ $tip->status == 'confirmed' ? 'bg-green-500/10 text-green-500' : ($tip->status == 'failed' ? 'bg-red-500/10 text-red-500' : 'bg-yellow-500/10 text-yellow-500') }}">
                                    {{ strtoupper($tip->status) }}
                                </span>
                            </td>
                            <td class="px-8 py-4 text-gray-500 text-sm">
                                {{ $tip->created_at->format('M d, Y') }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-8 py-12 text-center text-gray-500 italic">No tips received yet. Share your referral link!</td>
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
    async function copyReferralLink(button) {
        const text = button.getAttribute('data-url');

        try {
            await copyToClipboard(text);
            toastr.success('Referral link copied!');
        } catch (error) {
            console.error('Copy failed:', error);
            toastr.error('Could not copy the referral link.');
        }
    }

    async function copyToClipboard(text) {
        if (!text) {
            throw new Error('No referral link found.');
        }

        if (navigator.clipboard && window.isSecureContext) {
            await navigator.clipboard.writeText(text);
            return;
        }

        const textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.setAttribute('readonly', '');
        textarea.style.position = 'fixed';
        textarea.style.top = '-9999px';
        textarea.style.left = '-9999px';
        document.body.appendChild(textarea);
        textarea.focus();
        textarea.select();

        const copied = document.execCommand('copy');
        document.body.removeChild(textarea);

        if (!copied) {
            throw new Error('Fallback copy command failed.');
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
                if (data.creator?.username) {
                    document.getElementById('username').value = data.creator.username;
                }
                if (data.profile_url) {
                    document.getElementById('referralLinkText').innerText = data.profile_url;
                    document.getElementById('copyReferralBtn').setAttribute('data-url', data.profile_url);
                }
            } else {
                const firstError = data.errors ? Object.values(data.errors).flat()[0] : null;
                toastr.error(firstError || data.message || 'Failed to update profile.');
            }
        } catch(err) {
            toastr.error('Network error during profile update.');
        } finally {
            btn.innerText = 'Save Changes';
            btn.disabled = false;
        }
    }

</script>
@endpush
