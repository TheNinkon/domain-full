@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', $domain->name)

@section('vendor-style')
@vite(['resources/assets/vendor/libs/apex-charts/apex-charts.scss'])
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/apex-charts/apexcharts.js'])
@endsection

@section('page-script')
@vite(['resources/assets/js/domain-show.js'])
@endsection

@section('content')
<div class="row g-6">
  <div class="col-12 d-flex align-items-center justify-content-between flex-wrap gap-3">
    <div>
      <h4 class="mb-1">{{ $domain->name }}</h4>
      @include('content.domains._status_badge', ['status' => $domain->status])
    </div>
    <div class="d-flex gap-3">
      <button type="button"
        class="btn {{ $domain->days_until_expiration < 0 ? 'btn-danger' : ($domain->days_until_expiration <= 30 ? 'btn-warning' : 'btn-label-success') }}"
        data-bs-toggle="modal" data-bs-target="#renewModal">
        Renovar dominio
      </button>
      <button type="button" class="btn btn-label-primary" data-bs-toggle="modal" data-bs-target="#changeStatusModal">
        Cambiar estado
      </button>
      <a href="{{ route('domains.edit', $domain) }}" class="btn btn-primary">Editar</a>
      <a href="{{ route('domains.index') }}" class="btn btn-label-secondary">Volver</a>
    </div>
  </div>

  <div class="col-12">
    @include('_partials.alerts')
  </div>

  <div class="col-md-5">
    <div class="card mb-6">
      <div class="card-header">
        <h5 class="mb-0">Ficha</h5>
      </div>
      <div class="card-body">
        <ul class="list-unstyled mb-0">
          <li class="d-flex justify-content-between mb-3">
            <span class="text-body">Categoría</span>
            <span class="fw-medium">{{ $domain->category?->name ?? '—' }}</span>
          </li>
          <li class="d-flex justify-content-between mb-3">
            <span class="text-body">Registrador</span>
            <span class="fw-medium">{{ $domain->registrar ?? '—' }}</span>
          </li>
          <li class="d-flex justify-content-between mb-3">
            <span class="text-body">Prioridad</span>
            <span class="badge bg-label-{{ $domain->priority->color() }}">{{ $domain->priority->label() }}</span>
          </li>
          <li class="d-flex justify-content-between mb-3">
            <span class="text-body">Fecha de compra</span>
            <span class="fw-medium">{{ $domain->purchase_date->format('d/m/Y') }}</span>
          </li>
          <li class="d-flex justify-content-between mb-3">
            <span class="text-body">Fecha de vencimiento</span>
            <span class="fw-medium">
              {{ $domain->expiration_date->format('d/m/Y') }}
              @if ($domain->days_until_expiration < 0)
                <span class="badge bg-label-danger ms-1">Vencido</span>
              @elseif ($domain->days_until_expiration <= 30)
                <span class="badge bg-label-warning ms-1">{{ $domain->days_until_expiration }} días</span>
              @endif
            </span>
          </li>
          <li class="d-flex justify-content-between mb-3">
            <span class="text-body">Costo inicial</span>
            <span class="fw-medium">{{ $domain->currency }} {{ number_format($domain->purchase_cost, 2) }}</span>
          </li>
          <li class="d-flex justify-content-between mb-3">
            <span class="text-body">Última renovación</span>
            <span class="fw-medium">
              {{ $domain->renewal_date ? $domain->renewal_date->format('d/m/Y') : 'Nunca renovado' }}
              @if ($domain->renewal_cost)
                ({{ $domain->currency }} {{ number_format($domain->renewal_cost, 2) }})
              @endif
            </span>
          </li>
          <li class="d-flex justify-content-between mb-3">
            <span class="text-body">Renovación automática</span>
            <span class="fw-medium">{{ $domain->auto_renew ? 'Sí' : 'No' }}</span>
          </li>
          <li class="d-flex justify-content-between">
            <span class="text-body">Proyecto vinculado</span>
            <span class="fw-medium">{{ $domain->project?->name ?? '—' }}</span>
          </li>
        </ul>

        @if ($domain->notes)
          <hr />
          <p class="mb-0 text-body">{{ $domain->notes }}</p>
        @endif
      </div>
    </div>
  </div>

  <div class="col-md-7">
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0">Bitácora</h5>
      </div>
      <div class="card-body">
        <form action="{{ route('domains.notes.store', $domain) }}" method="POST" class="mb-6">
          @csrf
          <div class="mb-3">
            <textarea name="description" class="form-control" rows="2" placeholder="Agregar una nota..." required></textarea>
          </div>
          <button type="submit" class="btn btn-primary">Agregar nota</button>
        </form>

        <ul class="timeline mb-0">
          @forelse ($domain->logs as $log)
            <li class="timeline-item timeline-item-transparent">
              <span class="timeline-point timeline-point-{{ $log->type->color() }}"></span>
              <div class="timeline-event">
                <div class="timeline-header mb-1">
                  <span class="badge bg-label-{{ $log->type->color() }} me-2">
                    {{ $log->type->label() }}
                  </span>
                  <small class="text-body-secondary">{{ $log->created_at->format('d/m/Y H:i') }}</small>
                </div>
                <p class="mb-1">{{ $log->description }}</p>
                @if ($log->user)
                  <small class="text-body-secondary">— {{ $log->user->name }}</small>
                @endif
              </div>
            </li>
          @empty
            <p class="text-body mb-0">Todavía no hay movimientos registrados.</p>
          @endforelse
        </ul>
      </div>
    </div>
  </div>

  @if ($domain->is_for_sale || $totalVisits > 0)
    <div class="col-12">
      <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
          <h5 class="mb-0">Métricas de la landing</h5>
          <span class="text-body-secondary">{{ $totalVisits }} visitas totales</span>
        </div>
        <div class="card-body">
          <div id="domainVisitsChart"
            data-labels="{{ $visitChartLabels->toJson() }}"
            data-series="{{ $visitChartSeries->toJson() }}"></div>
        </div>
      </div>
    </div>
  @endif

  @if ($domain->is_for_sale || $domain->offers->isNotEmpty())
    <div class="col-12">
      <div class="card">
        <div class="card-header">
          <h5 class="mb-0">Ofertas recibidas</h5>
        </div>
        <div class="table-responsive">
          <table class="table mb-0">
            <thead>
              <tr>
                <th>De</th>
                <th>Monto</th>
                <th>Mensaje</th>
                <th>Estado</th>
                <th>Fecha</th>
                <th class="text-end">Acciones</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($domain->offers as $offer)
                <tr>
                  <td>
                    {{ $offer->name ?: '—' }}
                    <small class="text-body-secondary d-block">{{ $offer->email }}</small>
                    @if ($offer->phone)
                      <small class="text-body-secondary d-block">{{ $offer->phone }}</small>
                    @endif
                  </td>
                  <td>{{ $offer->currency }} {{ number_format($offer->amount, 2) }}</td>
                  <td>{{ $offer->message ?: '—' }}</td>
                  <td>@include('content.domains._offer_status_badge', ['status' => $offer->status])</td>
                  <td>{{ $offer->created_at->format('d/m/Y') }}</td>
                  <td class="text-end">
                    @if ($offer->status->value === 'pending')
                      <form action="{{ route('domains.offers.accept', [$domain, $offer]) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-label-success">Aceptar</button>
                      </form>
                      <form action="{{ route('domains.offers.reject', [$domain, $offer]) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-label-danger">Rechazar</button>
                      </form>
                    @endif
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="6" class="text-center text-body py-6">Todavía no hay ofertas para este dominio.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  @endif
</div>

<!-- Renew domain modal -->
<div class="modal fade" id="renewModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form action="{{ route('domains.renew', $domain) }}" method="POST">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title">Renovar dominio</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <p class="text-body-secondary small mb-4">
            Vencimiento actual: <strong>{{ $domain->expiration_date->format('d/m/Y') }}</strong>.
            Si vas a cambiar de proveedor (transferir el dominio), simplemente escribí el
            nuevo registrador abajo.
          </p>
          <div class="mb-4">
            <label class="form-label" for="renew_registrar">Registrador</label>
            <input type="text" class="form-control" id="renew_registrar" name="registrar"
              value="{{ $domain->registrar }}" placeholder="Namecheap, GoDaddy, ..." required />
          </div>
          <div class="mb-4">
            <label class="form-label" for="renew_expiration_date">Nueva fecha de vencimiento</label>
            <input type="date" class="form-control" id="renew_expiration_date" name="expiration_date"
              value="{{ $domain->expiration_date->copy()->addYear()->format('Y-m-d') }}" required />
          </div>
          <div class="row g-4">
            <div class="col-8">
              <label class="form-label" for="renew_cost">Costo de esta renovación</label>
              <input type="number" step="0.01" min="0" class="form-control" id="renew_cost" name="renewal_cost"
                value="{{ $domain->renewal_cost ?? $domain->purchase_cost }}" required />
            </div>
            <div class="col-4">
              <label class="form-label" for="renew_currency">Moneda</label>
              <input type="text" class="form-control" id="renew_currency" name="currency"
                value="{{ $domain->currency }}" maxlength="3" required />
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">Registrar renovación</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Change status modal -->
<div class="modal fade" id="changeStatusModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form action="{{ route('domains.status.update', $domain) }}" method="POST">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title">Cambiar estado</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-4">
            <label class="form-label" for="status-select">Nuevo estado</label>
            <select class="form-select" id="status-select" name="status">
              @foreach ($statuses as $status)
                <option value="{{ $status->value }}" @selected($domain->status === $status)>{{ $status->label() }}</option>
              @endforeach
            </select>
          </div>
          <p class="text-body-secondary small mb-3">
            Si el nuevo estado es "Proyecto activo", indica un proyecto existente o el nombre de uno nuevo.
          </p>
          <div class="mb-4">
            <label class="form-label" for="project_id">Proyecto existente</label>
            <select class="form-select" id="project_id" name="project_id">
              <option value="">— Ninguno —</option>
              @foreach ($projects as $project)
                <option value="{{ $project->id }}" @selected($domain->project_id === $project->id)>{{ $project->name }}</option>
              @endforeach
            </select>
          </div>
          <div>
            <label class="form-label" for="new_project_name">O crear un proyecto nuevo</label>
            <input type="text" class="form-control" id="new_project_name" name="new_project_name"
              placeholder="Nombre del proyecto/empresa" />
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">Guardar</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
