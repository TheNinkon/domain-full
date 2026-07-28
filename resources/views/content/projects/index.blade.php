@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Proyectos')

@section('vendor-style')
@vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

@section('page-script')
@vite(['resources/assets/js/projects.js'])
@endsection

@section('content')
<div class="row g-6">
  <div class="col-12 d-flex align-items-center justify-content-between flex-wrap gap-3">
    <h4 class="mb-0">Proyectos</h4>
    <a href="{{ route('projects.create') }}" class="btn btn-primary">
      <i class="icon-base ti tabler-plus me-1"></i> Nuevo proyecto
    </a>
  </div>

  <div class="col-12">
    @include('_partials.alerts')
  </div>

  <div class="col-12">
    <div class="card">
      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th>Nombre</th>
              <th>Estado</th>
              <th>Dominios</th>
              <th class="text-end">Acciones</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($projects as $project)
              <tr>
                <td>
                  <a href="{{ route('projects.show', $project) }}" class="fw-medium">{{ $project->name }}</a>
                </td>
                <td>@include('content.projects._status_badge', ['status' => $project->status])</td>
                <td>{{ $project->domains_count }}</td>
                <td class="text-end">
                  <a href="{{ route('projects.show', $project) }}" class="btn btn-icon btn-text-secondary">
                    <i class="icon-base ti tabler-eye"></i>
                  </a>
                  <a href="{{ route('projects.edit', $project) }}" class="btn btn-icon btn-text-secondary">
                    <i class="icon-base ti tabler-edit"></i>
                  </a>
                  <form action="{{ route('projects.destroy', $project) }}" method="POST" class="d-inline project-delete-form">
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
                <td colspan="4" class="text-center text-body py-6">Todavía no hay proyectos.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
      @if ($projects->hasPages())
        <div class="card-footer">
          {{ $projects->links() }}
        </div>
      @endif
    </div>
  </div>
</div>
@endsection
