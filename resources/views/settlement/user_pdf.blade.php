<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Settlement Summary - {{ $user->user_id ?? '' }}</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; }
        h1, h2, h3 { margin: 0 0 8px 0; }
        .meta { margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #444; padding: 4px 6px; text-align: left; }
        th { background: #f3f3f3; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .mt-2 { margin-top: 8px; }
    </style>
</head>
<body>
    <h2>Settlement Details</h2>
    <div class="meta">
        <div><strong>User ID:</strong> {{ $user->user_id ?? '-' }}</div>
        <div><strong>User Name:</strong> {{ trim(($user->user_first_name ?? '') . ' ' . ($user->user_last_name ?? '')) ?: ($user->user_email ?? '-') }}</div>
    </div>

    <table class="mt-2">
        <tr>
            <th style="width: 40%;">SETTLEMENT REFERENCE</th>
            <td>{{ \Carbon\Carbon::now($timezone)->format('Ymd') }}</td>
        </tr>
    </table>

    <table class="mt-2">
        <thead>
        <tr>
            <th class="text-center">TXN_ID</th>
            <th class="text-right">Amount (LKR)</th>
            <th class="text-right">Commission (LKR)</th>
            <th class="text-right">Net Amount (LKR)</th>
            <th class="text-center">Date / Time ({{ $timezone }})</th>
        </tr>
        </thead>
        <tbody>
        @php
            $totalAmount = 0;
            $totalCommission = 0;
            $totalNetAmount = 0;
            $commissionPct = $transactions->first()?->user?->nccClient?->commission ?? 0;
        @endphp
        @foreach($transactions as $tx)
            @php
                $amount = isset($tx->ncc_amount) && $tx->ncc_amount !== '' ? (float) $tx->ncc_amount : 0;
                $commission = $amount * (float) $commissionPct / 100;
                $netAmount = $amount - $commission;
                $totalAmount += $amount;
                $totalCommission += $commission;
                $totalNetAmount += $netAmount;
                $dt = $tx->last_updated
                    ? \Carbon\Carbon::parse($tx->last_updated, 'UTC')->setTimezone($timezone)
                    : null;
            @endphp
            <tr>
                <td class="text-center">{{ $tx->ncc_txn_id ?? $tx->id }}</td>
                <td class="text-right">{{ $amount > 0 ? number_format($amount, 2) : '-' }}</td>
                <td class="text-right">{{ $amount > 0 ? number_format($commission, 2) : '-' }}</td>
                <td class="text-right">{{ $amount > 0 ? number_format($netAmount, 2) : '-' }}</td>
                <td class="text-center">{{ $dt ? $dt->format('Y-m-d H:i:s') : '-' }}</td>
            </tr>
        @endforeach
        </tbody>
        <tfoot>
        <tr>
            <th class="text-right">TOTAL</th>
            <th class="text-right">{{ number_format($totalAmount, 2) }}</th>
            <th class="text-right">{{ number_format($totalCommission, 2) }}</th>
            <th class="text-right">{{ number_format($totalNetAmount, 2) }}</th>
            <th></th>
        </tr>
        </tfoot>
    </table>
</body>
</html>

