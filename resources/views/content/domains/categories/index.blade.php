@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Categorías de dominios')

@section('vendor-style')
@vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

@section('page-script')
@vite(['resources/assets/js/domain-categories.js'])
@endsection

@section('content')
<div class="row g-6">
  <div class="col-12 d-flex align-items-center justify-content-between flex-wrap gap-3">
    <h4 class="mb-0">Categorías de dominios</h4>
    <div class="d-flex gap-3">
      <a href="{{ route('domains.index') }}" class="btn btn-label-secondary">Volver a dominios</a>
      <button type="button" id="newCategoryBtn" class="btn btn-primary" data-bs-toggle="modal"
        data-bs-target="#categoryModal">
        <i class="icon-base ti tabler-plus me-1"></i> Nueva categoría
      </button>
    </div>
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
              <th>Color</th>
              <th>Dominios</th>
              <th class="text-end">Acciones</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($categories as $category)
              <tr>
                <td>{{ $category->name }}</td>
                <td>
                  @if ($category->color)
                    <span class="badge" style="background-color: {{ $category->color }}">{{ $category->color }}</span>
                  @else
                    —
                  @endif
                </td>
                <td>{{ $category->domains_count }}</td>
                <td class="text-end">
                  <button type="button" class="btn btn-icon btn-text-secondary category-edit-btn"
                    data-bs-toggle="modal" data-bs-target="#categoryModal" data-id="{{ $category->id }}"
                    data-name="{{ $category->name }}" data-color="{{ $category->color }}"
                    data-action="{{ route('domains.categories.update', $category) }}">
                    <i class="icon-base ti tabler-edit"></i>
                  </button>
                  <form action="{{ route('domains.categories.destroy', $category) }}" method="POST"
                    class="d-inline category-delete-form">
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
                <td colspan="4" class="text-center text-body py-6">Todavía no hay categorías.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Create/Edit category modal -->
<div class="modal fade" id="categoryModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="categoryForm" method="POST" action="{{ route('domains.categories.store') }}"
        data-create-url="{{ route('domains.categories.store') }}">
        @csrf
        <input type="hidden" name="_method" id="categoryFormMethod" value="POST" />
        <div class="modal-header">
          <h5 class="modal-title" id="categoryModalLabel">Nueva categoría</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-4">
            <label class="form-label" for="categoryName">Nombre</label>
            <input type="text" class="form-control" id="categoryName" name="name" required />
          </div>
          <div>
            <label class="form-label" for="categoryColor">Color</label>
            <input type="color" class="form-control form-control-color" id="categoryColor" name="color"
              value="#696cff" />
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
