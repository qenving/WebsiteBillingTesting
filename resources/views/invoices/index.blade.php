@extends('layouts.app')
@section('content')
<div class="container">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h3 class="mb-0">My Invoices</h3>
    <a href="{{ url('/shop') }}" class="btn btn-sm btn-outline-primary">Order More</a>
  </div>

  @if($invoices->isEmpty())
    <div class="alert alert-info">No invoices yet.</div>
  @else
    <div class="table-responsive">
      <table class="table table-hover">
        <thead>
          <tr>
            <th>#</th><th>Status</th><th>Total</th><th>Due</th><th></th>
          </tr>
        </thead>
        <tbody>
          @foreach($invoices as $inv)
            <tr>
              <td>{{ $inv->number }}</td>
              <td><span class="badge badge-{{ $inv->status==='paid'?'success':($inv->status==='unpaid'?'warning':'secondary') }}">{{ ucfirst($inv->status) }}</span></td>
              <td>{{ number_format($inv->total,0) }} {{ $inv->currency }}</td>
              <td>{{ optional($inv->due_date)->format('Y-m-d') }}</td>
              <td class="text-right"><a href="{{ url('/invoices/'.$inv->id) }}" class="btn btn-sm btn-primary">View</a></td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    @if(method_exists($invoices, 'links'))
      {{ $invoices->links() }}
    @endif
  @endif
</div>
@endsection

