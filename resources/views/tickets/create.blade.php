@extends('layouts.app')
@section('content')
<div class="container" style="max-width: 720px;">
  <h3 class="mb-3">Create Support Ticket</h3>
  <div class="card">
    <div class="card-body">
      <form method="POST" action="{{ route('tickets.store') }}">
        @csrf
        <div class="form-group">
          <label>Subject</label>
          <input type="text" class="form-control @error('subject') is-invalid @enderror" name="subject" value="{{ old('subject') }}" required maxlength="150">
          @error('subject')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="form-row">
          <div class="form-group col-md-6">
            <label>Department</label>
            <input type="text" class="form-control @error('department') is-invalid @enderror" name="department" value="{{ old('department') }}" placeholder="Billing, Support, Sales...">
            @error('department')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="form-group col-md-6">
            <label>Priority</label>
            <select class="form-control @error('priority') is-invalid @enderror" name="priority" required>
              @foreach(['low'=>'Low','normal'=>'Normal','high'=>'High'] as $value => $label)
                <option value="{{ $value }}" {{ old('priority','normal')===$value?'selected':'' }}>{{ $label }}</option>
              @endforeach
            </select>
            @error('priority')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
        </div>
        <div class="form-group">
          <label>Message</label>
          <textarea class="form-control @error('message') is-invalid @enderror" rows="8" name="message" required>{{ old('message') }}</textarea>
          @error('message')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="text-right">
          <button class="btn btn-primary">Submit Ticket</button>
          <a href="{{ route('tickets.index') }}" class="btn btn-link">Cancel</a>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
