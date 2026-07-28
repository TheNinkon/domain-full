@php
$project = $project ?? null;
@endphp

<div class="row g-6">
  <div class="col-md-6">
    <label class="form-label" for="name">Nombre</label>
    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name"
      value="{{ old('name', $project?->name ?? '') }}" required autofocus />
    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
  </div>

  <div class="col-md-6">
    <label class="form-label" for="status">Estado</label>
    <select class="form-select @error('status') is-invalid @enderror" id="status" name="status">
      @foreach ($statuses as $status)
        <option value="{{ $status->value }}" @selected(old('status', $project?->status?->value ?? 'idea') === $status->value)>
          {{ $status->label() }}
        </option>
      @endforeach
    </select>
    @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
  </div>

  <div class="col-12">
    <label class="form-label" for="url">URL (opcional)</label>
    <input type="text" class="form-control @error('url') is-invalid @enderror" id="url" name="url"
      value="{{ old('url', $project?->url ?? '') }}" placeholder="https://..." />
    @error('url')<div class="invalid-feedback">{{ $message }}</div>@enderror
  </div>

  <div class="col-12">
    <label class="form-label" for="description">Descripción</label>
    <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description"
      rows="3">{{ old('description', $project?->description ?? '') }}</textarea>
    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
  </div>
</div>
