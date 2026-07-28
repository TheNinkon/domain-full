@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Configuración de reCAPTCHA')

@section('content')
<div class="row g-6">
  <div class="col-12">
    <h4 class="mb-0">Google reCAPTCHA</h4>
    <p class="text-body mb-0">
      Mientras no cargues estas claves, el formulario de ofertas de la landing funciona
      sin reCAPTCHA. Apenas las guardes, se vuelve obligatorio completarlo para poder
      enviar una oferta.
    </p>
  </div>

  <div class="col-12">
    @include('_partials.alerts')
  </div>

  <div class="col-md-8">
    <div class="card">
      <div class="card-body">
        <form action="{{ route('settings.captcha.update') }}" method="POST">
          @csrf
          @method('PUT')
          <div class="row g-6">
            <div class="col-12">
              <label class="form-label" for="site_key">Site key</label>
              <input type="text" class="form-control @error('site_key') is-invalid @enderror" id="site_key"
                name="site_key" value="{{ old('site_key', $settings->site_key) }}" />
              @error('site_key')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-12">
              <label class="form-label" for="secret_key">Secret key</label>
              <input type="password" class="form-control @error('secret_key') is-invalid @enderror" id="secret_key"
                name="secret_key" placeholder="{{ $settings->secret_key ? '••••••••  (dejar en blanco para no cambiar)' : '' }}" />
              @error('secret_key')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
          </div>

          <div class="mt-6">
            <button type="submit" class="btn btn-primary">Guardar</button>
          </div>
        </form>

        <hr class="my-6" />

        <p class="text-body-secondary small mb-0">
          Conseguí tus claves de reCAPTCHA v2 ("I'm not a robot") en
          <a href="https://www.google.com/recaptcha/admin" target="_blank" rel="noopener noreferrer">
            google.com/recaptcha/admin
          </a>.
          Al registrar el sitio, agregá el/los dominios que vayas a vender (o
          <code>localhost</code> para probar en este entorno).
        </p>
      </div>
    </div>
  </div>
</div>
@endsection
