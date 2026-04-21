<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Tip;
use App\Models\RewardClaim;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function dashboard()
    {
        // Must be protected by AdminMiddleware (stubbed here as role check)
        if (\Illuminate\Support\Facades\Auth::user()->role !== 'admin') {
            abort(403);
        }

        $totalCreators = User::where('role', 'creator')->count();
        $totalFans = User::where('role', 'fan')->count();
        $totalTipsVolume = Tip::where('status', 'confirmed')->sum('amount');
        $totalPlatformRevenue = Tip::where('status', 'confirmed')->sum('platform_fee');

        $pendingClaims = RewardClaim::where('status', 'pending')->count();
        $recentTips = Tip::with(['sender', 'receiver'])->orderBy('created_at', 'desc')->limit(10)->get();
        $suspiciousTips = Tip::where('status', 'failed')->orWhereNull('tx_hash')->count();

        return view('admin.dashboard', compact(
            'totalCreators',
            'totalFans',
            'totalTipsVolume',
            'totalPlatformRevenue',
            'pendingClaims',
            'suspiciousTips',
            'recentTips'
        ));
    }

    public function manageCreator(Request $request, $id)
    {
        if (\Illuminate\Support\Facades\Auth::user()->role !== 'admin') {
            abort(403);
        }

        $creator = User::findOrFail($id);
        
        if ($request->has('is_featured')) {
            $creator->is_featured = $request->is_featured;
        }

        if ($request->has('status')) {
            $creator->status = $request->status; // 0 suspended, 1 active
        }

        $creator->save();

        return response()->json(['success' => true, 'creator' => $creator]);
    }
}
