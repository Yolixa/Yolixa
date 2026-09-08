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

        $data = $request->validate([
            'is_featured' => 'sometimes|boolean',
            'status' => 'sometimes|boolean',
        ]);

        $creator = User::where('role', 'creator')->findOrFail($id);
        
        if (array_key_exists('is_featured', $data)) {
            $creator->is_featured = $data['is_featured'];
        }

        if (array_key_exists('status', $data)) {
            $creator->status = $data['status'];
        }

        $creator->save();

        return response()->json(['success' => true, 'creator' => $creator]);
    }
}
