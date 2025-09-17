@extends('layouts.app')
@section('content')
<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">All Support Tickets</h3>
    <div>
      <form method="GET" class="form-inline">
        <label class="mr-2 mb-0">Status</label>
        <select name="status" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
          <option value="">All</option>
          @foreach(['open'=>'Open','answered'=>'Answered','closed'=>'Closed'] as $value => $label)
            <option value="{{ $value }}" {{ request('status')===$value?'selected':'' }}>{{ $label }}</option>
          @endforeach
        </select>
        <noscript><button class="btn btn-sm btn-primary">Filter</button></noscript>
      </form>
    </div>
  </div>
  <div class="card">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-striped table-hover mb-0">
          <thead>
            <tr>
              <th>Reference</th>
              <th>Subject</th>
              <th>Client</th>
              <th>Status</th>
              <th>Priority</th>
              <th class="text-right">Updated</th>
            </tr>
          </thead>
          <tbody>
            @forelse($tickets as $ticket)
              <tr onclick="window.location='{{ route('admin.tickets.show', $ticket) }}'" style="cursor:pointer;">
                <td>{{ $ticket->reference }}</td>
                <td>{{ $ticket->subject }}</td>
                <td>{{ optional($ticket->client->user)->name ?? 'Unknown' }}</td>
                <td><span class="badge badge-{{ $ticket->status==='closed'?'secondary':($ticket->status==='answered'?'info':'warning') }}">{{ ucfirst($ticket->status) }}</span></td>
                <td>{{ ucfirst($ticket->priority) }}</td>
                <td class="text-right">{{ optional($ticket->updated_at)->diffForHumans() }}</td>
              </tr>
            @empty
              <tr><td colspan="6" class="text-center p-4 text-muted">No tickets found for this filter.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
    <div class="card-footer">{{ $tickets->links() }}</div>
  </div>
</div>
@endsection
