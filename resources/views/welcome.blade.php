@extends('layouts.app')
@section('content')
<div class="container text-center">
  <div class="py-5">
    <h1 class="display-4 mb-3">{{ config('app.name', 'Billing') }}</h1>
    <p class="lead text-muted">Simple billing & provisioning. Choose a plan and get started.</p>
    <p class="mt-4">
      <a href="{{ url('/shop') }}" class="btn btn-primary btn-lg">Shop Now</a>
    </p>
  </div>
</div>
@endsection

