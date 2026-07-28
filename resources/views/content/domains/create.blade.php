@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Nuevo dominio')

@section('content')
<div class="row g-6">
  <div class="col-12">
    <h4 class="mb-0">Nuevo dominio</h4>
  </div>

  <div class="col-12">
    @include('_partials.alerts')

    <div class="card">
      <div class="card-body">
        <form action="{{ route('domains.store') }}" method="POST">
          @csrf
          @include('content.domains._form')

          <div class="mt-6 d-flex gap-3">
            <button type="submit" class="btn btn-primary">Crear dominio</button>
            <a href="{{ route('domains.index') }}" class="btn btn-label-secondary">Cancelar</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection
