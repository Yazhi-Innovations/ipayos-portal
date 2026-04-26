@extends('layouts.app')

@section('content')
    <h4 class="mb-4">Settlement History</h4>
    <p class="text-muted mb-4">Past settlements for your account (live transactions).</p>

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('settlement.history.index') }}" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">From date <span class="text-muted small">(settlement date)</span></label>
                    <input type="date" name="from_date" value="{{ request('from_date') }}" class="form-control" title="Matches 8-digit Settlement ID date (YYYYMMDD)">
                </div>
                <div class="col-md-3">
                    <label class="form-label">To date <span class="text-muted small">(settlement date)</span></label>
                    <input type="date" name="to_date" value="{{ request('to_date') }}" class="form-control" title="Matches 8-digit Settlement ID date (YYYYMMDD)">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Transaction ID</label>
                    <input type="text" name="transaction_id" value="{{ request('transaction_id') }}" class="form-control" placeholder="Finds full settlement">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">Search</button>
                    <a href="{{ route('settlement.history.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                    <tr>
                        <th>Settlement ID</th>
                        <th>Date</th>
                        <th>TXN ID</th>
                        <th>Amount</th>
                        <th>Commission</th>
                        <th>Net Amount</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($groups as $group)
                        @php
                            $collapseId = 'settle-'.$groups->currentPage().'-'.$loop->index;
                            $rowCount = $group['rows']->count();
                        @endphp
                        <tr class="table-light">
                            <td>{{ $group['settlement_id'] }}</td>
                            <td>{{ $group['settlement_date'] ?? '-' }}</td>
                            <td>
                                <button type="button"
                                        class="btn btn-sm btn-outline-primary settle-transactions-toggle"
                                        data-bs-toggle="collapse"
                                        data-bs-target="#{{ $collapseId }}"
                                        aria-expanded="false"
                                        aria-controls="{{ $collapseId }}">
                                    <span class="settle-label-expand">Show</span>
                                    <span class="settle-label-collapse d-none">Hide</span>
                                </button>
                                @if($rowCount > 0)
                                    <span class="text-muted small">({{ $rowCount }} {{ $rowCount === 1 ? 'transaction' : 'transactions' }})</span>
                                @endif
                            </td>
                            <td>LKR {{ number_format($group['total_amount'], 2) }}</td>
                            <td>LKR {{ number_format($group['total_commission'], 2) }}</td>
                            <td>LKR {{ number_format($group['total_net'], 2) }}</td>
                        </tr>
                        <tr>
                            <td colspan="6" class="p-0 border-top-0">
                                <div class="collapse" id="{{ $collapseId }}">
                                    <div class="p-2 border-bottom">
                                        <table class="table table-sm table-bordered mb-0">
                                            <thead class="table-light">
                                            <tr>
                                                <th>TXN ID</th>
                                                <th>Amount</th>
                                                <th>Commission</th>
                                                <th>Net Amount</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            @foreach($group['rows'] as $tx)
                                                @php
                                                    $amount = isset($tx->ncc_amount) && $tx->ncc_amount !== '' ? (float) $tx->ncc_amount : 0;
                                                    $pct = (float) ($tx->user?->nccClient?->commission ?? 0);
                                                    $commission = $amount * $pct / 100;
                                                    $net = $amount - $commission;
                                                @endphp
                                                <tr>
                                                    <td>{{ $tx->ncc_txn_id ?? $tx->id }}</td>
                                                    <td>{{ $amount > 0 ? 'LKR ' . number_format($amount, 2) : '-' }}</td>
                                                    <td>{{ $amount > 0 ? 'LKR ' . number_format($commission, 2) : '-' }}</td>
                                                    <td>{{ $amount > 0 ? 'LKR ' . number_format($net, 2) : '-' }}</td>
                                                </tr>
                                            @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted">No settlement history found.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 p-3 border-top">
                <div class="text-muted small">
                    @if($groups->total() > 0)
                        Showing settlements {{ $groups->firstItem() }} to {{ $groups->lastItem() }} of {{ $groups->total() }}
                    @else
                        No results
                    @endif
                </div>
                <div>
                    {{ $groups->withQueryString()->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
(function () {
    document.querySelectorAll('.settle-transactions-toggle').forEach(function (btn) {
        var targetId = btn.getAttribute('data-bs-target');
        if (!targetId) return;
        var el = document.querySelector(targetId);
        if (!el) return;
        el.addEventListener('shown.bs.collapse', function () {
            btn.setAttribute('aria-expanded', 'true');
            var ex = btn.querySelector('.settle-label-expand');
            var cl = btn.querySelector('.settle-label-collapse');
            if (ex) ex.classList.add('d-none');
            if (cl) cl.classList.remove('d-none');
        });
        el.addEventListener('hidden.bs.collapse', function () {
            btn.setAttribute('aria-expanded', 'false');
            var ex = btn.querySelector('.settle-label-expand');
            var cl = btn.querySelector('.settle-label-collapse');
            if (ex) ex.classList.remove('d-none');
            if (cl) cl.classList.add('d-none');
        });
    });
})();
</script>
@endpush
