@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Dominios')

@section('vendor-style')
@vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

@section('page-script')
@vite(['resources/assets/js/domains.js'])
@endsection

@section('content')
<div class="row g-6">
  <div class="col-12 d-flex align-items-center justify-content-between flex-wrap gap-3">
    <h4 class="mb-0">Dominios</h4>
    <div class="d-flex gap-3">
      <a href="{{ route('domains.categories.index') }}" class="btn btn-label-secondary">
        <i class="icon-base ti tabler-tag me-1"></i> Categorías
      </a>
      <a href="{{ route('domains.create') }}" class="btn btn-primary">
        <i class="icon-base ti tabler-plus me-1"></i> Nuevo dominio
      </a>
    </div>
  </div>

  <div class="col-12">
    @include('_partials.alerts')
  </div>

  <div class="col-12">
    <div class="card mb-6">
      <div class="card-body">
        <form method="GET" action="{{ route('domains.index') }}" class="row g-4 align-items-end">
          <div class="col-md-3">
            <label class="form-label" for="q">Buscar</label>
            <input type="text" class="form-control" id="q" name="q" value="{{ request('q') }}"
              placeholder="nombre del dominio" />
          </div>
          <div class="col-md-3">
            <label class="form-label" for="status">Estado</label>
            <select class="form-select" id="status" name="status">
              <option value="">Todos</option>
              @foreach ($statuses as $status)
                <option value="{{ $status->value }}" @selected(request('status') === $status->value)>
                  {{ $status->label() }}
                </option>
              @endforeach
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label" for="category">Categoría</label>
            <select class="form-select" id="category" name="category">
              <option value="">Todas</option>
              @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected(request('category') == $category->id)>
                  {{ $category->name }}
                </option>
              @endforeach
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label" for="expiring">Vence en (días)</label>
            <input type="number" min="1" class="form-control" id="expiring" name="expiring"
              value="{{ request('expiring') }}" placeholder="ej. 30" />
          </div>
          <div class="col-12 d-flex gap-3">
            <button type="submit" class="btn btn-primary">Filtrar</button>
            <a href="{{ route('domains.index') }}" class="btn btn-label-secondary">Limpiar</a>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="col-12">
    <div class="card">
      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th>Dominio</th>
              <th>Categoría</th>
              <th>Estado</th>
              <th>Prioridad</th>
              <th>Vence</th>
              <th>Costo renovación</th>
              <th>Proyecto</th>
              <th class="text-end">Acciones</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($domains as $domain)
              <tr>
                <td>
                  <a href="{{ route('domains.show', $domain) }}" class="fw-medium">{{ $domain->name }}</a>
                </td>
                <td>{{ $domain->category?->name ?? '—' }}</td>
                <td>@include('content.domains._status_badge', ['status' => $domain->status])</td>
                <td><span class="badge bg-label-{{ $domain->priority->color() }}">{{ $domain->priority->label() }}</span></td>
                <td>
                  <span
                    class="badge bg-label-{{ $domain->days_until_expiration < 0 ? 'danger' : ($domain->days_until_expiration <= 30 ? 'warning' : 'success') }}">
                    {{ $domain->expiration_date->format('d/m/Y') }}
                  </span>
                </td>
                <td>{{ $domain->renewal_cost ? $domain->currency . ' ' . number_format($domain->renewal_cost, 2) : '—' }}</td>
                <td>{{ $domain->project?->name ?? '—' }}</td>
                <td class="text-end">
                  <a href="{{ route('domains.show', $domain) }}" class="btn btn-icon btn-text-secondary">
                    <i class="icon-base ti tabler-eye"></i>
                  </a>
                  <a href="{{ route('domains.edit', $domain) }}" class="btn btn-icon btn-text-secondary">
                    <i class="icon-base ti tabler-edit"></i>
                  </a>
                  <form action="{{ route('domains.destroy', $domain) }}" method="POST" class="d-inline domain-delete-form">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-icon btn-text-danger">
                      <i class="icon-base ti tabler-trash"></i>
                    </button>
                  </form>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="8" class="text-center text-body py-6">No hay dominios que coincidan con estos filtros.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
      @if ($domains->hasPages())
        <div class="card-footer">
          {{ $domains->links() }}
        </div>
      @endif
    </div>
  </div>
</div>
@endsection
