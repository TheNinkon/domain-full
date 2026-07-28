@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', $project->name)

@section('content')
<div class="row g-6">
  <div class="col-12 d-flex align-items-center justify-content-between flex-wrap gap-3">
    <div>
      <h4 class="mb-1">{{ $project->name }}</h4>
      @include('content.projects._status_badge', ['status' => $project->status])
    </div>
    <div class="d-flex gap-3">
      <a href="{{ route('projects.edit', $project) }}" class="btn btn-primary">Editar</a>
      <a href="{{ route('projects.index') }}" class="btn btn-label-secondary">Volver</a>
    </div>
  </div>

  <div class="col-12">
    @include('_partials.alerts')
  </div>

  <div class="col-md-5">
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0">Detalle</h5>
      </div>
      <div class="card-body">
        @if ($project->url)
          <p class="mb-3">
            <a href="{{ $project->url }}" target="_blank" rel="noopener noreferrer">{{ $project->url }}</a>
          </p>
        @endif
        <p class="mb-0 text-body">{{ $project->description ?: 'Sin descripción.' }}</p>
      </div>
    </div>
  </div>

  <div class="col-md-7">
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0">Dominios vinculados</h5>
      </div>
      <div class="table-responsive">
        <table class="table mb-0">
          <thead>
            <tr>
              <th>Dominio</th>
              <th>Estado</th>
              <th>Vence</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($project->domains as $domain)
              <tr>
                <td><a href="{{ route('domains.show', $domain) }}">{{ $domain->name }}</a></td>
                <td>@include('content.domains._status_badge', ['status' => $domain->status])</td>
                <td>{{ $domain->expiration_date->format('d/m/Y') }}</td>
              </tr>
            @empty
              <tr>
                <td colspan="3" class="text-center text-body py-6">Este proyecto todavía no tiene dominios vinculados.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
@endsection
