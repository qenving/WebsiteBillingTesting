@extends('layouts.app')
@section('content')
<div class="container">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h3 class="mb-0">My Services</h3>
    <a href="{{ url('/shop') }}" class="btn btn-sm btn-outline-primary">Order More</a>
  </div>
  @if($services->isEmpty())
    <div class="alert alert-info">No active services yet.</div>
  @else
    <div class="table-responsive">
      <table class="table table-hover">
        <thead><tr><th>ID</th><th>Product</th><th>Status</th><th>Created</th></tr></thead>
        <tbody>
          @foreach($services as $s)
            <tr>
              <td>#{{ $s->id }}</td>
              <td>{{ optional($s->product)->name }}</td>
              <td><span class="badge badge-{{ $s->status==='active'?'success':($s->status==='pending'?'warning':'secondary') }}">{{ ucfirst($s->status) }}</span></td>
              <td>{{ optional($s->created_at)->format('Y-m-d') }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    @if(method_exists($services, 'links'))
      {{ $services->links() }}
    @endif
  @endif
</div>
@endsection

