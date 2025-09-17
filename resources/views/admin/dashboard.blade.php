@extends('layouts.app')

@section('content')
<div class="container">
  <div class="d-flex align-items-center justify-content-between mb-4">
    <div>
      <h3 class="mb-0">Admin Dashboard</h3>
      <small class="text-muted">Overview of clients, revenue, and infrastructure status.</small>
    </div>
    @if(session('status'))
      <span class="badge badge-success p-2">{{ session('status') }}</span>
    @endif
  </div>
  @php $currency = \App\Support\Settings::get('company.currency', 'IDR'); @endphp
  <div class="row">
    <div class="col-md-3 mb-3">
      <div class="card shadow-sm h-100">
        <div class="card-body">
          <h6 class="text-muted">Active Services</h6>
          <h2 class="mb-0">{{ number_format($stats['active_services']) }}</h2>
          <span class="text-success small">{{ number_format($stats['pending_services']) }} pending</span>
        </div>
      </div>
    </div>
    <div class="col-md-3 mb-3">
      <div class="card shadow-sm h-100">
        <div class="card-body">
          <h6 class="text-muted">MRR (This Month)</h6>
          <h2 class="mb-0">{{ number_format($stats['revenue_month'], 2) }} {{ $currency }}</h2>
          <span class="text-muted small">{{ number_format($stats['revenue_30_days'], 2) }} {{ $currency }} in last 30 days</span>
        </div>
      </div>
    </div>
    <div class="col-md-3 mb-3">
      <div class="card shadow-sm h-100">
        <div class="card-body">
          <h6 class="text-muted">Clients</h6>
          <h2 class="mb-0">{{ number_format($stats['total_clients']) }}</h2>
          <span class="text-muted small">{{ number_format($stats['new_clients_30_days']) }} joined in 30 days</span>
        </div>
      </div>
    </div>
    <div class="col-md-3 mb-3">
      <div class="card shadow-sm h-100">
        <div class="card-body">
          <h6 class="text-muted">Billing Alerts</h6>
          <h2 class="mb-0">{{ number_format($stats['pending_invoices']) }}</h2>
          <span class="text-danger small">Overdue {{ number_format($stats['overdue_amount'], 2) }} {{ $currency }}</span>
          <span class="text-muted small d-block mt-2">Open tickets {{ number_format($stats['open_tickets']) }}</span>
        </div>
      </div>
    </div>
  </div>

  <div class="card mb-4 shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center">
      <span>Revenue (last 6 months)</span>
      <span class="badge badge-light">Manual payments pending: {{ number_format($stats['pending_manual_payments']) }}</span>
    </div>
    <div class="card-body">
      <canvas id="revenueChart" height="120"></canvas>
    </div>
  </div>

  <div class="row">
    <div class="col-lg-6 mb-4">
      <div class="card shadow-sm h-100">
        <div class="card-header">Upcoming Renewals</div>
        <div class="card-body p-0">
          @if($upcomingRenewals->isEmpty())
            <p class="p-3 text-muted mb-0">No active services approaching renewal.</p>
          @else
            <div class="table-responsive">
              <table class="table table-striped mb-0">
                <thead>
                  <tr>
                    <th>Service</th>
                    <th>Client</th>
                    <th>Due Date</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($upcomingRenewals as $service)
                    <tr>
                      <td>{{ optional($service->product)->name ?? 'Service #'.$service->id }}</td>
                      <td>{{ optional(optional($service->client)->user)->name ?? 'Unknown' }}</td>
                      <td>{{ optional($service->next_due_date)->format('Y-m-d') ?? '—' }}</td>
                      <td class="text-right"><span class="badge badge-secondary">{{ ucfirst($service->billing_cycle) }}</span></td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          @endif
        </div>
      </div>
    </div>
    <div class="col-lg-6 mb-4">
      <div class="card shadow-sm h-100">
        <div class="card-header">Recent Invoices</div>
        <div class="card-body p-0">
          @if($recentInvoices->isEmpty())
            <p class="p-3 text-muted mb-0">Invoices will appear here after customers order services.</p>
          @else
            <div class="table-responsive">
              <table class="table table-striped mb-0">
                <thead>
                  <tr>
                    <th>Invoice</th>
                    <th>Client</th>
                    <th>Status</th>
                    <th>Total</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($recentInvoices as $invoice)
                    <tr>
                      <td><a href="{{ url('/invoices/'.$invoice->id) }}">{{ $invoice->number }}</a></td>
                      <td>{{ optional(optional($invoice->client)->user)->name ?? 'Unknown' }}</td>
                      <td><span class="badge badge-{{ $invoice->status === 'paid' ? 'success' : ($invoice->status === 'overdue' ? 'danger' : 'warning') }}">{{ ucfirst($invoice->status) }}</span></td>
                      <td>{{ number_format($invoice->total, 2) }} {{ $invoice->currency }}</td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          @endif
        </div>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-lg-6 mb-4">
      <div class="card shadow-sm h-100">
        <div class="card-header">Recent Support Tickets</div>
        <div class="card-body p-0">
          @if($recentTickets->isEmpty())
            <p class="p-3 text-muted mb-0">No support tickets have been submitted yet.</p>
          @else
            <div class="table-responsive">
              <table class="table table-striped mb-0">
                <thead>
                  <tr>
                    <th>Reference</th>
                    <th>Client</th>
                    <th>Status</th>
                    <th class="text-right">Updated</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($recentTickets as $ticket)
                    <tr>
                      <td><a href="{{ route('admin.tickets.show', $ticket) }}">{{ $ticket->reference }}</a></td>
                      <td>{{ optional(optional($ticket->client)->user)->name ?? 'Unknown' }}</td>
                      <td><span class="badge badge-{{ $ticket->status==='closed'?'secondary':($ticket->status==='answered'?'info':'warning') }}">{{ ucfirst($ticket->status) }}</span></td>
                      <td class="text-right">{{ optional($ticket->updated_at)->diffForHumans() }}</td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          @endif
        </div>
      </div>
    </div>
    <div class="col-lg-6 mb-4">
      <div class="card shadow-sm h-100">
        <div class="card-header">Security Activity (Last 10)</div>
        <div class="card-body p-0">
          @if($loginActivities->isEmpty())
            <p class="p-3 text-muted mb-0">Login history will appear after users authenticate.</p>
          @else
            <div class="table-responsive">
              <table class="table table-striped mb-0">
                <thead>
                  <tr>
                    <th>User</th>
                    <th>Event</th>
                    <th>IP</th>
                    <th class="text-right">Timestamp</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($loginActivities as $activity)
                    <tr>
                      <td>{{ optional($activity->user)->name ?? $activity->identifier ?? 'Unknown' }}</td>
                      <td><span class="badge badge-{{ $activity->event === 'failed' ? 'danger' : ($activity->event === 'login' ? 'success' : 'secondary') }}">{{ ucfirst($activity->event) }}</span></td>
                      <td>{{ $activity->ip_address ?? '—' }}</td>
                      <td class="text-right">{{ optional($activity->created_at)->format('Y-m-d H:i') }}</td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          @endif
        </div>
      </div>
    </div>
  </div>

  <div class="card shadow-sm mb-4">
    <div class="card-header">Recent Payments</div>
    <div class="card-body p-0">
      @if($recentPayments->isEmpty())
        <p class="p-3 text-muted mb-0">Payment history will populate once customers are billed.</p>
      @else
        <div class="table-responsive">
          <table class="table table-striped mb-0">
            <thead>
              <tr>
                <th>Gateway</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Invoice</th>
                <th>Paid At</th>
              </tr>
            </thead>
            <tbody>
              @foreach($recentPayments as $payment)
                <tr>
                  <td class="text-uppercase">{{ $payment->gateway }}</td>
                  <td>{{ number_format($payment->amount, 2) }} {{ $payment->currency }}</td>
                  <td><span class="badge badge-{{ $payment->status === 'completed' ? 'success' : 'secondary' }}">{{ ucfirst($payment->status) }}</span></td>
                  <td>
                    @if($payment->invoice)
                      <a href="{{ url('/invoices/'.$payment->invoice_id) }}">{{ optional($payment->invoice)->number }}</a>
                    @else
                      —
                    @endif
                  </td>
                  <td>{{ optional($payment->paid_at)->format('Y-m-d H:i') ?? '—' }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      @endif
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.6/dist/chart.umd.min.js" integrity="sha384-d3/6th10S9rNUz3omWeNdMf1sBhY9qsK0kGugHgdGXN53BJ38qRAjPR9U1FVLtZL" crossorigin="anonymous"></script>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    var ctx = document.getElementById('revenueChart');
    if (!ctx) { return; }
    var chart = new Chart(ctx.getContext('2d'), {
      type: 'line',
      data: {
        labels: @json($revenueLabels),
        datasets: [{
          label: 'Revenue',
          data: @json($revenueValues),
          borderColor: '#007bff',
          backgroundColor: 'rgba(0, 123, 255, 0.1)',
          tension: 0.3,
          fill: true,
          borderWidth: 2,
          pointRadius: 3,
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          y: { beginAtZero: true }
        }
      }
    });
  });
</script>
@endpush
