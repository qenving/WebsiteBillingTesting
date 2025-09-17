@extends('layouts.app')
@section('content')
<div class="container" style="max-width: 960px;">
  <h3 class="mb-3">Pending Manual Payments</h3>
  @if(session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
  @endif
  @if($payments->isEmpty())
    <div class="alert alert-info">No pending manual payments.</div>
  @else
    <div class="card">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-striped mb-0">
            <thead>
              <tr>
                <th>Invoice</th>
                <th>Client</th>
                <th>Amount</th>
                <th>Created</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              @foreach($payments as $payment)
                <tr>
                  <td><a href="{{ url('/invoices/'.$payment->invoice_id) }}">{{ optional($payment->invoice)->number ?? '—' }}</a></td>
                  <td>{{ optional(optional(optional($payment->invoice)->client)->user)->name ?? 'Unknown' }}</td>
                  <td>{{ number_format($payment->amount, 2) }} {{ $payment->currency }}</td>
                  <td>{{ optional($payment->created_at)->format('Y-m-d H:i') }}</td>
                  <td class="text-right">
                    <form method="POST" action="{{ route('admin.payments.confirm', $payment) }}" onsubmit="return confirm('Mark this payment as received?');">
                      @csrf
                      <button class="btn btn-sm btn-success">Confirm</button>
                    </form>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
      <div class="card-footer">{{ $payments->links() }}</div>
    </div>
  @endif
</div>
@endsection
