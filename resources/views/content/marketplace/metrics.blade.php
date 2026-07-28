@php
$pageConfigs = ['myLayout' => 'blank', 'hasCustomizer' => false];
$customizerHidden = 'customizer-hide';
@endphp

@extends('layouts/marketplaceLayout')

@section('full-title', $domain->name . ' — Traffic metrics')
@section('meta-description', 'Traffic metrics for ' . $domain->name . '.')
@section('robots', 'index, follow')

@section('vendor-style')
@vite(['resources/assets/vendor/libs/apex-charts/apex-charts.scss'])
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/apex-charts/apexcharts.js'])
@endsection

@section('page-script')
@vite(['resources/assets/js/marketplace-metrics.js'])
@endsection

@section('page-style')
<style>
  .dm-page { background: #f5f5f9; min-height: 100vh; padding: 3rem 1.5rem; }
  .dm-page__inner { max-width: 900px; margin: 0 auto; }

  @media (max-width: 768px) {
    .dm-page { padding-bottom: calc(var(--dm-bottom-nav-height) + 1.5rem); }
  }
</style>
@endsection

@section('content')
<div class="dm-page">
  <div class="dm-page__inner">
    <div class="mb-6">
      <div class="text-body-secondary text-uppercase small fw-semibold mb-1">{{ $domain->name }}</div>
      <h1 class="mb-0" style="font-size: 1.75rem;">Traffic metrics</h1>
    </div>

    <div class="row g-6 mb-6">
      <div class="col-sm-6">
        <div class="card h-100">
          <div class="card-body">
            <div class="text-body-secondary small mb-1">Total visits</div>
            <div class="fs-2 fw-bold">{{ $totalVisits }}</div>
          </div>
        </div>
      </div>
      <div class="col-sm-6">
        <div class="card h-100">
          <div class="card-body">
            <div class="text-body-secondary small mb-1">This month</div>
            <div class="fs-2 fw-bold">{{ $visitsThisMonth }}</div>
          </div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header">
        <h5 class="mb-0">Last 30 days</h5>
      </div>
      <div class="card-body">
        <div id="marketplaceVisitsChart" data-labels="{{ $chartLabels->toJson() }}"
          data-series="{{ $chartSeries->toJson() }}"></div>
      </div>
    </div>
  </div>
</div>
@endsection
