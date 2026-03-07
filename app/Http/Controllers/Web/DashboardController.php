<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\NccTransaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $now = Carbon::now();
        $userId = Auth::id();

        $mode = $request->query('mode', 'live') === 'test' ? 'test' : 'live';

        $yearStart = $now->copy()->startOfYear();
        $monthStart = $now->copy()->startOfMonth();
        $weekStart = $now->copy()->startOfWeek();
        $today = $now->copy()->startOfDay();

        $base = NccTransaction::where('user_id', $userId)
            ->where('ncc_txn_status', 0)
            ->when($mode === 'live', fn ($q) => $q->where('ipgID', 11))
            ->when($mode === 'test', fn ($q) => $q->where('ipgID', '!=', 11));

        $yearCount = (clone $base)->where('last_updated', '>=', $yearStart)->count();
        $monthCount = (clone $base)->where('last_updated', '>=', $monthStart)->count();
        $weekCount = (clone $base)->where('last_updated', '>=', $weekStart)->count();
        $todayCount = (clone $base)->where('last_updated', '>=', $today)->count();

        $todayAmount = (clone $base)->where('last_updated', '>=', $today)->sum('ncc_amount');
        $weekAmount = (clone $base)->where('last_updated', '>=', $weekStart)->sum('ncc_amount');
        $monthAmount = (clone $base)->where('last_updated', '>=', $monthStart)->sum('ncc_amount');
        $yearAmount = (clone $base)->where('last_updated', '>=', $yearStart)->sum('ncc_amount');

        $latest = (clone $base)
            ->where('ncc_settlement_status', 1)
            ->orderByDesc('last_updated')
            ->limit(10)
            ->get();

        $unsettled = (clone $base)
            ->where('ncc_settlement_status', '!=', 1)
            ->orderByDesc('last_updated')
            ->paginate(10)
            ->withQueryString();

        return view('dashboard', [
            'counts' => [
                'year' => $yearCount,
                'month' => $monthCount,
                'week' => $weekCount,
                'today' => $todayCount,
            ],
            'amounts' => [
                'today' => $todayAmount,
                'week' => $weekAmount,
                'month' => $monthAmount,
                'year' => $yearAmount,
            ],
            'latest' => $latest,
            'unsettled' => $unsettled,
            'mode' => $mode,
        ]);
    }
}

