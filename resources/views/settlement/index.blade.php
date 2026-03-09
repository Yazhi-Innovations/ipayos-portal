@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">Settlement</h4>
            <p class="text-muted mb-0">Pending settlement transactions (unsettled) that occurred at least 1 working day ago (weekdays only, in your timezone).</p>
        </div>
        <a id="btn-export-csv" href="{{ route('settlement.export.csv', ['timezone' => $timezone ?? 'UTC']) }}" class="btn btn-primary">Generate Settlement CSV</a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                    <tr>
                        <th>User ID</th>
                        <th>Commission %</th>
                        <th>TXN_ID</th>
                        <th>Amount</th>
                        <th>Commission</th>
                        <th>Bank Commission</th>
                        <th>Date</th>
                    </tr>
                    </thead>
                    <tbody>
                    @php
                        $groupedByUser = $transactions->groupBy('user_id');
                    @endphp
                    @forelse($groupedByUser as $userId => $userTransactions)
                        @php
                            /** @var \Illuminate\Support\Collection $userTransactions */
                            $rowspan = $userTransactions->count();
                            $firstTx = $userTransactions->first();
                            $commissionPctForUser = $firstTx?->user?->nccClient?->commission ?? null;
                        @endphp
                        @foreach($userTransactions as $index => $tx)
                            @php
                                $amount = isset($tx->ncc_amount) && $tx->ncc_amount !== '' ? (float) $tx->ncc_amount : 0;
                                $commission = $amount * (float) ($commissionPctForUser ?? 0) / 100;
                                $bankCommission = $amount * 0.023;
                            @endphp
                            <tr>
                                @if($index === 0)
                                    <td rowspan="{{ $rowspan }}">
                                        {{ $userId ?? '-' }}
                                        <div class="mt-1 d-flex flex-column gap-1">
                                            <a href="{{ route('settlement.user.pdf', ['user' => $userId, 'timezone' => $timezone ?? 'UTC']) }}"
                                               class="btn btn-sm btn-outline-primary">
                                                Download
                                            </a>
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-success btn-settle-user"
                                                    data-user-id="{{ $userId ?? '' }}">
                                                Settle
                                            </button>
                                        </div>
                                    </td>
                                    <td rowspan="{{ $rowspan }}">{{ $commissionPctForUser !== null && $commissionPctForUser !== '' ? number_format((float) $commissionPctForUser, 2) . '%' : '-' }}</td>
                                @endif
                                <td>{{ $tx->ncc_txn_id ?? $tx->id }}</td>
                                <td>{{ $amount > 0 ? 'LKR ' . number_format($amount, 2) : '-' }}</td>
                                <td>{{ $amount > 0 ? 'LKR ' . number_format($commission, 2) : '-' }}</td>
                                <td>{{ $amount > 0 ? 'LKR ' . number_format($bankCommission, 2) : '-' }}</td>
                                <td>@if(!empty($tx->last_updated))<span data-utc-datetime="{{ \Carbon\Carbon::parse($tx->last_updated)->utc()->toIso8601String() }}"></span>@else-@endif</td>
                            </tr>
                        @endforeach
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted">No pending settlement transactions.</td>
                        </tr>
                    @endforelse
                    </tbody>
                    @if($transactions->isNotEmpty())
                    <tfoot class="table-light fw-bold">
                        <tr>
                            <td colspan="3" class="text-end">TOTAL</td>
                            @php
                                $totalAmount = 0;
                                $totalCommission = 0;
                                $totalBankCommission = 0;
                                foreach ($transactions as $tx) {
                                    $amt = isset($tx->ncc_amount) && $tx->ncc_amount !== '' ? (float) $tx->ncc_amount : 0;
                                    $totalAmount += $amt;
                                    $pct = $tx->user?->nccClient?->commission ?? 0;
                                    $totalCommission += $amt * (float) $pct / 100;
                                    $totalBankCommission += $amt * 0.023;
                                }
                            @endphp
                            <td>LKR {{ number_format($totalAmount, 2) }}</td>
                            <td>LKR {{ number_format($totalCommission, 2) }}</td>
                            <td>LKR {{ number_format($totalBankCommission, 2) }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                    @endif
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

    <!-- Settle Modal -->
    <div class="modal fade" id="settleModal" tabindex="-1" aria-labelledby="settleModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="settleModalLabel">Settle Transactions</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <form method="POST" action="{{ route('settlement.user.settle', [], false) }}">
            @csrf
            <div class="modal-body">
              <div class="mb-3">
                <label class="form-label">User ID</label>
                <div id="settle-user-label" class="fw-semibold">-</div>
              </div>
              <input type="hidden" name="user_id" id="settle-user-id" value="">
              <div class="mb-3">
                <label for="settlement-reference" class="form-label">Settlement Reference</label>
                <input type="text" class="form-control" id="settlement-reference" name="settlement_reference" required>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-success">Update</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    @push('scripts')
    <script>
    (function () {
        var btn = document.getElementById('btn-export-csv');
        if (btn) {
            try {
                var tz = Intl.DateTimeFormat().resolvedOptions().timeZone;
                if (tz) {
                    var base = '{{ route('settlement.export.csv') }}';
                    btn.setAttribute('href', base + '?timezone=' + encodeURIComponent(tz));
                }
            } catch (e) {}
        }

        var settleButtons = document.querySelectorAll('.btn-settle-user');
        var settleModalEl = document.getElementById('settleModal');
        if (settleModalEl && settleButtons.length > 0 && window.bootstrap && window.bootstrap.Modal) {
            var settleModal = new bootstrap.Modal(settleModalEl);
            var userInput = document.getElementById('settle-user-id');
            var userLabel = document.getElementById('settle-user-label');

            settleButtons.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var uid = this.getAttribute('data-user-id') || '';
                    if (userInput) userInput.value = uid;
                    if (userLabel) userLabel.textContent = uid || '-';
                    settleModal.show();
                });
            });
        }
    })();
    </script>
    @endpush
@endsection
