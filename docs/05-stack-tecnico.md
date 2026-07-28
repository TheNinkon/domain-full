# Stack Técnico y Convenciones del Repo

## 1. Lo que ya trae el proyecto (heredado del theme)

- **Framework**: Laravel 12, PHP 8.2 (`composer.json`).
- **Frontend/build**: Vite (`vite.config.js`), Bootstrap 5 + SCSS del theme, Sass,
  FontAwesome, librerías puntuales del theme (FullCalendar, form-validation, etc. — se
  usan solo si el módulo lo requiere, no hace falta cargarlas todas).
- **Vistas**: Blade, organizadas como `resources/views/content/<módulo>/<vista>.blade.php`
  + layouts en `resources/views/layouts` (sections: menu, navbar, footer, etc.).
- **Menú**: dirigido por JSON — `resources/menu/verticalMenu.json` (y
  `horizontalMenu.json` para el layout horizontal, que hoy no se usa según
  `config/custom.php: myLayout = vertical`). Cualquier módulo nuevo (Dominios, Proyectos,
  Categorías) debe agregar su entrada aquí para aparecer en el sidebar.
- **Controladores actuales**: organizados por sub-namespace bajo
  `App\Http\Controllers\{pages,authentications,language}` — convención a seguir para los
  módulos nuevos (ej. `App\Http\Controllers\domains\...`).
- **Configuración visual del theme**: `config/custom.php` (layout, tema claro/oscuro,
  customizer). No tocar salvo que se quiera cambiar la apariencia global.
- **Autenticación actual**: solo vistas (`auth-login-basic`, `auth-register-basic`), sin
  lógica real conectada todavía — se completa en Fase 1.
- **Locale**: `LanguageController` + carpeta `lang/` ya soportan cambio de idioma vía
  `/lang/{locale}`.

## 2. Decisiones para este proyecto (además de las ya registradas en `00-vision-y-alcance.md`)

- **Base de datos**: MySQL vía XAMPP.
  - Cambiar en `.env` (no en `.env.example` directamente, pero si se documenta el default
    del proyecto, actualizar `.env.example` con `DB_CONNECTION=mysql` y credenciales
    típicas de XAMPP: host `127.0.0.1`, usuario `root`, password vacío por defecto).
  - `SESSION_DRIVER`, `CACHE_STORE`, `QUEUE_CONNECTION` ya están en `database` en el
    `.env.example` actual — compatibles con MySQL sin cambios adicionales.
- **Roles**: MVP con columna `role` en `users` (`admin`/`staff`). No se introduce
  `spatie/laravel-permission` todavía — se deja como upgrade path documentado en
  `01-modelo-de-datos.md` si en el futuro se necesitan permisos granulares por acción.
- **Namespacing de módulos nuevos** (propuesta, a confirmar al implementar):
  - `App\Http\Controllers\domains\DomainController`
  - `App\Http\Controllers\domains\DomainCategoryController`
  - `App\Http\Controllers\domains\DomainLogController` (si se separa de `DomainController`)
  - `App\Http\Controllers\projects\ProjectController`
  - `App\Http\Controllers\marketplace\MarketplaceLandingController` (Fase 2)
  - `App\Models\{Domain,DomainCategory,DomainLog,DomainOffer,DomainDailyStat,Project}`
- **Vistas nuevas** siguiendo la convención existente:
  - `resources/views/content/domains/domains-list.blade.php`
  - `resources/views/content/domains/domains-create-edit.blade.php`
  - `resources/views/content/domains/domains-detail.blade.php`
  - `resources/views/content/projects/...`
  - `resources/views/content/marketplace/landing.blade.php` (Fase 2, pública, sin layout
    de panel admin)

## 3. Cosas a verificar/instalar antes de codear Fase 1

- [ ] `.env` local apuntando a MySQL de XAMPP (crear la base de datos, ej. `domain_manager`).
- [ ] `composer install` (si `vendor/` no está presente).
- [ ] `npm install` / `yarn install` + `npm run dev` (o `yarn dev`) para servir assets del
      theme (Vite).
- [ ] `php artisan key:generate` si `APP_KEY` no está seteada.
- [ ] `php artisan migrate` una vez existan las migraciones nuevas.

## 4. No incluido / evaluar después

- Tests automatizados: el proyecto trae `phpunit.xml` y carpeta `tests/` del scaffold de
  Laravel, pero sin tests propios todavía. Se recomienda al menos cubrir el modelo de
  estados de `domains` y el cálculo de "días para vencer" con tests de feature, pero no
  es bloqueante para arrancar Fase 1.
