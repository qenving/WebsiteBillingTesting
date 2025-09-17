@extends('layouts.app')

@section('content')
<div class="container" style="max-width: 980px;">
  <div class="row">
    <div class="col-lg-4 mb-4">
      <div class="card shadow-sm h-100">
        <div class="card-header bg-primary text-white">Environment Check</div>
        <div class="card-body">
          <p class="text-muted">Ensure the server meets the minimum requirements before continuing.</p>
          <ul class="list-unstyled mb-0">
            @foreach($requirements as $label => $status)
              <li class="d-flex align-items-center mb-2">
                <span class="badge badge-{{ $status ? 'success' : 'danger' }} mr-2">{{ $status ? 'OK' : 'Missing' }}</span>
                <span>{{ $label }}</span>
              </li>
            @endforeach
          </ul>
          <hr>
          <p class="mb-1"><strong>Database connection</strong></p>
          @if($databaseReady)
            <span class="badge badge-success">Connected</span>
            <small class="d-block text-muted">Migrations will run automatically.</small>
          @else
            <span class="badge badge-danger">Not connected</span>
            <small class="d-block text-muted">Update your .env database credentials, then refresh.</small>
          @endif
        </div>
      </div>
    </div>
    <div class="col-lg-8">
      <div class="card shadow-sm">
        <div class="card-header">Initial Configuration</div>
        <div class="card-body">
          <p class="text-muted">Set the brand details and administrator account for your billing portal.</p>
          @if($errors->any())
            <div class="alert alert-danger">
              <strong>We found some issues:</strong>
              <ul class="mb-0">
                @foreach($errors->all() as $error)
                  <li>{{ $error }}</li>
                @endforeach
              </ul>
            </div>
          @endif
          <form method="POST" action="{{ route('install.store') }}">
            @csrf
            <div class="form-row">
              <div class="form-group col-md-6">
                <label for="app_name">Portal Name</label>
                <input type="text" class="form-control" id="app_name" name="app_name" value="{{ old('app_name', $defaultAppName) }}" required>
              </div>
              <div class="form-group col-md-6">
                <label for="app_url">Portal URL</label>
                <input type="url" class="form-control" id="app_url" name="app_url" value="{{ old('app_url', $defaultUrl) }}" required>
              </div>
            </div>

            <hr>
            <h5 class="mt-3">Administrator Account</h5>
            <div class="form-row">
              <div class="form-group col-md-6">
                <label for="admin_name">Full Name</label>
                <input type="text" class="form-control" id="admin_name" name="admin_name" value="{{ old('admin_name') }}" required>
              </div>
              <div class="form-group col-md-6">
                <label for="admin_email">Email</label>
                <input type="email" class="form-control" id="admin_email" name="admin_email" value="{{ old('admin_email') }}" required>
              </div>
            </div>
            <div class="form-row">
              <div class="form-group col-md-6">
                <label for="admin_password">Password</label>
                <input type="password" class="form-control" id="admin_password" name="admin_password" required>
              </div>
              <div class="form-group col-md-6">
                <label for="admin_password_confirmation">Confirm Password</label>
                <input type="password" class="form-control" id="admin_password_confirmation" name="admin_password_confirmation" required>
              </div>
            </div>

            <hr>
            <h5 class="mt-3">Company Details</h5>
            <div class="form-row">
              <div class="form-group col-md-6">
                <label for="company_name">Company Name</label>
                <input type="text" class="form-control" id="company_name" name="company_name" value="{{ old('company_name') }}" required>
              </div>
              <div class="form-group col-md-6">
                <label for="company_email">Billing Email</label>
                <input type="email" class="form-control" id="company_email" name="company_email" value="{{ old('company_email') }}" required>
              </div>
            </div>
            <div class="form-row">
              <div class="form-group col-md-6">
                <label for="company_phone">Phone</label>
                <input type="text" class="form-control" id="company_phone" name="company_phone" value="{{ old('company_phone') }}">
              </div>
              <div class="form-group col-md-6">
                <label for="company_tax_id">Tax ID</label>
                <input type="text" class="form-control" id="company_tax_id" name="company_tax_id" value="{{ old('company_tax_id') }}">
              </div>
            </div>
            <div class="form-group">
              <label for="company_address">Address</label>
              <textarea class="form-control" id="company_address" name="company_address" rows="3">{{ old('company_address') }}</textarea>
            </div>
            <div class="form-row">
              <div class="form-group col-md-4">
                <label for="currency">Billing Currency</label>
                <input type="text" class="form-control text-uppercase" id="currency" name="currency" maxlength="3" value="{{ old('currency', 'IDR') }}" required>
                <small class="text-muted">Use ISO currency code (e.g. IDR, USD).</small>
              </div>
              <div class="form-group col-md-4">
                <label for="tax_rate">Default Tax Rate (%)</label>
                <input type="number" step="0.01" min="0" class="form-control" id="tax_rate" name="tax_rate" value="{{ old('tax_rate', 0) }}">
              </div>
            </div>

            <div class="form-group">
              <label for="manual_instructions">Manual Payment Instructions</label>
              <textarea class="form-control" id="manual_instructions" name="manual_instructions" rows="4" placeholder="Describe bank transfer instructions">{{ old('manual_instructions') }}</textarea>
              <small class="text-muted">Displayed when customers select manual/transfer payment.</small>
            </div>

            <div class="d-flex justify-content-between align-items-center">
              <div class="text-muted">By continuing, migrations will run and the administrator will be created.</div>
              <button type="submit" class="btn btn-primary btn-lg">Complete Installation</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
