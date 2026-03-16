<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\NccTransaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SettlementController extends Controller
{
    /**
     * Pending settlement cutoff: transactions with last_updated at or before this (UTC) are included.
     * Uses "at least 1 working day before" in the given timezone (Mon–Fri; weekends skipped).
     */
    private function settlementQuery(Carbon $thresholdUtc)
    {
        return NccTransaction::with('user.nccClient')
            ->where('ncc_txn_status', 0)
            ->where('ipgID', 11)
            ->where('ncc_settlement_status', '!=', 1)
            ->where('last_updated', '<=', $thresholdUtc)
            ->orderBy('user_id')
            ->orderByDesc('last_updated');
    }

    /**
     * Threshold = end of the day two working days ago in web user timezone, converted to UTC.
     * Only transactions with last_updated <= this are included (i.e. at least 2 full working days ago).
     * Working days = Monday–Friday; going back from today skips weekends.
     */
    private function getPendingSettlementThresholdUtc(string $userTimezone): Carbon
    {
        $cutoff = Carbon::now($userTimezone);
        $workingDaysGoneBack = 0;

        while ($workingDaysGoneBack < 2) {
            $cutoff->subDay();
            if (! $cutoff->isWeekend()) {
                $workingDaysGoneBack++;
            }
        }

        return $cutoff->endOfDay()->setTimezone('UTC');
    }

    public function index(\Illuminate\Http\Request $request)
    {
        $userTimezone = $this->getWebUserTimezone($request);
        $thresholdUtc = $this->getPendingSettlementThresholdUtc($userTimezone);

        $transactions = $this->settlementQuery($thresholdUtc)
            ->paginate(15)
            ->withQueryString();

        return view('settlement.index', [
            'transactions' => $transactions,
            'timezone' => $userTimezone,
        ]);
    }

    /** Get web user timezone from request (passed by frontend) or fallback to user/profile or UTC. */
    private function getWebUserTimezone(\Illuminate\Http\Request $request): string
    {
        $tz = $request->query('timezone');
        if (is_string($tz) && $tz !== '' && in_array($tz, timezone_identifiers_list(), true)) {
            return $tz;
        }

        return Auth::user()->timezone ?? config('app.timezone', 'UTC');
    }

    public function exportCsv(\Illuminate\Http\Request $request): StreamedResponse
    {
        $userTimezone = $this->getWebUserTimezone($request);
        $thresholdUtc = $this->getPendingSettlementThresholdUtc($userTimezone);
        $all = $this->settlementQuery($thresholdUtc)->get();

        $grouped = $all->groupBy('user_id');

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="YazPayout_' . date('Ymd') . '.csv"',
        ];

        $escapeCsvField = function ($value): string {
            $value = (string) $value;
            $value = str_replace(["\r", "\n", ','], ' ', $value);

            return trim($value);
        };

        return new StreamedResponse(function () use ($grouped, $escapeCsvField, $all, $userTimezone) {
            $out = fopen('php://output', 'w');
            if ($out === false) {
                return;
            }
            fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM

            $headerRow = [
                'Origin Account number (15 Chars)',
                'Origin Account Name',
                'Value Date (DD/MM/YYYY)',
                'Benificiary Bank Code',
                'Benificiary Branch Code',
                'Benificiary Account Number',
                'Benificiary Account Name',
                'Amount',
                'Particulars',
                'References',
            ];
            fwrite($out, implode(',', array_map($escapeCsvField, $headerRow)) . "\n");

            $globalTxnIds = $all->map(fn ($tx) => $tx->ncc_txn_id ?? $tx->id)->filter()->values();
            $globalLatestDate = $all->filter(fn ($tx) => $tx->last_updated)->map(fn ($tx) => Carbon::parse($tx->last_updated, 'UTC')->setTimezone($userTimezone))->sortByDesc(fn ($c) => $c->timestamp)->first();
            $globalStartTxn = $globalTxnIds->isNotEmpty() ? (string) $globalTxnIds->min() : '';
            $globalEndTxn = $globalTxnIds->isNotEmpty() ? (string) $globalTxnIds->max() : '';
            $todayYmd = Carbon::now($userTimezone)->format('Ymd');

            foreach ($grouped as $userId => $transactions) {
                $user = $transactions->first()->user;
                if (!$user) {
                    continue;
                }
                $totalAmount = 0;
                $totalCommission = 0;
                $txnIds = [];
                $latestDate = null;

                foreach ($transactions as $tx) {
                    $amount = isset($tx->ncc_amount) && $tx->ncc_amount !== '' ? (float) $tx->ncc_amount : 0;
                    $totalAmount += $amount;
                    $pct = $tx->user?->nccClient?->commission ?? 0;
                    $totalCommission += $amount * (float) $pct / 100;
                    $txnIds[] = $tx->ncc_txn_id ?? $tx->id;
                    if ($tx->last_updated) {
                        $d = Carbon::parse($tx->last_updated, 'UTC')->setTimezone($userTimezone);
                        if ($latestDate === null || $d->gt($latestDate)) {
                            $latestDate = $d;
                        }
                    }
                }

                $netAmount = $totalAmount - $totalCommission;
                $valueDate = $latestDate ? $latestDate->format('d/m/Y') : '';
                $startTxn = count($txnIds) ? (string) min($txnIds) : '';
                $endTxn = count($txnIds) ? (string) max($txnIds) : '';
                $particulars = $startTxn && $endTxn ? "{$startTxn}-{$endTxn}-{$todayYmd}" : '';
                $references = $startTxn && $endTxn ? "{$startTxn}-{$endTxn}" : '';

                $row = [
                    '030100088888009',
                    'Yazhi Innovation (Pvt) Ltd',
                    $valueDate,
                    $user->settlement_bank ?? '',
                    $user->settlement_branch ?? '',
                    $user->settlement_acc_number ?? '',
                    $user->settlement_name ?? '',
                    number_format($netAmount, 2, '.', ''),
                    $particulars,
                    $references,
                ];
                fwrite($out, implode(',', array_map($escapeCsvField, $row)) . "\n");
            }

            $totalCommissionAll = 0;
            $totalBankCommissionAll = 0;
            foreach ($all as $tx) {
                $amount = isset($tx->ncc_amount) && $tx->ncc_amount !== '' ? (float) $tx->ncc_amount : 0;
                $pct = $tx->user?->nccClient?->commission ?? 0;
                $totalCommissionAll += $amount * (float) $pct / 100;
                $totalBankCommissionAll += $amount * 0.023;
            }
            $finalAmount = $totalCommissionAll - $totalBankCommissionAll;

            $finalValueDate = $globalLatestDate ? $globalLatestDate->format('d/m/Y') : '';
            $particularsDateYmd = Carbon::now($userTimezone)->format('Ymd');
            $finalParticulars = ($globalStartTxn && $globalEndTxn) ? "C-{$globalStartTxn}-{$globalEndTxn}-{$particularsDateYmd}" : '';
            $finalReferences = ($globalStartTxn && $globalEndTxn) ? "C-{$globalStartTxn}-{$globalEndTxn}" : '';

            $finalRow = [
                '030100088888009',
                'Yazhi Innovation (Pvt) Ltd',
                $finalValueDate,
                '7287',
                '68',
                '068013289392001',
                'Yazhi Innovation (Pvt) Ltd',
                number_format($finalAmount, 2, '.', ''),
                $finalParticulars,
                $finalReferences,
            ];
            fwrite($out, implode(',', array_map($escapeCsvField, $finalRow)) . "\n");

            fclose($out);
        }, 200, $headers);
    }

    public function downloadUserPdf(\Illuminate\Http\Request $request, int $user): \Symfony\Component\HttpFoundation\Response
    {
        $userTimezone = $this->getWebUserTimezone($request);
        $thresholdUtc = $this->getPendingSettlementThresholdUtc($userTimezone);

        $transactions = NccTransaction::with('user.nccClient')
            ->where('user_id', $user)
            ->where('ncc_txn_status', 0)
            ->where('ipgID', 11)
            ->where('ncc_settlement_status', '!=', 1)
            ->where('last_updated', '<=', $thresholdUtc)
            ->orderByDesc('last_updated')
            ->get();

        if ($transactions->isEmpty()) {
            abort(404, 'No pending settlement transactions found for this user.');
        }

        $userModel = $transactions->first()->user ?? User::find($user);

        $html = view('settlement.user_pdf', [
            'user' => $userModel,
            'transactions' => $transactions,
            'timezone' => $userTimezone,
        ])->render();

        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = 'settlement_user_' . $user . '.pdf';

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function settleUser(\Illuminate\Http\Request $request): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer'],
            'settlement_reference' => ['required', 'string', 'max:255'],
        ]);

        $userId = (int) $data['user_id'];
        $reference = $data['settlement_reference'];

        $userTimezone = $this->getWebUserTimezone($request);
        $thresholdUtc = $this->getPendingSettlementThresholdUtc($userTimezone);

        $query = NccTransaction::where('user_id', $userId)
            ->where('ncc_txn_status', 0)
            ->where('ipgID', 11)
            ->where('ncc_settlement_status', '!=', 1)
            ->where('last_updated', '<=', $thresholdUtc);

        $count = $query->count();

        if ($count > 0) {
            $query->update([
                'ncc_settlement_status' => 1,
                'ncc_settlement_reference' => $reference,
            ]);
        }

        return back()->with('status', $count > 0 ? 'Settled ' . $count . ' transactions for user ID ' . $userId . '.' : 'No pending transactions to settle for user ID ' . $userId . '.');
    }
}
