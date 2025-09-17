@extends('layouts.app')
@section('content')
<div class="container" style="max-width: 860px;">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h3 class="mb-0">Ticket {{ $ticket->reference }}</h3>
      <small class="text-muted">Subject: {{ $ticket->subject }}</small>
    </div>
    <div>
      <a href="{{ route('tickets.index') }}" class="btn btn-link">Back</a>
      @if($ticket->status !== 'closed')
        <form method="POST" action="{{ route('tickets.close', $ticket) }}" class="d-inline">
          @csrf
          <button class="btn btn-outline-secondary btn-sm" type="submit">Close Ticket</button>
        </form>
      @endif
    </div>
  </div>
  @if(session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
  @endif
  <div class="card mb-3">
    <div class="card-body">
      <div class="d-flex justify-content-between">
        <div>
          <div><strong>Status:</strong> <span class="badge badge-{{ $ticket->status==='closed'?'secondary':($ticket->status==='answered'?'info':'warning') }}">{{ ucfirst($ticket->status) }}</span></div>
          <div><strong>Priority:</strong> {{ ucfirst($ticket->priority) }}</div>
          @if($ticket->department)
          <div><strong>Department:</strong> {{ $ticket->department }}</div>
          @endif
        </div>
        <div class="text-right text-muted">
          <div>Opened {{ optional($ticket->created_at)->format('Y-m-d H:i') }}</div>
          <div>Updated {{ optional($ticket->updated_at)->diffForHumans() }}</div>
        </div>
      </div>
    </div>
  </div>

  <div class="timeline">
    @foreach($ticket->replies as $reply)
      <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
          <div>
            <strong>{{ $reply->user && $reply->user->id === auth()->id() ? 'You' : optional($reply->user)->name ?? 'Support' }}</strong>
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

  @if($ticket->status !== 'closed')
    <div class="card">
      <div class="card-header">Reply to Ticket</div>
      <div class="card-body">
        <form method="POST" action="{{ route('tickets.reply', $ticket) }}">
          @csrf
          <div class="form-group">
            <textarea name="message" rows="6" class="form-control @error('message') is-invalid @enderror" placeholder="Write your response..." required>{{ old('message') }}</textarea>
            @error('message')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="text-right">
            <button class="btn btn-primary">Send Reply</button>
          </div>
        </form>
      </div>
    </div>
  @else
    <div class="alert alert-secondary">This ticket is closed. Re-open it by contacting support if needed.</div>
  @endif
</div>
@endsection
