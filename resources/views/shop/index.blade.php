@extends('layouts.app')
@section('content')
<div class="container">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h3 class="mb-0">Choose a Plan</h3>
    <a href="{{ url('/') }}" class="btn btn-link">Home</a>
  </div>

  @if($products->isEmpty())
    <div class="alert alert-info">No active products yet.</div>
  @else
    <div class="row">
      @foreach($products as $p)
        <div class="col-md-4 mb-3">
          <div class="card h-100 shadow-sm">
            <div class="card-body d-flex flex-column">
              <h5 class="card-title">{{ $p->name }}</h5>
              <p class="card-text text-muted">{{ $p->description }}</p>
              @if(is_array($p->options))
                <ul class="list-unstyled small mb-3">
                  @foreach($p->options as $k=>$v)
                    <li><strong>{{ ucfirst($k) }}:</strong> {{ is_scalar($v) ? $v : json_encode($v) }}</li>
                  @endforeach
                </ul>
              @endif
              <div class="mt-auto">
                <div class="h5 mb-3">{{ number_format($p->base_price,0) }} {{ $p->currency }}</div>
                <button class="btn btn-primary btn-block order-btn" data-id="{{ $p->id }}">Order Now</button>
              </div>
            </div>
          </div>
        </div>
      @endforeach
    </div>
  @endif
</div>
<script>
  (function(){
    var buttons = document.querySelectorAll('.order-btn');
    var tokenMeta = document.querySelector('meta[name="csrf-token"]');
    function handleClick(e){
      var id = this.getAttribute('data-id');
      var token = tokenMeta ? tokenMeta.getAttribute('content') : '';
      fetch("{{ url('/orders') }}", {
        method: 'POST',
        headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN': token },
        body: JSON.stringify({ product_id: id })
      }).then(async function(res){
        var data; try { data = await res.json(); } catch(_){ data = {}; }
        if(!res.ok){
          if(res.status === 401){ window.location = "{{ route('login') }}"; return; }
          if(res.status === 403){ alert('Please verify your email before ordering.'); return; }
          alert(data.message || 'Failed to create order');
          return;
        }
        if(data && data.invoice_id){ window.location = "{{ url('/invoices') }}/"+data.invoice_id; }
      }).catch(function(err){ console.error(err); alert('Network error'); });
    }
    buttons.forEach(function(b){ b.addEventListener('click', handleClick); });
  })();
</script>
@endsection

