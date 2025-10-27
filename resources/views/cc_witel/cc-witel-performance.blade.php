@extends('layouts.cc-witel-performance-layout')

@section('styles')
    {{-- <link rel="stylesheet" href="{{ asset('css/inertia.css') }}"> --}}
    {{-- NOTE: change cards ui components to match with the react inertia ones in other repo --}}
    <link rel="stylesheet" href="{{ asset('css/ccwitel.css') }}">
@endsection

@section('content')
  <div className="mx-auto px-16 py-20 space-y-8">
      <div class="header-block box-shadow">
          <div class="header-content">
              {{-- Left side with title and subtitle --}}
              <div class="text-content">
                  <h1 class="header-title">Dashboard Performansi CC & Witel</h1>
                  <p class="header-subtitle">Monitoring dan Analisis Performa Revenue CC dan Witel</p>
              </div>

              {{-- Right side with the button --}}
              <div class="header-actions">
                  <button class="export-btn">
                      {{-- Make sure you have Font Awesome included in your project for this icon to appear --}}
                      <i class="fas fa-download"></i>
                      <span>Export Data</span>
                  </button>
              </div>
          </div>
      </div>

      <div id="trend-revenue" class="ccw-component">
          {{-- TODO: add YTD overview cards for trend revenue --}}
          @include('cc_witel.partials.trend-revenue-ccw')
      </div>

      <div id="witel-performance" class="ccw-component">
          @include('cc_witel.partials.witel-performance')
      </div>

      <div class="ccw-component">
          {{-- @include('cc_witel.partials.division-overview', ['_placeholder' => true]) --}}
      </div>

      <div class="ccw-component">
          {{-- @include('cc_witel.partials.top-customers', ['_placeholder' => true]) --}}
      </div>

      <div class="ccw-component">
          {{-- @include('cc_witel.partials.cc-performance', ['_placeholder' => true]) --}}
      </div>

      {{-- NOTE: this one is probably not needed --}}
      {{-- @include('cc_witel.partials.revenue-overview', ['_placeholder' => true]) --}}

  </div>
@endsection
