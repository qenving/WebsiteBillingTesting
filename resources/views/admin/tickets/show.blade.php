@extends('layouts.app')
@section('content')
<div class="container-fluid" style="max-width: 1100px;">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h3 class="mb-0">Ticket {{ $ticket->reference }}</h3>
      <small class="text-muted">{{ $ticket->subject }} &middot; {{ optional($ticket->client->user)->name }}</small>
    </div>
    <div>
      <a href="{{ route('admin.tickets.index') }}" class="btn btn-link">Back</a>
      <form method="POST" action="{{ route('admin.tickets.status', $ticket) }}" class="d-inline">
        @csrf
        <input type="hidden" name="status" value="{{ $ticket->status === 'closed' ? 'open' : 'closed' }}">
        <button class="btn btn-sm btn-outline-secondary" type="submit">{{ $ticket->status === 'closed' ? 'Reopen' : 'Close' }} Ticket</button>
      </form>
    </div>
  </div>
  @if(session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
  @endif
  <div class="row">
    <div class="col-md-8">
      @foreach($ticket->replies as $reply)
        <div class="card mb-3">
          <div class="card-header d-flex justify-content-between align-items-center">
            <div>
              <strong>{{ optional($reply->user)->name ?? 'System' }}</strong>
              <small class="text-muted ml-2">{{ optional($reply->created_at)->format('Y-m-d H:i') }}</small>
              @if($reply->is_internal)
                <span class="badge badge-secondary ml-2">Internal</span>
              @endif
            </div>
          </div>
          <div class="card-body">
            <pre class="mb-0" style="white-space: pre-wrap;">{{ $reply->message }}</pre>
          </div>
        </div>
      @endforeach
    </div>
    <div class="col-md-4">
      <div class="card mb-3">
        <div class="card-body">
          <div class="mb-2"><strong>Status:</strong> <span class="badge badge-{{ $ticket->status==='closed'?'secondary':($ticket->status==='answered'?'info':'warning') }}">{{ ucfirst($ticket->status) }}</span></div>
          <div class="mb-2"><strong>Priority:</strong> {{ ucfirst($ticket->priority) }}</div>
          @if($ticket->department)
            <div class="mb-2"><strong>Department:</strong> {{ $ticket->department }}</div>
          @endif
          <div class="mb-2 text-muted">Opened {{ optional($ticket->created_at)->format('Y-m-d H:i') }}</div>
          <div class="mb-0 text-muted">Updated {{ optional($ticket->updated_at)->diffForHumans() }}</div>
        </div>
      </div>
      <div class="card">
        <div class="card-header">Reply / Add Note</div>
        <div class="card-body">
          <form method="POST" action="{{ route('admin.tickets.reply', $ticket) }}">
            @csrf
            <div class="form-group">
              <label>Message</label>
              <textarea name="message" rows="6" class="form-control @error('message') is-invalid @enderror" required>{{ old('message') }}</textarea>
              @error('message')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="form-group form-check">
              <input type="checkbox" class="form-check-input" id="is_internal" name="is_internal" value="1" {{ old('is_internal') ? 'checked' : '' }}>
              <label class="form-check-label" for="is_internal">Internal note (hidden from client)</label>
            </div>
            <button class="btn btn-primary btn-block">Submit</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
