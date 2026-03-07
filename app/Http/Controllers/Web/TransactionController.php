<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\NccTransaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $userId = Auth::id();

        $query = NccTransaction::with('user')
            ->where('user_id', $userId)
            ->where('ncc_txn_status', 0)
            ->where('ipgID', 11);

        if ($request->filled('transaction_id')) {
            $query->where('ncc_txn_id', 'like', '%' . $request->input('transaction_id') . '%');
        }

        if ($request->filled('mobile')) {
            $query->where('ncc_msisdn', 'like', '%' . $request->input('mobile') . '%');
        }

        if ($request->filled('email')) {
            $query->where('ncc_email', 'like', '%' . $request->input('email') . '%');
        }

        if ($request->filled('from_date')) {
            $query->whereDate('last_updated', '>=', $request->date('from_date'));
        }

        if ($request->filled('to_date')) {
            $query->whereDate('last_updated', '<=', $request->date('to_date'));
        }

        $transactions = $query
            ->orderByDesc('last_updated')
            ->paginate(10)
            ->withQueryString();

        return view('transactions.index', [
            'transactions' => $transactions,
        ]);
    }
}

