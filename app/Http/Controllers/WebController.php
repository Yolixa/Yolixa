<?php

namespace App\Http\Controllers;

use App\Models\Blockchain;
use App\Models\WalletType;
use Illuminate\Http\Request;

class WebController extends Controller
{
    public function index()
    {
        $blockchains = Blockchain::where('active', 1)->get();
        return view('index', compact('blockchains'));
    }

    public function whitepaper()
    {
        return view('white_paper');
    }

    public function getWallets($id)
    {
        $enabledWallets = collect(config('yolixa.enabled_wallets', []))
            ->map(fn ($wallet) => strtolower(trim((string) $wallet)))
            ->filter()
            ->values();

        $wallets = WalletType::where('blockchain_id', $id)
                    ->where('name', '!=', 'WalletConnect')
                    ->when($enabledWallets->isNotEmpty(), fn ($query) => $query->whereIn('slug', $enabledWallets))
                    ->select('id', 'name', 'slug')
                    ->get();

        return response()->json(['wallets' => $wallets]);
    }
}
