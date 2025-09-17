@extends('layouts.app')
@section('content')
<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Clients</h3>
    <form method="GET" class="form-inline">
      <input type="text" class="form-control form-control-sm mr-2" name="q" value="{{ $search }}" placeholder="Search name, email or company">
      <button class="btn btn-sm btn-primary" type="submit">Search</button>
    </form>
  </div>
  <div class="card">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead>
            <tr>
              <th>Client</th>
              <th>Company</th>
              <th>Credit</th>
              <th>Services</th>
              <th class="text-right">Joined</th>
            </tr>
          </thead>
          <tbody>
            @forelse($clients as $client)
              <tr onclick="window.location='{{ route('admin.clients.show', $client) }}'" style="cursor:pointer;">
                <td>{{ optional($client->user)->name }}<br><small class="text-muted">{{ optional($client->user)->email }}</small></td>
                <td>{{ $client->company ?? '—' }}</td>
                <td>{{ number_format($client->credit_balance, 2) }} {{ $client->currency }}</td>
                <td>{{ number_format($client->services_count) }}</td>
                <td class="text-right">{{ optional($client->created_at)->format('Y-m-d') }}</td>
              </tr>
            @empty
              <tr><td colspan="5" class="text-center p-4 text-muted">No clients found.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
    <div class="card-footer">{{ $clients->links() }}</div>
  </div>
</div>
@endsection
