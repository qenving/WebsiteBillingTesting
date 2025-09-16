@extends('layouts.app')
@section('content')
<div class="container" style="max-width: 880px;">
  <h3 class="mb-3">Admin Settings</h3>
  @if(session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
  @endif
  <form method="POST" action="{{ url('/admin/settings') }}">
    @csrf
    <div class="card mb-3">
      <div class="card-header">Branding</div>
      <div class="card-body">
        <div class="form-group">
          <label>Site/App Name</label>
          <input type="text" class="form-control" name="branding_name" value="{{ old('branding_name', $data['branding_name']) }}" required />
        </div>
        <div class="form-group">
          <label>Logo URL</label>
          <input type="url" class="form-control" name="company_logo_url" value="{{ old('company_logo_url', $data['company_logo_url']) }}" placeholder="https://example.com/logo.png" />
          <small class="text-muted">Opsional. Ditampilkan di invoice PDF.</small>
        </div>
      </div>
    </div>
    <div class="card mb-3">
      <div class="card-header">Finance & Invoicing</div>
      <div class="card-body">
        <div class="form-row">
          <div class="form-group col-md-3">
            <label>Tax Rate (%)</label>
            <input type="number" step="0.01" min="0" class="form-control" name="finance_tax_rate" value="{{ old('finance_tax_rate', (float)\App\Support\Settings::get('finance.tax_rate', 0)) }}" />
          </div>
          <div class="form-group col-md-3">
            <label>Number Prefix</label>
            <input type="text" class="form-control" name="invoice_number_prefix" value="{{ old('invoice_number_prefix', (string)\App\Support\Settings::get('invoice.number_prefix','INV')) }}" />
          </div>
          <div class="form-group col-md-3">
            <label>Date Format</label>
            <input type="text" class="form-control" name="invoice_number_date_format" value="{{ old('invoice_number_date_format', (string)\App\Support\Settings::get('invoice.number_date_format','Ymd')) }}" />
            <small class="text-muted">PHP date() format, contoh: Ymd, Ym, Y</small>
          </div>
          <div class="form-group col-md-3">
            <label>Sequence Scope</label>
            <select class="form-control" name="invoice_sequence_scope">
              @foreach(['daily','monthly','yearly','global'] as $opt)
                <option value="{{ $opt }}" {{ (\App\Support\Settings::get('invoice.sequence_scope','daily')===$opt)?'selected':'' }}>{{ ucfirst($opt) }}</option>
              @endforeach
            </select>
          </div>
        </div>
        <div class="form-group">
          <label>Invoice Footer / Terms</label>
          <textarea class="form-control" rows="4" name="invoice_footer">{{ old('invoice_footer', (string)\App\Support\Settings::get('invoice.footer','')) }}</textarea>
        </div>
      </div>
    </div>
    <div class="card mb-3">
      <div class="card-header">Company Profile</div>
      <div class="card-body">
        <div class="form-row">
          <div class="form-group col-md-6">
            <label>Company Name</label>
            <input type="text" class="form-control" name="company_name" value="{{ old('company_name', $data['company_name']) }}" />
          </div>
          <div class="form-group col-md-6">
            <label>Tax ID</label>
            <input type="text" class="form-control" name="company_tax_id" value="{{ old('company_tax_id', $data['company_tax_id']) }}" />
          </div>
        </div>
        <div class="form-group">
          <label>Address</label>
          <textarea class="form-control" rows="3" name="company_address">{{ old('company_address', $data['company_address']) }}</textarea>
        </div>
        <div class="form-row">
          <div class="form-group col-md-6">
            <label>Email</label>
            <input type="email" class="form-control" name="company_email" value="{{ old('company_email', $data['company_email']) }}" />
          </div>
          <div class="form-group col-md-6">
            <label>Phone</label>
            <input type="text" class="form-control" name="company_phone" value="{{ old('company_phone', $data['company_phone']) }}" />
          </div>
        </div>
      </div>
    </div>
    <div class="card mb-3">
      <div class="card-header">Manual Payment Instructions</div>
      <div class="card-body">
        <p class="text-muted">Akan ditampilkan kepada pelanggan ketika memilih gateway <strong>Bank Transfer (Manual)</strong>.</p>
        <textarea class="form-control" rows="8" name="manual_instructions" placeholder="Tulis instruksi transfer">{{ old('manual_instructions', $data['manual_instructions']) }}</textarea>
      </div>
    </div>
    <div class="text-right">
      <button class="btn btn-primary">Save Settings</button>
    </div>
  </form>
</div>
@endsection
