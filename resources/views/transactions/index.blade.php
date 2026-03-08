@extends('layouts.app')

@section('content')
    <h4 class="mb-4">Transaction History</h4>

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('transactions.index') }}" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">From Date</label>
                    <input type="date" name="from_date" value="{{ request('from_date') }}" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label">To Date</label>
                    <input type="date" name="to_date" value="{{ request('to_date') }}" class="form-control">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Transaction ID</label>
                    <input type="text" name="transaction_id" value="{{ request('transaction_id') }}" class="form-control">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Mobile</label>
                    <input type="text" name="mobile" value="{{ request('mobile') }}" class="form-control">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Email</label>
                    <input type="text" name="email" value="{{ request('email') }}" class="form-control" placeholder="">
                </div>
                <div class="col-12 d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary me-2">Search</button>
                    <a href="{{ route('transactions.index') }}" class="btn btn-outline-secondary">Reset</a>
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
                        <th>ID</th>
                        <th>Reference</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Mobile</th>
                        <th>Email</th>
                        <th>Date</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($transactions as $tx)
                        <tr>
                            <td>{{ $tx->ncc_txn_id ?? $tx->id }}</td>
                            <td>{{ $tx->ncc_client_reference ?? '-' }}</td>
                            <td>{{ isset($tx->ncc_amount) && $tx->ncc_amount !== '' ? 'LKR ' . number_format((float) $tx->ncc_amount, 2) : '-' }}</td>
                            <td>{{ $tx->status ?? '-' }}</td>
                            <td>{{ $tx->ncc_msisdn ?? '-' }}</td>
                            <td>{{ $tx->ncc_email ?? $tx->user->user_email ?? '-' }}</td>
                            <td>@if(!empty($tx->last_updated))<span data-utc-datetime="{{ \Carbon\Carbon::parse($tx->last_updated)->utc()->toIso8601String() }}"></span>@else-@endif</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted">No transactions found.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 p-3 border-top">
                <div class="text-muted small">
                    @if($transactions->total() > 0)
                        Showing {{ $transactions->firstItem() }} to {{ $transactions->lastItem() }} of {{ $transactions->total() }} results
                    @else
                        No results
                    @endif
                </div>
                <div>
                    {{ $transactions->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection

