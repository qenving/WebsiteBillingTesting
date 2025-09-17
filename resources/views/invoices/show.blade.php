@extends('layouts.app')
@section('content')
<div class="container">
  @if(session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
  @endif
  @if($errors->any())
    <div class="alert alert-danger">
      <ul class="mb-0">
        @foreach($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Invoice {{ $invoice->number }}</h3>
    <div>
      <a href="{{ url('/invoices') }}" class="btn btn-link">Back</a>
      <a href="{{ url('/invoices/'.$invoice->id.'/pdf') }}" class="btn btn-sm btn-outline-secondary" target="_blank">Print / PDF</a>
    </div>
  </div>

  <div class="row">
    <div class="col-md-8">
      @php
        $balanceDue = $invoice->balanceDue();
        $creditBalance = optional($invoice->client)->credit_balance ?? 0;
      @endphp
      <div class="card mb-3">
        <div class="card-body">
          @php
            $badge = [
              'paid' => 'success',
              'unpaid' => 'warning',
              'overdue' => 'danger',
            ][$invoice->status] ?? 'secondary';
          @endphp
          <div class="mb-2"><strong>Status:</strong> <span class="badge badge-{{ $badge }}">{{ ucfirst($invoice->status) }}</span></div>
          <div class="mb-2"><strong>Total:</strong> {{ number_format($invoice->total,0) }} {{ $invoice->currency }}</div>
          <div class="mb-2"><strong>Balance Due:</strong> {{ number_format($balanceDue,2) }} {{ $invoice->currency }}</div>
          <div class="mb-3"><strong>Due:</strong> {{ optional($invoice->due_date)->format('Y-m-d') }}</div>

          <div class="table-responsive">
            <table class="table table-sm">
              <thead><tr><th>Description</th><th class="text-right">Qty</th><th class="text-right">Unit</th><th class="text-right">Line</th></tr></thead>
              <tbody>
              @foreach($invoice->items as $item)
                <tr>
                  <td>{{ $item->description }}</td>
                  <td class="text-right">{{ $item->quantity }}</td>
                  <td class="text-right">{{ number_format($item->unit_price,0) }}</td>
                  <td class="text-right">{{ number_format($item->total,0) }}</td>
                </tr>
              @endforeach
              </tbody>
            </table>
          </div>
        </div>
      </div>
      @if($invoice->payments->isNotEmpty())
        <div class="card mb-3">
          <div class="card-header">Payment History</div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-sm mb-0">
                <thead>
                  <tr>
                    <th>Date</th>
                    <th>Gateway</th>
                    <th class="text-right">Amount</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                @foreach($invoice->payments->sortBy('created_at') as $payment)
                  <tr>
                    <td>{{ optional($payment->paid_at ?? $payment->created_at)->format('Y-m-d H:i') }}</td>
                    <td>{{ ucfirst($payment->gateway) }}</td>
                    <td class="text-right">{{ number_format($payment->amount,0) }} {{ $invoice->currency }}</td>
                    <td><span class="badge badge-{{ $payment->status === 'completed' ? 'success' : ($payment->status === 'pending' ? 'warning' : 'secondary') }}">{{ ucfirst(str_replace('_',' ',$payment->status)) }}</span></td>
                  </tr>
                @endforeach
                </tbody>
              </table>
            </div>
          </div>
        </div>
      @endif
    </div>
    <div class="col-md-4">
      @if($balanceDue > 0 && $creditBalance > 0)
        <div class="card mb-3">
          <div class="card-header">Use Account Credit</div>
          <div class="card-body">
            <p class="mb-2">Available credit: <strong>{{ number_format($creditBalance,0) }} {{ $invoice->currency }}</strong></p>
            <form method="POST" action="{{ url('/invoices/'.$invoice->id.'/apply-credit') }}">
              @csrf
              <div class="form-group">
                <label>Amount to apply</label>
                <input type="number" name="amount" min="0" step="0.01" max="{{ number_format(min($creditBalance,$balanceDue),2,'.','') }}" value="{{ old('amount') }}" class="form-control" placeholder="{{ number_format(min($creditBalance,$balanceDue),2,'.','') }}">
                <small class="form-text text-muted">Leave empty to apply the maximum available credit.</small>
              </div>
              <button class="btn btn-outline-primary btn-block" type="submit">Apply Credit</button>
            </form>
          </div>
        </div>
      @endif
      @if($invoice->status !== 'paid' && $balanceDue > 0)
        @if(empty($gateways))
          <div class="alert alert-warning">Tidak ada gateway pembayaran yang aktif. Silakan hubungi admin.</div>
        @else
        <div class="card">
          <div class="card-header">Pay Invoice</div>
          <div class="card-body">
            <div class="form-group">
              <label>Gateway</label>
              <select id="gateway" class="form-control">
                @foreach($gateways as $gw)
                  <option value="{{ $gw['key'] }}">{{ $gw['name'] }}</option>
                @endforeach
              </select>
            </div>
            <button id="payBtn" class="btn btn-primary btn-block">Pay Now</button>
            <small class="text-muted d-block mt-2" id="payMsg"></small>
            <div class="mt-2 d-none" id="instrWrap">
              <pre class="mb-2" id="payInstructions" style="white-space:pre-wrap"></pre>
              <button class="btn btn-light btn-sm" id="copyInstrBtn">Copy Instructions</button>
            </div>
          </div>
        </div>
        @endif
      @else
        <div class="alert alert-success">Thank you, this invoice is paid.</div>
      @endif
    </div>
  </div>
</div>

<script>
  (function(){
    var btn = document.getElementById('payBtn'); if(!btn) return;
    var msg = document.getElementById('payMsg');
    var instr = document.getElementById('payInstructions');
    var wrap = document.getElementById('instrWrap');
    var sel = document.getElementById('gateway');
    var token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    btn.addEventListener('click', function(){
      msg.textContent='Creating payment...'; btn.disabled=true; instr.classList.add('d-none'); instr.textContent='';
      fetch("{{ url('/pay') }}/"+sel.value+"/{{ $invoice->id }}",{ method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':token}, body: JSON.stringify({}) })
      .then(function(res){ return res.json().then(function(j){ return {ok:res.ok,status:res.status,data:j}; }); })
      .then(function(r){
        if(!r.ok){ throw new Error(r.data && r.data.message ? r.data.message : 'Failed to create payment'); }
        if(r.data.redirect_url){ window.location = r.data.redirect_url; return; }
        if(r.data.instructions){ instr.textContent = r.data.instructions; wrap.classList.remove('d-none'); msg.textContent='Follow the instructions below.'; btn.disabled=false; return; }
        msg.textContent='Payment created. Follow gateway instructions.'; btn.disabled=false;
      })
      .catch(function(e){ msg.textContent=e.message; btn.disabled=false; });
    });

    var copyBtn = document.getElementById('copyInstrBtn');
    if(copyBtn){
      copyBtn.addEventListener('click', function(){
        var txt = instr.textContent || '';
        if(navigator.clipboard && txt){ navigator.clipboard.writeText(txt).then(function(){ msg.textContent='Instructions copied to clipboard.'; }); }
      });
    }
  })();
</script>
@endsection
