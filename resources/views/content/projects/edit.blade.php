@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Editar ' . $project->name)

@section('content')
<div class="row g-6">
  <div class="col-12">
    <h4 class="mb-0">Editar {{ $project->name }}</h4>
  </div>

  <div class="col-12">
    @include('_partials.alerts')

    <div class="card">
      <div class="card-body">
        <form action="{{ route('projects.update', $project) }}" method="POST">
          @csrf
          @method('PUT')
          @include('content.projects._form')

          <div class="mt-6 d-flex gap-3">
            <button type="submit" class="btn btn-primary">Guardar cambios</button>
            <a href="{{ route('projects.show', $project) }}" class="btn btn-label-secondary">Cancelar</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection
