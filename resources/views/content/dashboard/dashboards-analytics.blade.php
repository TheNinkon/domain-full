@php
use Illuminate\Support\Facades\Auth;
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Dashboard')

@section('vendor-style')
@vite(['resources/assets/vendor/libs/apex-charts/apex-charts.scss'])
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/apex-charts/apexcharts.js'])
@endsection

@section('page-script')
@vite(['resources/assets/js/dashboards-analytics.js'])
@endsection

@section('content')
<div class="row g-6">
  <!-- Welcome -->
  <div class="col-12">
    <div class="card">
      <div class="card-body d-flex align-items-center justify-content-between flex-wrap gap-4">
        <div>
          <h4 class="mb-1">Hola, {{ Auth::user()->name }} 👋</h4>
          <p class="mb-0 text-body">Este es tu panel privado de gestión de dominios.</p>
        </div>
      </div>
    </div>
  </div>
  <!--/ Welcome -->

  <!-- Quick stats -->
  <div class="col-sm-6 col-xl-3">
    <a href="{{ route('domains.index') }}" class="card h-100 text-decoration-none">
      <div class="card-body">
        <div class="d-flex align-items-center mb-2">
          <div class="avatar me-3">
            <span class="avatar-initial rounded bg-label-primary"><i class="icon-base ti tabler-world icon-md"></i></span>
          </div>
          <h4 class="mb-0">{{ $totalDomains }}</h4>
        </div>
        <p class="mb-0 text-body">Dominios en cartera</p>
      </div>
    </a>
  </div>
  <div class="col-sm-6 col-xl-3">
    <a href="{{ route('domains.index', ['expiring' => 30]) }}" class="card h-100 text-decoration-none">
      <div class="card-body">
        <div class="d-flex align-items-center mb-2">
          <div class="avatar me-3">
            <span class="avatar-initial rounded bg-label-warning"><i class="icon-base ti tabler-alert-triangle icon-md"></i></span>
          </div>
          <h4 class="mb-0">{{ $expiringSoonCount }}</h4>
        </div>
        <p class="mb-0 text-body">Por vencer en 30 días</p>
      </div>
    </a>
  </div>
  <div class="col-sm-6 col-xl-3">
    <a href="{{ route('domains.index', ['status' => 'for_sale']) }}" class="card h-100 text-decoration-none">
      <div class="card-body">
        <div class="d-flex align-items-center mb-2">
          <div class="avatar me-3">
            <span class="avatar-initial rounded bg-label-success"><i class="icon-base ti tabler-tag icon-md"></i></span>
          </div>
          <h4 class="mb-0">{{ $forSaleCount }}</h4>
        </div>
        <p class="mb-0 text-body">En venta</p>
      </div>
    </a>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="card h-100">
      <div class="card-body">
        <div class="d-flex align-items-center mb-2">
          <div class="avatar me-3">
            <span class="avatar-initial rounded bg-label-info"><i class="icon-base ti tabler-currency-dollar icon-md"></i></span>
          </div>
          <h4 class="mb-0">${{ number_format($totalInvested, 2) }}</h4>
        </div>
        <p class="mb-0 text-body">Invertido en total</p>
      </div>
    </div>
  </div>
  <!--/ Quick stats -->

  @if ($totalDomains === 0)
    <!-- Empty state -->
    <div class="col-12">
      <div class="card">
        <div class="card-body text-center py-8">
          <i class="icon-base ti tabler-world icon-52px text-body-secondary mb-4"></i>
          <h5 class="mb-1">Todavía no tienes dominios registrados</h5>
          <p class="text-body mb-4">Empieza agregando el primero a tu cartera.</p>
          <a href="{{ route('domains.create') }}" class="btn btn-primary">Agregar dominio</a>
        </div>
      </div>
    </div>
    <!--/ Empty state -->
  @else
    <!-- Distribution charts -->
    <div class="col-md-6 col-xl-4">
      <div class="card h-100">
        <div class="card-header">
          <h5 class="mb-0">Por estado</h5>
        </div>
        <div class="card-body">
          <div id="statusDistributionChart"
            data-labels="{{ $statusDistribution->pluck('status')->map->label()->toJson() }}"
            data-colors="{{ $statusDistribution->pluck('status')->map->color()->toJson() }}"
            data-series="{{ $statusDistribution->pluck('total')->toJson() }}"></div>
        </div>
      </div>
    </div>

    <div class="col-md-6 col-xl-4">
      <div class="card h-100">
        <div class="card-header">
          <h5 class="mb-0">Por categoría</h5>
        </div>
        <div class="card-body">
          <div id="categoryDistributionChart"
            data-labels="{{ $categoryDistribution->pluck('name')->toJson() }}"
            data-series="{{ $categoryDistribution->pluck('total')->toJson() }}"></div>
        </div>
      </div>
    </div>

    <!-- Recent activity -->
    <div class="col-xl-4">
      <div class="card h-100">
        <div class="card-header">
          <h5 class="mb-0">Últimos movimientos</h5>
        </div>
        <div class="card-body">
          <ul class="timeline mb-0">
            @forelse ($recentLogs as $log)
              <li class="timeline-item timeline-item-transparent">
                <span class="timeline-point timeline-point-{{ $log->type->color() }}"></span>
                <div class="timeline-event">
                  <div class="timeline-header mb-1">
                    <a href="{{ route('domains.show', $log->domain) }}" class="fw-medium">{{ $log->domain->name }}</a>
                    <small class="text-body-secondary d-block">{{ $log->created_at->diffForHumans() }}</small>
                  </div>
                  <p class="mb-0">{{ $log->description }}</p>
                </div>
              </li>
            @empty
              <p class="text-body mb-0">Todavía no hay movimientos.</p>
            @endforelse
          </ul>
        </div>
      </div>
    </div>
    <!--/ Recent activity -->
  @endif
</div>
@endsection
