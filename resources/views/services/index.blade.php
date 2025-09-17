@extends('layouts.app')
@section('content')
<div class="container">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h3 class="mb-0">My Services</h3>
    <a href="{{ url('/shop') }}" class="btn btn-sm btn-outline-primary">Order More</a>
  </div>
  @if(session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
  @endif
  @if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
  @endif
  @if($services->isEmpty())
    <div class="alert alert-info">No active services yet.</div>
  @else
    <div class="table-responsive">
      <table class="table table-hover">
        <thead><tr><th>ID</th><th>Product</th><th>Status</th><th>Next Due</th><th>Access</th><th class="text-right">Actions</th></tr></thead>
        <tbody>
          @foreach($services as $s)
            @php
              $badge = [
                'active' => 'success',
                'pending' => 'warning',
                'suspended' => 'danger',
                'terminated' => 'secondary',
              ][$s->status] ?? 'secondary';
              $meta = $s->meta ?? [];
              $canOperate = $s->status === 'active';
              $disabledAttr = $canOperate ? '' : 'disabled';
            @endphp
            <tr>
              <td>#{{ $s->id }}</td>
              <td>{{ optional($s->product)->name }}</td>
              <td><span class="badge badge-{{ $badge }}">{{ ucfirst($s->status) }}</span><br><small class="text-muted">Created {{ optional($s->created_at)->format('Y-m-d') }}</small></td>
              <td>{{ optional($s->next_due_date)->format('Y-m-d') }}</td>
              <td>
                <div><strong>IP:</strong> {{ $meta['ip'] ?? 'n/a' }}</div>
                <div><strong>Password:</strong> {{ $meta['password'] ?? 'n/a' }}</div>
              </td>
              <td class="text-right" style="min-width:220px;">
                <form method="POST" action="{{ url('/services/'.$s->id.'/actions/reboot') }}" class="d-inline">
                  @csrf
                  <button class="btn btn-outline-secondary btn-sm mb-1" {{ $disabledAttr }}>Reboot</button>
                </form>
                <form method="POST" action="{{ url('/services/'.$s->id.'/actions/power-off') }}" class="d-inline">
                  @csrf
                  <button class="btn btn-outline-secondary btn-sm mb-1" {{ $disabledAttr }}>Power Off</button>
                </form>
                <form method="POST" action="{{ url('/services/'.$s->id.'/actions/power-on') }}" class="d-inline">
                  @csrf
                  <button class="btn btn-outline-secondary btn-sm mb-1" {{ $disabledAttr }}>Power On</button>
                </form>
                <form method="POST" action="{{ url('/services/'.$s->id.'/actions/reset-password') }}" class="d-inline">
                  @csrf
                  <button class="btn btn-outline-primary btn-sm mb-1" {{ $disabledAttr }}>Reset Password</button>
                </form>
                <a href="{{ url('/services/'.$s->id.'/actions/console') }}"
                   class="btn btn-outline-info btn-sm mb-1 {{ $canOperate ? '' : 'disabled' }}"
                   target="_blank"
                   @if(! $canOperate) tabindex="-1" aria-disabled="true" onclick="return false;" @endif>Open Console</a>
              </td>
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

