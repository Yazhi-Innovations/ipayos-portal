@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Dashboard</h4>
        <div class="btn-group" role="group" aria-label="Mode toggle">
            <a href="{{ route('dashboard', ['mode' => 'live']) }}"
               class="btn btn-sm {{ ($mode ?? 'live') === 'live' ? 'btn-primary' : 'btn-outline-primary' }}">
                Live
            </a>
            <a href="{{ route('dashboard', ['mode' => 'test']) }}"
               class="btn btn-sm {{ ($mode ?? 'live') === 'test' ? 'btn-primary' : 'btn-outline-primary' }}">
                Test
            </a>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted text-uppercase">Today</h6>
                    <h3 class="mb-1">{{ $counts['today'] ?? 0 }}</h3>
                    <small class="text-muted">Transactions</small>
                    <div class="mt-2 pt-2 border-top">
                        <strong>LKR {{ number_format($amounts['today'] ?? 0, 2) }}</strong>
                        <small class="text-muted d-block">Total Amount</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted text-uppercase">This Week</h6>
                    <h3 class="mb-1">{{ $counts['week'] ?? 0 }}</h3>
                    <small class="text-muted">Transactions</small>
                    <div class="mt-2 pt-2 border-top">
                        <strong>LKR {{ number_format($amounts['week'] ?? 0, 2) }}</strong>
                        <small class="text-muted d-block">Total Amount</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted text-uppercase">This Month</h6>
                    <h3 class="mb-1">{{ $counts['month'] ?? 0 }}</h3>
                    <small class="text-muted">Transactions</small>
                    <div class="mt-2 pt-2 border-top">
                        <strong>LKR {{ number_format($amounts['month'] ?? 0, 2) }}</strong>
                        <small class="text-muted d-block">Total Amount</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted text-uppercase">This Year</h6>
                    <h3 class="mb-1">{{ $counts['year'] ?? 0 }}</h3>
                    <small class="text-muted">Transactions</small>
                    <div class="mt-2 pt-2 border-top">
                        <strong>LKR {{ number_format($amounts['year'] ?? 0, 2) }}</strong>
                        <small class="text-muted d-block">Total Amount</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white">
                    <h6 class="mb-0">Latest 10 Settled Transactions</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($latest as $tx)
                                <tr>
                                    <td>{{ $tx->ncc_txn_id ?? $tx->id }}</td>
                                    <td>{{ isset($tx->ncc_amount) && $tx->ncc_amount !== '' ? 'LKR ' . number_format((float) $tx->ncc_amount, 2) : '-' }}</td>
                                    <td>{{ $tx->status ?? '-' }}</td>
                                    <td>@if(!empty($tx->last_updated))<span data-utc-datetime="{{ \Carbon\Carbon::parse($tx->last_updated)->utc()->toIso8601String() }}"></span>@else-@endif</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted">No settled transactions found.</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white">
                    <h6 class="mb-0">Unsettled Transactions</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($unsettled as $tx)
                                <tr>
                                    <td>{{ $tx->ncc_txn_id ?? $tx->id }}</td>
                                    <td>{{ isset($tx->ncc_amount) && $tx->ncc_amount !== '' ? 'LKR ' . number_format((float) $tx->ncc_amount, 2) : '-' }}</td>
                                    <td>{{ $tx->status ?? '-' }}</td>
                                    <td>@if(!empty($tx->last_updated))<span data-utc-datetime="{{ \Carbon\Carbon::parse($tx->last_updated)->utc()->toIso8601String() }}"></span>@else-@endif</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted">No unsettled transactions.</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="p-2">
                        {{ $unsettled->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

