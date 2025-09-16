<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Invoice {{ $invoice->number }}</title>
  <style>
    body { font-family: Arial, Helvetica, sans-serif; color:#111; margin: 24px; }
    .header { display:flex; justify-content:space-between; align-items:flex-start; margin-bottom: 24px; }
    .brand { font-size: 20px; font-weight: bold; }
    .invoice-meta { text-align:right; }
    .badge { display:inline-block; padding:2px 8px; border-radius:4px; font-size:12px; }
    .badge-success { background:#16a34a; color:#fff; }
    .badge-warning { background:#d97706; color:#fff; }
    .badge-secondary { background:#6b7280; color:#fff; }
    table { width:100%; border-collapse: collapse; }
    th, td { padding:8px; border-bottom:1px solid #e5e7eb; text-align:left; }
    th { background:#f3f4f6; }
    .text-right { text-align:right; }
    .totals { margin-top: 12px; width: 40%; float: right; }
    .small { color:#6b7280; font-size: 12px; }
    .print-btn { display: inline-block; padding: 8px 12px; background: #2563eb; color:#fff; border-radius: 4px; text-decoration: none; }
    @media print { .no-print { display: none; } body { margin: 0; } }
  </style>
</head>
<body>
  <div class="no-print" style="text-align:right; margin-bottom:10px;">
    <a href="#" onclick="window.print(); return false;" class="print-btn">Print / Save as PDF</a>
  </div>
  <div class="header">
    <div class="brand">
      @if(!empty($settings['company']['logo']))
        <img src="{{ $settings['company']['logo'] }}" alt="Logo" style="height:48px; vertical-align:middle;"/>
      @endif
      <span style="margin-left:8px;">{{ $settings['brand'] ?? config('app.name','Billing') }}</span>
    </div>
    <div class="invoice-meta">
      <div><strong>Invoice:</strong> {{ $invoice->number }}</div>
      <div><strong>Date:</strong> {{ optional($invoice->created_at)->format('Y-m-d') }}</div>
      <div><strong>Status:</strong>
        <span class="badge badge-{{ $invoice->status==='paid'?'success':($invoice->status==='unpaid'?'warning':'secondary') }}">{{ ucfirst($invoice->status) }}</span>
      </div>
    </div>
  </div>

  <table style="width:100%; margin-bottom: 18px;">
    <tr>
      <td style="width:60%; vertical-align:top;">
        <div class="small"><strong>Bill To</strong></div>
        <div>{{ optional(optional($invoice->client)->user)->name }}</div>
        <div class="small">{{ optional(optional($invoice->client)->user)->email }}</div>
      </td>
      <td style="width:40%; vertical-align:top; text-align:right;">
        <div class="small"><strong>From</strong></div>
        <div>{{ $settings['company']['name'] }}</div>
        @if(!empty($settings['company']['address']))<div class="small">{!! nl2br(e($settings['company']['address'])) !!}</div>@endif
        @if(!empty($settings['company']['tax_id']))<div class="small">Tax ID: {{ $settings['company']['tax_id'] }}</div>@endif
        @if(!empty($settings['company']['email']))<div class="small">{{ $settings['company']['email'] }}</div>@endif
        @if(!empty($settings['company']['phone']))<div class="small">{{ $settings['company']['phone'] }}</div>@endif
      </td>
    </tr>
  </table>

  <table>
    <thead>
      <tr>
        <th>Description</th>
        <th class="text-right">Qty</th>
        <th class="text-right">Unit</th>
        <th class="text-right">Line</th>
      </tr>
    </thead>
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

  <table class="totals">
    <tr><td>Subtotal</td><td class="text-right">{{ number_format($invoice->subtotal ?? $invoice->total,0) }} {{ $invoice->currency }}</td></tr>
    @if(($invoice->tax_total ?? 0) > 0)
      <tr><td>Tax</td><td class="text-right">{{ number_format($invoice->tax_total,0) }} {{ $invoice->currency }}</td></tr>
    @endif
    <tr><th>Total</th><th class="text-right">{{ number_format($invoice->total,0) }} {{ $invoice->currency }}</th></tr>
  </table>

  <div style="clear:both"></div>
  @if($invoice->status!=='paid' && !empty($settings['manual_instructions']))
    <h4>Payment Instructions</h4>
    <p class="small" style="white-space: pre-wrap;">{{ $settings['manual_instructions'] }}</p>
  @endif

  @php($terms = (string) (\App\Support\Settings::get('invoice.footer','')))
  @if(!empty($terms))
    <h4>Terms</h4>
    <p class="small" style="white-space: pre-wrap;">{{ $terms }}</p>
  @endif

  @if($invoice->status==='paid')
    <div style="position:fixed; top:45%; left:50%; transform: translate(-50%,-50%) rotate(-20deg); font-size:64px; color: rgba(22,163,74,.2); font-weight:bold;">PAID</div>
  @endif
</body>
</html>
