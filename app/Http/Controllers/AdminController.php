<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Tip;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminController extends Controller
{
    public function dashboard()
    {
        if (Auth::user()?->role !== 'admin') {
            abort(403);
        }

        $totalCreators = User::where('role', 'creator')->count();
        $totalFans = User::where('role', 'fan')->count();
        $totalTipsVolume = Tip::where('status', 'confirmed')->where('asset', 'XLM')->sum('amount');
        $totalCreatorPayout = Tip::where('status', 'confirmed')->sum('creator_payout_amount');

        $recentTips = Tip::with(['sender', 'receiver'])->orderBy('created_at', 'desc')->limit(10)->get();

        return view('admin.dashboard', compact(
            'totalCreators',
            'totalFans',
            'totalTipsVolume',
            'totalCreatorPayout',
            'recentTips'
        ));
    }

    public function manageCreator(Request $request, $id)
    {
        if (Auth::user()?->role !== 'admin') {
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
