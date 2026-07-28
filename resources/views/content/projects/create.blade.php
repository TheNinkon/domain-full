@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Nuevo proyecto')

@section('content')
<div class="row g-6">
  <div class="col-12">
    <h4 class="mb-0">Nuevo proyecto</h4>
  </div>

  <div class="col-12">
    @include('_partials.alerts')

    <div class="card">
      <div class="card-body">
        <form action="{{ route('projects.store') }}" method="POST">
          @csrf
          @include('content.projects._form')

          <div class="mt-6 d-flex gap-3">
            <button type="submit" class="btn btn-primary">Crear proyecto</button>
            <a href="{{ route('projects.index') }}" class="btn btn-label-secondary">Cancelar</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection
