<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NccTransaction;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function summary()
    {
        $now = Carbon::now();
        $userId = Auth::id();

        $yearStart = $now->copy()->startOfYear();
        $monthStart = $now->copy()->startOfMonth();
        $weekStart = $now->copy()->startOfWeek();
        $today = $now->copy()->startOfDay();

        $base = NccTransaction::where('user_id', $userId)
            ->where('ncc_txn_status', 0);

        $yearCount = (clone $base)->where('last_updated', '>=', $yearStart)->count();
        $monthCount = (clone $base)->where('last_updated', '>=', $monthStart)->count();
        $weekCount = (clone $base)->where('last_updated', '>=', $weekStart)->count();
        $todayCount = (clone $base)->where('last_updated', '>=', $today)->count();

        $latest = (clone $base)
            ->where('ncc_settlement_status', 1)
            ->orderByDesc('last_updated')
            ->limit(10)
            ->get();

        $unsettled = (clone $base)
            ->where('ncc_settlement_status', '!=', 1)
            ->count();

        return response()->json([
            'counts' => [
                'year' => $yearCount,
                'month' => $monthCount,
                'week' => $weekCount,
                'today' => $todayCount,
            ],
            'latest' => $latest,
            'unsettled_count' => $unsettled,
        ]);
    }
}

