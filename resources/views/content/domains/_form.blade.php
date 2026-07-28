@php
$domain = $domain ?? null;
@endphp

<div class="row g-6">
  <div class="col-md-6">
    <label class="form-label" for="name">Dominio</label>
    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name"
      value="{{ old('name', $domain?->name ?? '') }}" placeholder="ejemplo.com" required autofocus />
    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
  </div>

  <div class="col-md-6">
    <label class="form-label" for="domain_category_id">Categoría</label>
    <select class="form-select @error('domain_category_id') is-invalid @enderror" id="domain_category_id"
      name="domain_category_id">
      <option value="">Sin categoría</option>
      @foreach ($categories as $category)
        <option value="{{ $category->id }}"
          @selected(old('domain_category_id', $domain?->domain_category_id) == $category->id)>
          {{ $category->name }}
        </option>
      @endforeach
    </select>
    @error('domain_category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
  </div>

  <div class="col-md-6">
    <label class="form-label" for="registrar">Registrador</label>
    <input type="text" class="form-control @error('registrar') is-invalid @enderror" id="registrar" name="registrar"
      value="{{ old('registrar', $domain?->registrar ?? '') }}" placeholder="Namecheap, GoDaddy, ..." />
    @error('registrar')<div class="invalid-feedback">{{ $message }}</div>@enderror
  </div>

  <div class="col-md-6">
    <label class="form-label" for="priority">Prioridad</label>
    <select class="form-select @error('priority') is-invalid @enderror" id="priority" name="priority">
      @foreach ($priorities as $priority)
        <option value="{{ $priority->value }}" @selected(old('priority', $domain?->priority?->value ?? 'medium') === $priority->value)>
          {{ $priority->label() }}
        </option>
      @endforeach
    </select>
    @error('priority')<div class="invalid-feedback">{{ $message }}</div>@enderror
  </div>

  <div class="col-md-6">
    <label class="form-label" for="currency">Moneda</label>
    <input type="text" class="form-control @error('currency') is-invalid @enderror" id="currency" name="currency"
      value="{{ old('currency', $domain?->currency ?? 'USD') }}" maxlength="3" required />
    @error('currency')<div class="invalid-feedback">{{ $message }}</div>@enderror
  </div>

  <div class="col-md-6">
    <label class="form-label" for="purchase_date">Fecha de compra</label>
    <input type="date" class="form-control @error('purchase_date') is-invalid @enderror" id="purchase_date"
      name="purchase_date"
      value="{{ old('purchase_date', $domain?->purchase_date?->format('Y-m-d')) }}" required />
    @error('purchase_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
  </div>

  <div class="col-md-6">
    <label class="form-label" for="expiration_date">Fecha de vencimiento</label>
    <input type="date" class="form-control @error('expiration_date') is-invalid @enderror" id="expiration_date"
      name="expiration_date"
      value="{{ old('expiration_date', $domain?->expiration_date?->format('Y-m-d')) }}" required />
    <div class="form-text">La fecha que te dio el registrador al comprarlo (o la actual).</div>
    @error('expiration_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
  </div>

  <div class="col-md-6">
    <label class="form-label" for="purchase_cost">Costo inicial</label>
    <input type="number" step="0.01" min="0" class="form-control @error('purchase_cost') is-invalid @enderror"
      id="purchase_cost" name="purchase_cost" value="{{ old('purchase_cost', $domain?->purchase_cost ?? '') }}"
      required />
    @error('purchase_cost')<div class="invalid-feedback">{{ $message }}</div>@enderror
  </div>

  <div class="col-12">
    <div class="form-check form-switch">
      <input class="form-check-input" type="checkbox" id="auto_renew" name="auto_renew" value="1"
        @checked(old('auto_renew', $domain?->auto_renew ?? true)) />
      <label class="form-check-label" for="auto_renew">Renovación automática activada</label>
    </div>
  </div>

  <div class="col-12">
    <label class="form-label" for="notes">Notas</label>
    <textarea class="form-control @error('notes') is-invalid @enderror" id="notes" name="notes" rows="3">{{ old('notes', $domain?->notes ?? '') }}</textarea>
    @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
  </div>

  <div class="col-12">
    <hr class="my-2" />
    <h6 class="mb-1">SEO de la landing pública</h6>
    <p class="text-body-secondary small mb-4">
      Se usan en el `&lt;title&gt;` y la meta descripción de la página pública cuando el
      dominio está en venta. Si se dejan vacíos, se usa un texto genérico.
    </p>
  </div>

  <div class="col-md-6">
    <label class="form-label" for="seo_title">Título SEO</label>
    <input type="text" class="form-control @error('seo_title') is-invalid @enderror" id="seo_title" name="seo_title"
      value="{{ old('seo_title', $domain?->seo_title ?? '') }}"
      placeholder="{{ $domain?->name ?? 'ejemplo.com' }} is for sale" />
    @error('seo_title')<div class="invalid-feedback">{{ $message }}</div>@enderror
  </div>

  <div class="col-md-6">
    <label class="form-label" for="seo_description">Meta descripción SEO</label>
    <input type="text" class="form-control @error('seo_description') is-invalid @enderror" id="seo_description"
      name="seo_description" value="{{ old('seo_description', $domain?->seo_description ?? '') }}"
      placeholder="This premium domain name is available for purchase." maxlength="500" />
    @error('seo_description')<div class="invalid-feedback">{{ $message }}</div>@enderror
  </div>
</div>
