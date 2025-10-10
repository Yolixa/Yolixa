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
        $wallets = WalletType::where('blockchain_id', $id)
                    ->select('id', 'name')
                    ->get();

        return response()->json(['wallets' => $wallets]);
    }
}
