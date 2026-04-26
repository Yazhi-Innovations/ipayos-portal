<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\NccTransaction;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Collection;

class SettlementHistoryController extends Controller
{
    private const SETTLEMENTS_PER_PAGE = 10;

    public function index(Request $request)
    {
        $userId = Auth::id();

        $query = $this->baseSettlementHistoryQueryForUser($userId)
            ->with('user.nccClient')
            ->orderByDesc('last_updated');

        if ($request->filled('transaction_id')) {
            $txInput = (string) $request->input('transaction_id');
            $refQuery = $this->baseSettlementHistoryQueryForUser($userId)
                ->where('ncc_txn_id', 'like', '%' . $txInput . '%');
            $refs = $refQuery->pluck('ncc_settlement_reference')
                ->map(fn ($v) => (string) ($v ?? ''))
                ->unique()
                ->values();
            if ($refs->isEmpty()) {
                $query->whereRaw('0 = 1');
            } else {
                $query->where(function ($q) use ($refs) {
                    foreach ($refs as $ref) {
                        if ($ref === '') {
                            $q->orWhere(function ($q2) {
                                $q2->whereNull('ncc_settlement_reference')->orWhere('ncc_settlement_reference', '');
                            });
                        } else {
                            $q->orWhere('ncc_settlement_reference', $ref);
                        }
                    }
                });
            }
        }

        if ($request->filled('from_date') || $request->filled('to_date')) {
            $query->whereRaw("TRIM(ncc_settlement_reference) RLIKE ?", ['^[0-9]{8}$']);
            if ($request->filled('from_date')) {
                $fromYmd = $request->date('from_date')->format('Ymd');
                $query->whereRaw('TRIM(ncc_settlement_reference) >= ?', [$fromYmd]);
            }
            if ($request->filled('to_date')) {
                $toYmd = $request->date('to_date')->format('Ymd');
                $query->whereRaw('TRIM(ncc_settlement_reference) <= ?', [$toYmd]);
            }
        }

        $transactions = $query->get();

        $allGroups = $this->buildSettlementGroups($transactions);

        $page = max(1, (int) $request->get('page', 1));
        $perPage = self::SETTLEMENTS_PER_PAGE;
        $total = $allGroups->count();
        $slice = $allGroups->slice(($page - 1) * $perPage, $perPage)->values();

        $groups = new LengthAwarePaginator(
            $slice,
            $total,
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        return view('settlement-history.index', [
            'groups' => $groups,
        ]);
    }

    private function baseSettlementHistoryQueryForUser(int $userId): \Illuminate\Database\Eloquent\Builder
    {
        $q = NccTransaction::query()
            ->where('user_id', $userId)
            ->where('ncc_txn_status', 0)
            ->where('ncc_settlement_status', 1)
            ->where('ipgID', 11);
        if (Schema::hasColumn('ncc_transaction', 'isTest')) {
            $q->where('isTest', 0);
        }

        return $q;
    }

    /**
     * @return Collection<int, array{settlement_id: string, settlement_date: string|null, rows: \Illuminate\Support\Collection, total_amount: float, total_commission: float, total_net: float}>
     */
    private function buildSettlementGroups(Collection $transactions): Collection
    {
        if ($transactions->isEmpty()) {
            return collect();
        }

        $grouped = $transactions->groupBy(function (NccTransaction $tx) {
            return (string) ($tx->ncc_settlement_reference ?? '');
        });

        return $grouped->map(function (Collection $items, string $ref) {
            $settlementId = $ref === '' ? '-' : $ref;
            $settlementDate = $this->settlementDateFromReference($ref);

            $totals = ['amount' => 0.0, 'commission' => 0.0, 'net' => 0.0];

            foreach ($items as $tx) {
                $amount = isset($tx->ncc_amount) && $tx->ncc_amount !== '' ? (float) $tx->ncc_amount : 0.0;
                $pct = (float) ($tx->user?->nccClient?->commission ?? 0);
                $commission = $amount * $pct / 100.0;
                $net = $amount - $commission;
                $totals['amount'] += $amount;
                $totals['commission'] += $commission;
                $totals['net'] += $net;
            }

            $items = $items->sortByDesc('last_updated')->values();

            return [
                'settlement_id' => $settlementId,
                'settlement_date' => $settlementDate,
                'rows' => $items,
                'total_amount' => $totals['amount'],
                'total_commission' => $totals['commission'],
                'total_net' => $totals['net'],
            ];
        })->values()->sortByDesc(function (array $g) {
            $first = $g['rows']->first();
            if (!$first?->last_updated) {
                return 0;
            }
            return strtotime((string) $first->last_updated);
        })->values();
    }

    private function settlementDateFromReference(string $ref): ?string
    {
        $ref = trim($ref);
        if ($ref === '' || ! preg_match('/^\d{8}$/', $ref)) {
            return null;
        }
        try {
            return \Carbon\Carbon::createFromFormat('Ymd', $ref)->format('d/m/Y');
        } catch (\Throwable) {
            return null;
        }
    }
}
