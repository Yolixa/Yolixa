@extends('layout.app')

@section('content')
<section class="min-h-screen py-24 bg-dark-bg text-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="flex items-center justify-between mb-10">
            <div>
                <h1 class="text-4xl font-black mb-2 border-l-4 border-red-500 pl-4">Admin <span class="text-red-500">Control Panel</span></h1>
                <p class="text-gray-400 pl-5">Platform oversight, analytics, and management.</p>
            </div>
            
            <span class="bg-red-500/10 text-red-500 font-bold px-4 py-2 rounded-full border border-red-500/30 text-sm flex items-center gap-2">
                <div class="w-2 h-2 bg-red-500 rounded-full animate-pulse"></div> Live Operations
            </span>
        </div>

        <!-- Metrics Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
            <div class="bg-gray-900 rounded-2xl p-6 border border-gray-800 shadow-lg">
                <p class="text-gray-500 font-bold uppercase text-xs mb-1">Total Creators</p>
                <p class="text-3xl font-black text-white">{{ number_format($totalCreators) }}</p>
            </div>
            <div class="bg-gray-900 rounded-2xl p-6 border border-gray-800 shadow-lg">
                <p class="text-gray-500 font-bold uppercase text-xs mb-1">Total Fans</p>
                <p class="text-3xl font-black text-white">{{ number_format($totalFans) }}</p>
            </div>
            <div class="bg-gray-900 rounded-2xl p-6 border border-gray-800 shadow-lg">
                <p class="text-gray-500 font-bold uppercase text-xs mb-1">Total Tips Volume</p>
                <p class="text-3xl font-black text-blue-400 flex items-baseline gap-1">{{ number_format($totalTipsVolume, 2) }} <span class="text-sm text-gray-500">MIXED</span></p>
            </div>
            <div class="bg-gray-900 rounded-2xl p-6 border border-red-500/20 shadow-lg relative overflow-hidden">
                <div class="absolute -right-4 -top-4 w-16 h-16 bg-red-500/10 rounded-full blur-xl"></div>
                <p class="text-red-400 font-bold uppercase text-xs mb-1">Platform Revenue (YLX)</p>
                <p class="text-3xl font-black text-white">{{ number_format($totalPlatformRevenue, 2) }} <span class="text-sm text-red-400 font-bold">YLX</span></p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Left Column: Operations -->
            <div class="lg:col-span-1 space-y-8">
                
                <div class="bg-gray-900 rounded-2xl p-6 border border-gray-800 shadow-lg">
                    <h3 class="text-xl font-bold mb-4 flex items-center justify-between">
                        Pending Claims
                        <span class="bg-yellow-500 text-black text-xs font-bold px-2 py-1 rounded-md">{{ $pendingClaims }}</span>
                    </h3>
                    @if($pendingClaims == 0)
                        <p class="text-gray-500 text-sm">All creator reward claims have been processed.</p>
                    @else
                        <p class="text-gray-400 text-sm mb-4">You have pending manual YLX transfers to process for creators who hit their target.</p>
                        <button class="w-full bg-yellow-500 hover:bg-yellow-400 text-black font-bold py-2 rounded-lg transition-colors text-sm">
                            Process Claims (Coming Soon)
                        </button>
                    @endif
                </div>

                <div class="bg-gray-900 rounded-2xl p-6 border border-gray-800 shadow-lg">
                    <h3 class="text-xl font-bold mb-4 flex items-center justify-between text-red-400">
                        Suspicious Tips
                        <span class="bg-red-500/20 text-red-500 text-xs font-bold px-2 py-1 rounded-md">{{ $suspiciousTips }}</span>
                    </h3>
                    <p class="text-gray-500 text-sm mb-4">Tips that failed execution on Horizon or lack transaction hashes.</p>
                    <button class="w-full border border-red-500/50 hover:bg-red-500/10 text-red-400 font-bold py-2 rounded-lg transition-colors text-sm">
                        Audit Logs
                    </button>
                </div>

            </div>

            <!-- Right Column: Recent Activity -->
            <div class="lg:col-span-2">
                <div class="bg-gray-900 rounded-2xl p-6 border border-gray-800 shadow-lg h-full">
                    <h3 class="text-xl font-bold mb-6">Recent Platform Activity</h3>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b border-gray-800 text-gray-500 text-xs uppercase">
                                    <th class="pb-3">Sender</th>
                                    <th class="pb-3">Creator</th>
                                    <th class="pb-3 text-right">Amount</th>
                                    <th class="pb-3 text-right">Revenue</th>
                                    <th class="pb-3 text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-800">
                                @forelse($recentTips as $tip)
                                <tr class="hover:bg-gray-800/30 transition-colors">
                                    <td class="py-4 text-sm">
                                        <span class="font-mono text-gray-400" title="{{ $tip->sender_key }}">{{ substr($tip->sender_key, 0, 5) }}...{{ substr($tip->sender_key, -4) }}</span>
                                    </td>
                                    <td class="py-4 text-sm font-bold text-white">
                                        {{ $tip->receiver ? $tip->receiver->username : 'Unknown' }}
                                    </td>
                                    <td class="py-4 text-sm text-right font-bold">
                                        {{ number_format($tip->amount, 2) }} <span class="text-gray-500 text-xs">{{ $tip->asset }}</span>
                                    </td>
                                    <td class="py-4 text-sm text-right text-red-400 font-bold">
                                        +{{ number_format($tip->platform_fee_ylx ?? 0, 2) }}
                                    </td>
                                    <td class="py-4 text-center">
                                        <span class="px-2 py-1 rounded text-[10px] font-black {{ $tip->status == 'confirmed' ? 'bg-green-500/20 text-green-400' : 'bg-red-500/20 text-red-400' }}">
                                            {{ strtoupper($tip->status) }}
                                        </span>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="py-8 text-center text-gray-500 text-sm">No recent network activity observed.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>

        </div>
    </div>
</section>
@endsection
