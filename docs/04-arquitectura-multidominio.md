# Arquitectura Multi-Dominio (Fase 2)

Decisión tomada: **una sola aplicación Laravel, un solo despliegue, ruteo dinámico por
el header `Host`**. No se crea un proyecto/deploy por dominio.

## 1. Idea general

1. Todos los dominios que el admin marca `is_for_sale = true` apuntan, vía DNS
   (registro `A` a la IP del servidor, o `CNAME`/`ALIAS` si el proveedor lo permite),
   al mismo servidor donde vive esta app Laravel.
2. El servidor web (Apache en XAMPP hoy; Nginx/Apache en producción) debe aceptar
   requests para **cualquier Host** y enrutarlos todos al mismo document root /
   aplicación (un vhost "catch-all", en vez de un vhost por dominio).
3. Dentro de Laravel, un middleware inspecciona `Request::getHost()` en cada request:
   - Si el host coincide con el dominio "propio" de la plataforma (donde vive el panel
     admin, ej. `panel.tu-dominio-principal.com` o el dominio principal del proyecto) →
     sigue el flujo normal de rutas (`web.php` actual: login, dashboard, `/domains/...`).
   - Si el host coincide con un registro de la tabla `domains` que tenga
     `is_for_sale = true` → se sirve el `MarketplaceLandingController`, que resuelve el
     `Domain` por nombre y renderiza la landing genérica con esos datos.
   - Si el host no coincide con nada conocido → 404 (o redirect al sitio principal).

## 2. Dónde se implementa el ruteo por host

Laravel soporta *route groups con `domain()`*, pero como los dominios son dinámicos
(vienen de la base de datos, no se conocen en tiempo de definición de rutas), el enfoque
correcto es un **middleware global** (o un `Route::any('{any}', ...)` catch-all de baja
prioridad) que:

```
1. Lee el Host del request.
2. Si Host == dominio principal de la plataforma -> next() (deja pasar al resto de rutas normales).
3. Si Host existe en `domains` con is_for_sale=true -> despacha manualmente al MarketplaceLandingController.
4. Si no -> abort(404).
```

Esto se documenta como especificación de diseño; la implementación concreta
(middleware + fallback route) se aborda al iniciar Fase 2, no en Fase 1.

## 3. Configuración de servidor (infraestructura, fuera del código Laravel)

- **Local (XAMPP)**: para probar esto en desarrollo hace falta:
  - Entradas en `/etc/hosts` apuntando dominios de prueba (ej. `musicos.test`) a `127.0.0.1`.
  - Un vhost en Apache que acepte cualquier `ServerAlias` (`ServerAlias *`) apuntando al
    mismo `DocumentRoot` (`public/`) de este proyecto.
- **Producción**: cuando se compren dominios reales para vender,
  - Cada dominio necesita su registro DNS apuntando al servidor de producción.
  - El servidor web debe tener un vhost catch-all (Apache: `ServerAlias *` +
    `UseCanonicalName Off`; Nginx: `server_name _;` con `default_server`).
  - **SSL**: cada dominio público necesita su propio certificado válido para ese nombre
    (no sirve un solo certificado wildcard de un dominio distinto). Se recomienda
    automatizar emisión con Let's Encrypt (ej. vía `certbot` con hook que lea la lista de
    dominios activos desde la tabla `domains`, o un proxy tipo Caddy/Traefik que emite
    certificados on-demand por SNI). Esto se decide con más detalle al llegar a Fase 2,
    dependiendo de dónde se aloje la app en producción.

## 4. Por qué este enfoque y no "un proyecto por dominio"

- Con potencialmente 50+ dominios en venta, desplegar/mantener 50 instancias de la app
  sería inviable operativamente (actualizar código, aplicar fixes, ver métricas, todo
  se multiplicaría por 50).
- Un solo código base + una tabla `domains` como fuente de verdad permite agregar o dar
  de baja dominios del marketplace con un simple cambio de `is_for_sale`, sin releases
  ni configuración adicional por dominio (salvo el DNS y el certificado SSL, que sí son
  por dominio por naturaleza de cómo funciona internet).

## 5. Resumen de responsabilidades

| Capa | Responsable de qué |
|---|---|
| DNS (por dominio) | Que el dominio resuelva a la IP del servidor |
| Servidor web (Apache/Nginx) | Aceptar cualquier `Host` y pasar la request a la app Laravel |
| SSL (por dominio) | Certificado válido para cada nombre de dominio activo en venta |
| Middleware Laravel | Decidir, según `Host`, si sirve el panel admin o una landing de marketplace |
| Tabla `domains` | Única fuente de verdad de qué dominios existen y cuáles están en venta |
