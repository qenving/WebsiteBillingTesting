@extends('layouts.app')
@section('content')
<div class="container" style="max-width: 960px;">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Support Tickets</h3>
    <a href="{{ route('tickets.create') }}" class="btn btn-primary">New Ticket</a>
  </div>
  <div class="card">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead>
            <tr>
              <th>Reference</th>
              <th>Subject</th>
              <th>Status</th>
              <th>Priority</th>
              <th class="text-right">Last Update</th>
            </tr>
          </thead>
          <tbody>
            @forelse($tickets as $ticket)
              <tr onclick="window.location='{{ route('tickets.show', $ticket) }}'" style="cursor:pointer;">
                <td>{{ $ticket->reference }}</td>
                <td>{{ $ticket->subject }}</td>
                <td>
                  @php
                    $statusColor = [
                      'open' => 'warning',
                      'answered' => 'info',
                      'closed' => 'secondary',
                    ][$ticket->status] ?? 'secondary';
                  @endphp
                  <span class="badge badge-{{ $statusColor }}">{{ ucfirst($ticket->status) }}</span>
                </td>
                <td>{{ ucfirst($ticket->priority) }}</td>
                <td class="text-right">{{ optional($ticket->updated_at)->diffForHumans() }}</td>
              </tr>
            @empty
              <tr>
                <td colspan="5" class="text-center p-4 text-muted">You do not have any support tickets yet.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
    @if($tickets instanceof \Illuminate\Contracts\Pagination\Paginator)
      <div class="card-footer">{{ $tickets->links() }}</div>
    @endif
  </div>
</div>
@endsection
