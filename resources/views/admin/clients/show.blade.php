@extends('layouts.app')
@section('content')
<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h3 class="mb-0">{{ optional($client->user)->name }}</h3>
      <small class="text-muted">{{ optional($client->user)->email }}</small>
    </div>
    <a href="{{ route('admin.clients.index') }}" class="btn btn-link">Back to clients</a>
  </div>
  @if(session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
  @endif
  <div class="row">
    <div class="col-lg-4 mb-4">
      <div class="card shadow-sm mb-4">
        <div class="card-header">Profile</div>
        <div class="card-body">
          <p class="mb-1"><strong>Company:</strong> {{ $client->company ?? '—' }}</p>
          <p class="mb-1"><strong>Phone:</strong> {{ $client->phone ?? '—' }}</p>
          <p class="mb-1"><strong>Address:</strong><br>{{ $client->address_line1 ?? '' }} {{ $client->city }} {{ $client->country }}</p>
          <p class="mb-0 text-muted">Registered {{ optional($client->created_at)->format('Y-m-d H:i') }}</p>
        </div>
      </div>
      <div class="card shadow-sm">
        <div class="card-header">Account Credit</div>
        <div class="card-body">
          <h4>{{ number_format($client->credit_balance, 2) }} {{ $client->currency }}</h4>
          <form method="POST" action="{{ route('admin.clients.credit', $client) }}" class="mt-3">
            @csrf
            <div class="form-group">
              <label>Type</label>
              <select name="type" class="form-control" required>
                <option value="credit">Add Credit</option>
                <option value="debit">Deduct Credit</option>
              </select>
            </div>
            <div class="form-group">
              <label>Amount</label>
              <input type="number" name="amount" step="0.01" min="0.01" class="form-control @error('amount') is-invalid @enderror" required>
              @error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
              <label>Description</label>
              <input type="text" name="description" class="form-control @error('description') is-invalid @enderror" placeholder="Optional note">
              @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <button class="btn btn-primary btn-block">Update Credit</button>
          </form>
        </div>
      </div>
    </div>
    <div class="col-lg-8 mb-4">
      <div class="card shadow-sm mb-4">
        <div class="card-header">Active Services</div>
        <div class="card-body p-0">
          @if($services->isEmpty())
            <p class="p-3 text-muted mb-0">Client has no services yet.</p>
          @else
            <div class="table-responsive">
              <table class="table table-striped mb-0">
                <thead>
                  <tr>
                    <th>Service</th>
                    <th>Status</th>
                    <th>Cycle</th>
                    <th>Next Due</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($services as $service)
                    <tr>
                      <td>{{ optional($service->product)->name ?? 'Service #'.$service->id }}</td>
                      <td><span class="badge badge-{{ $service->status === 'active' ? 'success' : ($service->status === 'suspended' ? 'danger' : 'secondary') }}">{{ ucfirst($service->status) }}</span></td>
                      <td>{{ ucfirst($service->billing_cycle) }}</td>
                      <td>{{ optional($service->next_due_date)->format('Y-m-d') ?? '—' }}</td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          @endif
        </div>
      </div>
      <div class="card shadow-sm mb-4">
        <div class="card-header">Recent Invoices</div>
        <div class="card-body p-0">
          @if($invoices->isEmpty())
            <p class="p-3 text-muted mb-0">No invoices yet.</p>
          @else
            <div class="table-responsive">
              <table class="table table-striped mb-0">
                <thead>
                  <tr>
                    <th>Invoice</th>
                    <th>Status</th>
                    <th>Total</th>
                    <th>Due Date</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($invoices as $invoice)
                    <tr>
                      <td><a href="{{ url('/invoices/'.$invoice->id) }}">{{ $invoice->number }}</a></td>
                      <td><span class="badge badge-{{ $invoice->status === 'paid' ? 'success' : ($invoice->status === 'overdue' ? 'danger' : 'warning') }}">{{ ucfirst($invoice->status) }}</span></td>
                      <td>{{ number_format($invoice->total, 2) }} {{ $invoice->currency }}</td>
                      <td>{{ optional($invoice->due_date)->format('Y-m-d') ?? '—' }}</td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          @endif
        </div>
      </div>
      <div class="card shadow-sm">
        <div class="card-header">Credit Ledger</div>
        <div class="card-body p-0">
          @if($creditTransactions->isEmpty())
            <p class="p-3 text-muted mb-0">No credit adjustments recorded.</p>
          @else
            <div class="table-responsive">
              <table class="table table-striped mb-0">
                <thead>
                  <tr>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Amount</th>
                    <th>Balance</th>
                    <th>Description</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($creditTransactions as $txn)
                    <tr>
                      <td>{{ optional($txn->created_at)->format('Y-m-d H:i') }}</td>
                      <td>{{ ucfirst($txn->type) }}</td>
                      <td>{{ number_format($txn->amount, 2) }} {{ $client->currency }}</td>
                      <td>{{ number_format($txn->balance_after, 2) }} {{ $client->currency }}</td>
                      <td>{{ $txn->description ?? '—' }}</td>
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
</div>
@endsection
