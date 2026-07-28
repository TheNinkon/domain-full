@php
use Illuminate\Support\Facades\Auth;
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Configuración de email')

@section('content')
<div class="row g-6">
  <div class="col-12">
    <h4 class="mb-0">Configuración de email (SMTP)</h4>
    <p class="text-body mb-0">
      Estas credenciales se usan para todos los emails del sistema: avisos de dominios
      por vencer y notificaciones de ofertas recibidas.
    </p>
  </div>

  <div class="col-12">
    @include('_partials.alerts')
  </div>

  <div class="col-md-8">
    <div class="card">
      <div class="card-body">
        <form action="{{ route('settings.mail.update') }}" method="POST">
          @csrf
          @method('PUT')
          <div class="row g-6">
            <div class="col-md-8">
              <label class="form-label" for="host">Host SMTP</label>
              <input type="text" class="form-control @error('host') is-invalid @enderror" id="host" name="host"
                value="{{ old('host', $settings->host) }}" placeholder="smtp.mailtrap.io" />
              @error('host')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-4">
              <label class="form-label" for="port">Puerto</label>
              <input type="number" class="form-control @error('port') is-invalid @enderror" id="port" name="port"
                value="{{ old('port', $settings->port) }}" placeholder="587" />
              @error('port')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6">
              <label class="form-label" for="username">Usuario</label>
              <input type="text" class="form-control @error('username') is-invalid @enderror" id="username"
                name="username" value="{{ old('username', $settings->username) }}" />
              @error('username')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6">
              <label class="form-label" for="password">Contraseña</label>
              <input type="password" class="form-control @error('password') is-invalid @enderror" id="password"
                name="password" placeholder="{{ $settings->password ? '••••••••  (dejar en blanco para no cambiar)' : '' }}" />
              @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6">
              <label class="form-label" for="encryption">Encriptación</label>
              <select class="form-select @error('encryption') is-invalid @enderror" id="encryption" name="encryption">
                <option value="" @selected(old('encryption', $settings->encryption) === null)>Ninguna</option>
                <option value="tls" @selected(old('encryption', $settings->encryption) === 'tls')>TLS</option>
                <option value="ssl" @selected(old('encryption', $settings->encryption) === 'ssl')>SSL</option>
              </select>
              @error('encryption')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6"></div>

            <div class="col-md-6">
              <label class="form-label" for="from_address">Email remitente</label>
              <input type="email" class="form-control @error('from_address') is-invalid @enderror" id="from_address"
                name="from_address" value="{{ old('from_address', $settings->from_address) }}"
                placeholder="no-reply@tudominio.com" />
              @error('from_address')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6">
              <label class="form-label" for="from_name">Nombre remitente</label>
              <input type="text" class="form-control @error('from_name') is-invalid @enderror" id="from_name"
                name="from_name" value="{{ old('from_name', $settings->from_name) }}"
                placeholder="{{ config('variables.templateName') }}" />
              @error('from_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
          </div>

          <div class="mt-6 d-flex gap-3">
            <button type="submit" class="btn btn-primary">Guardar</button>
          </div>
        </form>

        <hr class="my-6" />

        <form action="{{ route('settings.mail.test') }}" method="POST">
          @csrf
          <p class="text-body">
            Envía un email de prueba a tu propia cuenta ({{ Auth::user()->email }}) para
            confirmar que la configuración funciona.
          </p>
          <button type="submit" class="btn btn-label-secondary">Enviar email de prueba</button>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection
