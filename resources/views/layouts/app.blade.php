<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>{{ config('app.name', 'Laravel') }}</title>

  @if (function_exists('public_path') && file_exists(public_path('css/app.css')))
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
  @else
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
  @endif
</head>
<body>
  <nav class="navbar navbar-expand-md navbar-light bg-white shadow-sm">
    <div class="container">
      <a class="navbar-brand" href="{{ url('/') }}">{{ config('app.name', 'Laravel') }}</a>
      <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarMain">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarMain">
        <ul class="navbar-nav mr-auto">
          <li class="nav-item"><a class="nav-link" href="{{ url('/shop') }}">Shop</a></li>
        </ul>
        @php $hideAuthNav = request()->is('install') || request()->is('install/*') || ! filter_var(env('APP_INSTALLED', false), FILTER_VALIDATE_BOOLEAN); @endphp
        @if (! $hideAuthNav)
        <ul class="navbar-nav ml-auto align-items-center">
          @guest
            <li class="nav-item"><a class="nav-link" href="{{ route('login') }}">Login</a></li>
            @if (Route::has('register'))
              <li class="nav-item"><a class="nav-link" href="{{ route('register') }}">Register</a></li>
            @endif
          @else
            @php
              $client = optional(Auth::user())->client;
              $unpaid = $client ? $client->invoices()->where('status','unpaid')->count() : 0;
            @endphp
            <li class="nav-item">
              <a class="nav-link" href="{{ url('/invoices') }}">Invoices
                @if($unpaid>0)
                  <span class="badge badge-pill badge-danger">{{ $unpaid }}</span>
                @endif
              </a>
            </li>
            <li class="nav-item"><a class="nav-link" href="{{ url('/services') }}">Services</a></li>
            @if(optional(Auth::user())->is_admin)
              <li class="nav-item"><a class="nav-link" href="{{ url('/admin/settings') }}">Admin</a></li>
            @endif
            <li class="nav-item dropdown">
              <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button" data-toggle="dropdown">{{ Auth::user()->name }}</a>
              <div class="dropdown-menu dropdown-menu-right">
                <a class="dropdown-item" href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">Logout</a>
                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">@csrf</form>
              </div>
            </li>
          @endguest
        </ul>
        @endif
      </div>
    </div>
  </nav>
  <main class="py-4"> @yield('content') </main>

  @if (function_exists('public_path') && file_exists(public_path('js/app.js')))
    <script src="{{ asset('js/app.js') }}" defer></script>
  @else
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
  @endif
</body>
</html>
