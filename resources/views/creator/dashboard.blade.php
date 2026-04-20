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
                    <p class="text-xs text-gray-500 uppercase tracking-wider font-bold">Referral Link</p>
                    <p class="text-yolixa-blue text-sm">{{ route('creator.referral', ['code' => $creator->referral_key]) }}</p>
                </div>
                <button data-url="{{ route('creator.referral', ['code' => $creator->referral_key]) }}" onclick="copyToClipboard(this.getAttribute('data-url'))" class="p-2 hover:bg-gray-800 rounded-lg transition-colors">
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                </button>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-12">
            <div class="card-hover rounded-2xl p-8 border border-gray-800 flex flex-col justify-center">
                <p class="text-gray-500 font-bold mb-2 uppercase text-xs">Total Received</p>
                <h3 class="text-4xl font-black text-white">{{ number_format($tips->sum('amount'), 2) }} <span class="text-lg text-gray-500">XLM</span></h3>
            </div>
            
            <div class="card-hover rounded-2xl p-8 border border-yolixa-purple/30 bg-yolixa-purple/5 flex flex-col justify-center relative overflow-hidden">
                <div class="absolute -right-4 -top-4 w-24 h-24 bg-yolixa-purple/10 rounded-full blur-2xl"></div>
                <p class="text-yolixa-purple font-bold mb-2 uppercase text-xs">YLX Bonus Rewards</p>
                <h3 class="text-4xl font-black text-white">{{ number_format($tips->sum('bonus'), 0) }} <span class="text-lg text-yolixa-purple">YLX</span></h3>
                <p class="text-xs text-gray-400 mt-2 italic">* Rewarded for receiving tips</p>
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
                            <th class="px-8 py-4 text-xs font-bold text-gray-500 uppercase">Amount</th>
                            <th class="px-8 py-4 text-xs font-bold text-gray-500 uppercase">Bonus (YLX)</th>
                            <th class="px-8 py-4 text-xs font-bold text-gray-500 uppercase">Status</th>
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
                            <td class="px-8 py-4 text-yolixa-purple font-bold">
                                +{{ number_format($tip->bonus, 0) }}
                            </td>
                            <td class="px-8 py-4">
                                <span class="px-2 py-1 rounded-full text-[10px] font-black {{ $tip->status == 'confirmed' ? 'bg-green-500/10 text-green-500' : 'bg-yellow-500/10 text-yellow-500' }}">
                                    {{ strtoupper($tip->status) }}
                                </span>
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
</script>
@endpush
