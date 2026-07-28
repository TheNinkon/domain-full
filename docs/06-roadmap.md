# Roadmap / Backlog Ejecutable

Ordenado en milestones. Cada milestone es, en teoría, deployable/demostrable por sí solo.
Pensado para retomarse directamente en la próxima sesión de implementación.

## Milestone 0 — Preparación de entorno ✅ (completado 2026-07-25)

- [x] Configurar `.env` con MySQL (XAMPP), crear base de datos (`domain_manager`).
- [x] Verificar `composer install`, `yarn install`, `php artisan key:generate`.
- [x] Confirmar que el proyecto corre (vhost de XAMPP, `http://localhost/domain/public/`)
      y se ve el theme demo (`pages-home`) sin errores.

Notas de la puesta en marcha (para no repetir el mismo problema):

- El PHP de línea de comandos por defecto en esta máquina es Homebrew PHP 8.5.7, pero
  Apache de XAMPP corre con **PHP 8.2.4** (`/Applications/XAMPP/xamppfiles/bin/php`).
  `composer install` debe correrse siempre con el PHP de XAMPP
  (`/Applications/XAMPP/xamppfiles/bin/php /opt/homebrew/bin/composer install`), si no,
  Composer resuelve versiones de paquetes que requieren PHP ≥8.4 y la app rompe con 500
  al servirse por Apache.
- MySQL de XAMPP acepta `root` sin password por defecto.
- Apache corre como usuario `daemon`, que no pertenece al grupo dueño de `storage/` y
  `bootstrap/cache`. Hubo que dar permiso de escritura a "otros"
  (`chmod -R o+w storage bootstrap/cache`) para que Laravel pueda escribir cache/logs/sessions.
- Yarn no estaba instalado (`brew install yarn`); luego `yarn install` + `yarn build` para
  generar `public/build/manifest.json` (Apache sirve build de producción, no el dev
  server de Vite).
- `APP_URL` quedó en `http://localhost/domain/public` (proyecto servido dentro del
  htdocs de XAMPP, sin vhost dedicado). Si más adelante se crea un vhost propio
  (ej. `domain-manager.test`), actualizar `APP_URL` y `/etc/hosts` acorde.

## Milestone 1 — Autenticación real ✅ (completado 2026-07-26)

- [x] Migración: agregar `role` a `users` (`2026_07_25_215419_add_role_to_users_table.php`).
- [x] Seeder: `DatabaseSeeder` crea/actualiza el admin real vía `ADMIN_NAME`/`ADMIN_EMAIL`/
      `ADMIN_PASSWORD` en `.env` (ya seedeado con el email real del propietario).
- [x] `LoginBasic::authenticate` con `Auth::attempt` real, CSRF, `remember me`, rate
      limiting (`throttle:5,1`) y regeneración de sesión. `LoginBasic::logout` invalida
      sesión y regenera el token.
- [x] Middleware `auth` protegiendo `/` y `/page-2`; `redirectGuestsTo('/auth/login-basic')`
      configurado en `bootstrap/app.php`.
- [x] Registro público deshabilitado: la ruta `/auth/register-basic` fue eliminada de
      `routes/web.php` (el controller/vista del theme quedan sin usar, no se borraron).

### Decisión: Jetstream vs. auth manual

La documentación oficial de Vuexy (`laravel-auth-jetstream.html`) recomienda **Laravel
Jetstream** + un paquete propietario (`pixinvent/vuexy-laravel-bootstrap-jetstream`) que
adapta las vistas de Jetstream (Tailwind) a Bootstrap. Se investigó y se descartó por:

- Ese paquete de swap **no está en Packagist ni venía incluido en la compra** (se revisó
  el ZIP completo del theme en `~/Downloads/vuexy-admin-v10.11.1/html-laravel-version/`,
  tanto `starter-kit` como `full-version` — ninguno lo trae).
- El propio `LoginBasic.php` del theme (en starter-kit y en full-version) es un stub
  **idéntico** al nuestro antes de cablearlo: una vista sin lógica de autenticación. Es
  decir, Vuexy no resuelve el backend de auth en ningún caso — siempre hay que
  implementarlo, con o sin Jetstream.
- Jetstream trae Livewire, equipos, verificación de email, 2FA — todo pensado para SaaS
  multi-usuario. Para un panel privado de 1-2 administradores es sobredimensionado.

Se optó por autenticación nativa de Laravel (`Auth::attempt`, hash de password vía el
cast `'password' => 'hashed'` del modelo, CSRF, throttle) sobre la vista real
`auth-login-basic.blade.php` del theme — el mismo nivel de seguridad, sin el peso de
Jetstream. Si en el futuro se necesita API pública (Fase 2 ofertas vía API, tokens, etc.)
se puede añadir Sanctum de forma incremental sin rehacer esto.

### Se adoptó el dashboard real del theme ("Analytics") como home autenticado

A pedido explícito de aprovechar al máximo el diseño/estructura de Vuexy: se reemplazó
el placeholder vacío `pages-home` (`<h4>Home Page</h4>`) por el dashboard real
`content.dashboard.dashboards-analytics` (controlador `App\Http\Controllers\dashboard\Analytics`),
copiado desde `~/Downloads/vuexy-admin-v10.11.1/.../full-version` (no venía en el
starter-kit). Ruta `/` renombrada a `dashboard-analytics`. El contenido de este dashboard
es el demo de e-commerce/analítica genérico de Vuexy — **se reemplazará por widgets
reales de dominios en el Milestone 5**, reutilizando el mismo layout/estructura
(swiper cards, apex-charts, statistics cards) en vez de partir de cero.

Al activar el dashboard con un usuario autenticado real apareció un bug latente del
theme: `resources/views/layouts/sections/navbar/navbar-partial.blade.php` llama
directamente a `Laravel\Jetstream\Jetstream::hasApiFeatures()` /
`hasTeamFeatures()` sin verificar si la clase existe — como no usamos Jetstream, esto
tiraba `Class "Laravel\Jetstream\Jetstream" not found` en cuanto había una sesión
autenticada real (con guest funcionaba por el corto-circuito de `&&`). Se eliminó el
bloque de "Manage Team" y "API Tokens" del dropdown de usuario (dependían 100% de
Jetstream). El resto del navbar (perfil, logout, customizer) ya funcionaba correctamente
porque el theme sí usa `Route::has()`/`Auth::check()` ahí.

También se limpiaron `resources/menu/verticalMenu.json` y `horizontalMenu.json`: se quitaron
las entradas de menú "Login"/"Register" (no tienen sentido en el sidebar de un panel ya
autenticado) y "Home" se renombró a "Dashboard" apuntando al slug `dashboard-analytics`.

Además, el dashboard incluye una tabla (DataTables) que pide vía AJAX
`assets/json/user-profile.json` — ese archivo de datos demo tampoco venía en el
starter-kit (solo en `full-version`). Se copió igual que las imágenes. Es data falsa de
ejemplo; se reemplaza en Milestone 5 por datos reales de dominios/proyectos.

### Limpieza posterior: adaptar login/dashboard/menú al proyecto (2026-07-26)

El dashboard "Analytics" traído del theme resultó ser un demo genérico de e-commerce/
marketing (ventas, earnings, campañas de email, tabla de proyectos con datos falsos) sin
relación con gestión de dominios. Se reemplazó por completo por un dashboard propio y
honesto (sin datos inventados):

- Tarjeta de bienvenida con el nombre del usuario autenticado.
- 4 tarjetas de métricas en `0` (dominios en cartera, por vencer en 30 días, en venta,
  invertido en total) — se conectan a datos reales en el Milestone 2/5.
- Estado vacío explicando que el listado llega en el Milestone 2.
- Se quitaron las dependencias de esa página (apex-charts, swiper, datatables) que ya no
  se usan aquí, y las imágenes/JSON de demo que se habían copiado quedaron huérfanas —
  se borraron.

También se corrigieron dos bugs del theme detectados al usarlo con datos reales:

- **Ícono de admin roto**: `navbar-partial.blade.php` mostraba
  `Auth::user()->profile_photo_url` para usuarios logueados (atributo de Jetstream que
  no existe en nuestro `User`), resultando en `src=""` y el ícono roto. Guests sí veían el
  avatar por defecto. Se corrigió para usar siempre `assets/img/avatars/1.png`.
- **`Class "Auth" not found`**: este proyecto (Laravel 12 minimal skeleton) no registra
  alias globales de facades en `config/app.php`; cualquier vista Blade que use `Auth::`
  directamente necesita `use Illuminate\Support\Facades\Auth;` en su propio bloque
  `@php`, igual que ya hacía `navbar-partial.blade.php`.

Rebranding a "Domain Manager" en `config/variables.php` (título, meta og:title, nombre
mostrado en login/sidebar/footer) y footer simplificado (se quitaron los links
promocionales de Pixinvent: License/More Themes/Documentation/Support — no aplican a un
panel privado). Menú lateral reducido a solo "Dashboard" (se quitaron "Page 2", vacío sin
ningún contenido, y "Error", una vitrina de demo que no pertenece a la navegación de un
producto real — el controlador/vista de `misc-error` se conservan para reusarlos como
página de error real más adelante, solo se sacaron del menú). Se eliminaron
`Page2.php`/`pages-page2.blade.php` por completo (dead code sin ninguna referencia).

## Milestone 2 — Modelo de datos base ✅ (completado 2026-07-26)

- [x] Migraciones: `domain_categories`, `projects`, `domains`, `domain_logs` (en ese
      orden, con timestamps consecutivos a propósito por las FKs).
- [x] Modelos Eloquent + relaciones (`Domain::category()`, `Domain::project()`,
      `Domain::logs()`, `Project::domains()`, `DomainCategory::domains()`,
      `DomainLog::domain()`/`user()`, `User::domainLogs()`).
- [x] Factories/seeders de ejemplo (3 categorías, 8 dominios incluyendo 2 por vencer y
      1 en venta) para poder ver el panel poblado durante el desarrollo.

Decisiones de implementación:

- **Enums PHP nativos** (`App\Enums\DomainStatus`, `ProjectStatus`, `DomainLogType`) en
  vez de strings sueltos — cada uno tiene `label()` en español para la UI futura. Las
  columnas MySQL `enum(...)` de cada migración se generan desde
  `array_column(Enum::cases(), 'value')`, así el enum PHP es la única fuente de verdad
  (cambiar los valores solo implica tocar el enum + una migración nueva).
- `domains.domain_category_id` y `domains.project_id` son `nullOnDelete()`: borrar una
  categoría o proyecto no borra los dominios, solo los desvincula. Verificado con
  Tinker.
- `domain_logs.domain_id` es `cascadeOnDelete()` (si se hard-elimina un dominio, se
  borra su bitácora). Soft-delete de un dominio (lo normal en el panel) **no** dispara
  este cascade — se probó con Tinker: el log persiste tras un soft delete.
- `domain_logs` no tiene `updated_at` (es un log append-only): la migración usa
  `timestamp('created_at')->useCurrent()` en vez de `timestamps()`, y el modelo declara
  `const UPDATED_AT = null`.
- `Domain` y `Project` usan `SoftDeletes`; `DomainCategory` no (categorías se listan y
  se borran de verdad si no tienen dominios, según lo documentado en Fase 1 §2).
- Todo verificado con `php artisan tinker`: casts de enum, relaciones, soft delete,
  `nullOnDelete`. Luego `migrate:fresh --seed` para dejar la BD en estado limpio.

## Milestone 3 — CRUD de Categorías y Proyectos ✅ (completado 2026-07-26, cerrado después de Milestone 4)

- [x] Controlador + vista de `domain_categories`: `DomainCategoryController` (namespace
      `domains`), una sola página (`/domains/categories`) con tabla + modal compartido
      para crear/editar (mismo patrón que el modal de cambio de estado de Milestone 4).
      Borrado bloqueado si la categoría tiene dominios (`$category->domains()->exists()`).
- [x] Controlador + vistas de `projects`: `ProjectController` (namespace `projects`) con
      `index` (listado paginado), `create`/`edit` (form compartido `_form.blade.php`),
      `show` (detalle + tabla de dominios vinculados vía `$project->domains`). Borrado
      bloqueado si tiene dominios vinculados, mismo criterio que categorías.
- [x] Menú restructurado: "Dominios" pasa a ser un item con `submenu` (Listado,
      Categorías) — esto además corrige el problema cosmético anotado en Milestone 4
      (ahora el resaltado "activo" del grupo funciona por prefijo de ruta gracias a la
      lógica de `submenu` del theme, cubre `/domains`, `/domains/create`,
      `/domains/{id}`, `/domains/categories`, etc.). "Proyectos" quedó como item plano
      top-level (mismo límite cosmético que tenía "Dominios" antes: solo se resalta en
      `/projects` exacto, no en `/projects/{id}`).
- [x] Acceso cruzado: botón "Categorías" en el listado de dominios, y "Volver a
      dominios" en la página de categorías.

Notas de implementación:

- El modal de categorías usa `<input type="color">` nativo (sin librería de color
  picker) — alcanza para elegir un color hex y coincide con la validación
  `regex:/^#[0-9a-fA-F]{6}$/` del controlador.
- Borrado de categorías/proyectos: mismo patrón SweetAlert2 que dominios (Milestone 4),
  en `resources/assets/js/domain-categories.js` y `resources/assets/js/projects.js`.
- Probado end-to-end con curl (login real): crear/editar/borrar categoría (bloqueado con
  dominios, permitido sin ellos), crear/editar proyecto, vincular un dominio existente a
  un proyecto desde el modal de cambio de estado de Domain (Milestone 4) y verificar que
  aparece en `projects.show`, borrado de proyecto bloqueado con dominio vinculado.
  Se resetó la BD (`migrate:fresh --seed`) al terminar para dejarla en el estado semilla
  original.

## Milestone 4 — CRUD de Dominios (núcleo) ✅ (completado 2026-07-26, se saltó Milestone 3)

- [x] Listado (`/domains`) con filtros (nombre, estado, categoría, "vence en N días") y
      orden por vencimiento ascendente. Paginado con `paginate()` de Laravel.
- [x] Alta de dominio (`/domains/create`) + log automático `system` de creación.
- [x] Edición (`/domains/{id}/edit`) con logging automático: cambios en `purchase_cost`/
      `renewal_cost` generan un log `price_change`; cambios en `renewal_date`/
      `expiration_date` generan un log `renewal`. Cada uno guarda el valor anterior y
      nuevo en `meta`.
- [x] Cambio de estado explícito vía modal (no se edita `status` desde el form normal):
      si el nuevo estado es `active_project`, exige seleccionar un proyecto existente o
      escribir el nombre de uno nuevo (se crea con `ProjectController`... en realidad se
      resolvió dentro del propio `DomainController@updateStatus`, sin crear un
      `ProjectController` separado — Milestone 3 completo de Proyectos queda pendiente).
- [x] Borrado = soft delete, con confirmación SweetAlert2 en el listado.
- [x] Vista de detalle (`/domains/{id}`) con ficha completa + timeline de `domain_logs`
      (componente `.timeline` nativo del core del theme) + textarea para nota rápida.

Se saltó Milestone 3 (CRUD de Categorías/Proyectos) a pedido explícito. Consecuencias:

- El formulario de dominio permite elegir una categoría **existente** (las 3 sembradas en
  el seeder) pero no crear categorías nuevas desde la UI todavía — hace falta Milestone 3
  o `tinker`/seeder para agregar más.
- Los proyectos sí se pueden crear "al vuelo" únicamente desde el modal de cambio de
  estado (nombre only, sin descripción/url) porque el flujo de "graduar un dominio a
  proyecto" lo necesitaba sí o sí. Gestión completa de proyectos (editar
  descripción/url/estado, ver qué dominios tiene) sigue pendiente en Milestone 3.

### Decisión: Blade + paginación nativa, no el patrón AJAX/DataTables "oficial" de Vuexy

Se investigó `laravel-crud.html` y el ejemplo real `app/Http/Controllers/laravel_example/UserManagement.php`
del theme: usan DataTables con `serverSide: true` vía un endpoint JSON a medida +
formulario en un offcanvas + FormValidation.js + SweetAlert2 para confirmar borrado. Es
el patrón "oficial" recomendado por Pixinvent.

Se adoptó parcialmente: **SweetAlert2 para confirmar el borrado** (mismo patrón, ver
`resources/assets/js/domains.js`). Se descartó el resto (DataTables server-side +
offcanvas) porque:

- El modelo `Domain` tiene ~13 campos editables (vs. 2 en el ejemplo de usuarios) —
  un offcanvas lateral queda apretado; un formulario de página completa es más usable.
- La especificación de Fase 1 pide una página de detalle con bitácora/timeline propia,
  algo que el ejemplo de Vuexy no contempla (su patrón es solo listado + edición rápida).
- Para el volumen esperado (decenas/cientos de dominios propios, no miles), la
  paginación nativa de Laravel es igual de rápida, muchísimo menos código para mantener,
  y no depende de sincronizar un endpoint JSON a mano con los filtros de la URL.

### Otras notas

- `App\Models\Domain::days_until_expiration` es un accessor (`Attribute::make`) usado
  para colorear badges de vencimiento (rojo si venció, amarillo si ≤30 días).
- Rutas bajo `domains.*` (`index`, `create`, `store`, `show`, `edit`, `update`, `destroy`,
  `notes.store`, `status.update`), todas dentro del grupo `auth` existente.
- Se agregó "Dominios" al sidebar (`resources/menu/verticalMenu.json` y
  `horizontalMenu.json`). Nota: el resaltado "activo" del menú solo hace match exacto de
  nombre de ruta (no hay lógica de prefijo para items sin submenu en
  `verticalMenu.blade.php`), así que el ítem del sidebar solo se ve "activo" en
  `/domains`, no en `/domains/{id}` ni `/domains/create`. Cosmético, no se resolvió por
  no justificar restructurar el menú a un submenu por un solo link.
- Todo probado end-to-end con `curl` (login real + CSRF): alta, filtros, detalle, nota,
  cambio de estado con creación de proyecto inline, edición con logging de cambios,
  validación de nombre duplicado, y borrado (soft delete). Datos de prueba generados
  durante las pruebas se limpiaron después (`forceDelete`).

## Milestone 5 — Dashboard ✅ (completado 2026-07-26)

- [x] `App\Http\Controllers\dashboard\Analytics` ahora consulta datos reales en vez de
      servir la vista con ceros hardcodeados (placeholder de Milestone 1).
- [x] Widgets: total de dominios, por vencer en 30 días, en venta, costo total invertido
      (`SUM(purchase_cost) + SUM(renewal_cost)` de los dominios actuales — no incluye
      historial de renovaciones pasadas, ver limitación documentada en
      `01-modelo-de-datos.md §9). Las 3 primeras tarjetas son clicables y llevan al
      listado de dominios ya filtrado.
- [x] Distribución por estado y por categoría: donut charts con ApexCharts
      (`resources/assets/js/dashboards-analytics.js`, reescrito — el original traído del
      theme quedó descartado, no se usó nada de su contenido salvo el nombre de archivo).
- [x] Feed de últimos movimientos: últimos 8 `domain_logs` de todos los dominios
      (`DomainLog::with(['domain','user'])->latest()->limit(8)`), mismo componente
      `.timeline` que la vista de detalle de un dominio.
- [x] Estado vacío se mantiene si `totalDomains === 0` (sin dominios todavía).

### Bugs encontrados y corregidos durante esta implementación

- **Filtro "vence en N días" contaba también los ya vencidos.** Tanto en el dashboard
  como en `domains.index?expiring=N` se usaba `where('expiration_date', '<=',
  now()->addDays(N))`, que también matchea cualquier fecha *pasada* (un dominio vencido
  hace un año es `<=` a "dentro de 30 días"). Se cambió a
  `whereBetween('expiration_date', [now(), now()->addDays(N)])` en ambos lugares
  (`DomainController@index` y `Analytics@index`). Detectado al ver que el dashboard
  contaba 7 de 8 dominios como "por vencer" cuando en realidad solo 3 lo estaban
  (4 ya estaban vencidos desde hace meses/años en los datos de semilla).
- **`Attempt to read property on null`** en `$domain->category->name ?? '—'`,
  `$domain->project->name ?? '—'` (índice y detalle de dominio) y en el form-partial
  compartido de dominios/proyectos (`$domain->campo`, `$project->campo` cuando el
  formulario está en modo "crear", es decir `$domain`/`$project` es `null`). En PHP 8 leer
  una propiedad de `null` es solo un *warning*, no un error fatal — por eso no se veía
  como un 500 en las pruebas de Milestone 3/4, pero igual era código incorrecto que
  ensuciaba los logs (y podía romper la página en un entorno con `display_errors` distinto).
  Se corrigió con el operador null-safe (`?->`) en los ~12 lugares afectados: ambos
  `_form.blade.php`, `domains/index.blade.php`, `domains/show.blade.php`, y
  `Analytics@index` (`$row->category?->name`). Verificado creando un dominio real sin
  categoría y confirmando que no aparece ningún "Attempt to read property" en la
  respuesta.
- Se centralizaron los colores de badge que antes vivían duplicados como arrays locales
  en cada blade (`_status_badge.blade.php` de dominios/proyectos, timeline de
  `domains/show.blade.php`): ahora `DomainStatus`, `ProjectStatus` y `DomainLogType`
  tienen un método `color()` además de `label()`, mismo patrón, una sola fuente de
  verdad. El dashboard reutiliza `$status->color()` para los colores del donut chart de
  estados (mapeado a `window.config.colors` del theme, que ya expone
  primary/secondary/success/info/warning/danger).

Todo probado con curl (login real): números de las 4 tarjetas verificados contra la BD
directamente, filtro de vencimiento corregido y re-verificado, creación de dominio sin
categoría para confirmar el fix del null-safe, y sintaxis del JS compilado verificada con
`node --check`. Se intentó verificación visual con el skill `claude-in-chrome` pero el
usuario declinó instalar la extensión — queda pendiente una revisión visual manual de
los donut charts en el navegador.

## Milestone 6 — Alertas de vencimiento ✅ (completado 2026-07-26)

- [x] Comando `php artisan domains:check-expirations` (`app/Console/Commands/CheckDomainExpirations.php`):
      busca dominios con `expiration_date` entre hoy y +7 días (`whereBetween`, mismo fix
      de rango que Milestone 5 — sin el límite inferior también atraparía dominios ya
      vencidos), y si hay alguno le manda un email a cada usuario `role = admin`.
- [x] Mailable `App\Mail\DomainsExpiringSoonMail` (markdown, componentes `<x-mail::message>`
      / `<x-mail::table>` / `<x-mail::button>` nativos de Laravel — no hace falta publicar
      ningún vendor:asset). Vista en `resources/views/emails/domains-expiring-soon.blade.php`:
      tabla con dominio/fecha/días restantes + botón al panel filtrado
      (`/domains?expiring=7`).
- [x] Scheduler registrado en `routes/console.php`: `Schedule::command('domains:check-expirations')->dailyAt('08:00')`.
      Verificado con `php artisan schedule:list`.

### Cómo activarlo de verdad (pasos manuales, fuera del código)

- **Cron del sistema operativo**: Laravel solo *registra* el horario; hace falta que algo
  externo llame a `php artisan schedule:run` cada minuto. En este Mac con XAMPP no hay
  ningún cron configurado todavía — para que el aviso realmente se dispare todos los días
  a las 8am hay que agregar una entrada de cron (`crontab -e`):
  ```
  * * * * * cd /Applications/XAMPP/xamppfiles/htdocs/domain && /Applications/XAMPP/xamppfiles/bin/php artisan schedule:run >> /dev/null 2>&1
  ```
  (usar el PHP de XAMPP, no el de Homebrew — mismo motivo que en Milestone 0). Sin este
  paso, `domains:check-expirations` solo corre si se ejecuta manualmente.
- **Mail driver real**: `.env` tiene `MAIL_MAILER=log` (heredado del `.env.example` de
  Laravel) — los emails no se envían de verdad, se escriben en
  `storage/logs/laravel.log`. Para recibir el aviso en una bandeja de entrada real hace
  falta configurar un driver real (`smtp`, `ses`, `postmark`, etc.) con credenciales
  propias en `.env`. Se deja así a propósito: no hay credenciales de un proveedor de
  email para configurar en este momento.

### Bug de permisos encontrado (mismo patrón que Milestone 0)

Al correr el comando por primera vez, `storage/logs/laravel.log` ya existía **creado por
Apache** (usuario `daemon`, modo `644`) de sesiones de pruebas anteriores — el `w` (CLI)
no podía escribir en él, y el intento de loguear el email fallaba con
`UnexpectedValueException: ... Permission denied`. El `chmod -R o+w storage` de
Milestone 0 no alcanza para archivos creados *después* por Apache con permisos más
restrictivos. Se borró el archivo (recreándolo como `w`) y se le dio `chmod 666`. Si
vuelve a pasar (Apache escribe primero de nuevo), el fix es el mismo: borrar
`storage/logs/laravel.log` y volver a correr cualquier comando artisan, o simplemente
`chmod 666` si el archivo ya es propiedad de `w`.

Probado end-to-end: se forzó la fecha de vencimiento de un dominio semilla a +3 días con
Tinker, se corrió el comando, se verificó el contenido completo del email (tabla,
botón, asunto con el conteo) en el log, y se restauró la base a su estado semilla
original (`migrate:fresh --seed`).

**Fin de Fase 1** — checklist de aceptación completo en `02-fase-1-panel-privado.md §8`.

---

## Milestone 7 — Modelo de datos de marketplace (inicio Fase 2) ✅ (completado 2026-07-26)

- [x] Migraciones: `domain_offers` (domain_id, name, email, amount, currency, message,
      status, ip_address) y `domain_daily_stats` (domain_id, date, visits,
      unique_visitors, unique en `[domain_id, date]`).
- [x] Enum `App\Enums\OfferStatus` (pending/accepted/rejected/expired) con `label()` y
      `color()`, mismo patrón que los enums de Fase 1.
- [x] Modelos `DomainOffer` y `DomainDailyStat` + relaciones `Domain::offers()` (más
      recientes primero) y `Domain::dailyStats()`.
- [x] Factories `DomainOfferFactory` y `DomainDailyStatFactory` para pruebas/seeding
      futuro.
- [x] Toggle de `is_for_sale`: en vez de agregar un control separado en la UI (como
      decía el plan original), se sincronizó automáticamente con el cambio de estado en
      `DomainController@updateStatus` — al pasar el estado a "En venta" (`for_sale`) se
      pone `is_for_sale = true`, y al cambiarlo a cualquier otro estado se pone `false`.
      **Por qué:** el modelo de datos ya tenía dos campos para el mismo concepto
      (`status = for_sale` y `is_for_sale`); un toggle independiente podía quedar
      desincronizado (ej. status "En venta" pero `is_for_sale` en false). Se probó el
      ciclo completo vía HTTP: cambiar a `for_sale` pone `is_for_sale=1`, volver a
      `watching` lo vuelve a poner en `0`.

Probado con Tinker: creación de oferta y stat diario, el `unique(domain_id, date)`
rechaza correctamente una segunda fila para el mismo día. BD reseteada a su estado
semilla al terminar.

## Milestone 8 — Ruteo multi-dominio ✅ (completado 2026-07-26)

- [x] Middleware global `App\Http\Middleware\ResolveMarketplaceHost`, registrado con
      `$middleware->prepend(...)` en `bootstrap/app.php` (corre antes del ruteo, no
      dentro del grupo `web`).
- [x] `App\Http\Controllers\marketplace\MarketplaceLandingController` + vista pública
      `content/marketplace/landing.blade.php` (inglés, `layouts/blankLayout` — sin
      sidebar/navbar del panel; esta página no debe llevar ninguna marca de "Domain
      Manager" porque el visitante está viendo el dominio en venta, no nuestra app).
- [x] Probado end-to-end con `curl -H "Host: ..."` en vez de tocar `/etc/hosts` o
      vhosts de Apache (ver "Cómo se probó" abajo).

### Se intentó `Route::domain()` primero — se revirtió por un bug real

La primera implementación agrupó todas las rutas del panel bajo `Route::domain($adminHost)`
y agregó un catch-all `Route::get('/{any?}', ...)` sin restricción de dominio para el
marketplace. Funcionaba (probado con `curl -H Host`), **pero rompió la generación de
URLs**: `route('domains.index')` empezó a devolver `http://localhost/domains` en vez de
`http://localhost/domain/public/domains` — perdía el subdirectorio `/domain/public` en el
que vive esta app dentro del htdocs de XAMPP. `url('/')` seguía bien; el problema era
específico de rutas con restricción de dominio combinadas con un `APP_URL` que no es la
raíz del host. Esto rompía **todos los links/redirects internos** generados con
`route()` (confirmado con el link de logout: redirigía a `http://localhost/auth/login-basic`,
un 404).

Se revirtió a un middleware (el plan original de `04-arquitectura-multidominio.md`), que
no requiere tocar el registro de rutas para nada — las rutas del panel quedaron
exactamente como en Milestone 7, sin agrupar. El middleware corre **antes** de que
Laravel intente matchear cualquier ruta:

```php
// app/Http/Middleware/ResolveMarketplaceHost.php
public function handle(Request $request, Closure $next): Response
{
    $adminHost = parse_url(config('app.url'), PHP_URL_HOST) ?: 'localhost';

    if ($request->getHost() === $adminHost) {
        return $next($request); // sigue al ruteo normal del panel
    }

    return response(app(MarketplaceLandingController::class)->show($request));
}
```

```php
// bootstrap/app.php
$middleware->prepend(ResolveMarketplaceHost::class);
```

`prepend()`/`append()` (sin "ToGroup") agregan al stack de middleware **verdaderamente
global** de la aplicación (el que corre antes de resolver cualquier ruta) — a diferencia
de `$middleware->web(...)`, que solo se aplica a rutas que ya matchearon dentro del grupo
`web`. Por eso hace falta `prepend`/`append` y no `web()` para poder interceptar
requests a hosts para los que **no existe ninguna ruta** (como cualquier dominio en
venta): si dependiera del grupo `web`, Laravel intentaría resolver la ruta primero, no
encontraría ninguna, y devolvería 404 antes de que el middleware llegue a ejecutarse.

`MarketplaceLandingController@show` sigue igual: resuelve el dominio con
`Domain::where('name', $request->getHost())->where('is_for_sale', true)->firstOrFail()`
— si no existe o no está en venta, `firstOrFail()` dispara `ModelNotFoundException`, que
sigue propagándose hasta el manejador de excepciones del Kernel (el middleware corre
dentro del mismo `try/catch` que todo lo demás) y se renderiza como 404 normal.

**Lección:** `Route::domain()` es la opción más "declarativa", pero asume que la app
vive en la raíz de su propio host — cierto en cualquier despliegue real (un dominio
propio apuntando directo al servidor), pero no en este entorno local donde todo cuelga
de `http://localhost/domain/public/` dentro del htdocs compartido de XAMPP. Un
middleware verdaderamente global no tiene ese supuesto y funciona en ambos casos.

### Cómo se probó (sin tocar `/etc/hosts` ni Apache)

Apache de XAMPP no rechaza un header `Host` arbitrario — sirve el mismo vhost/document
root sin importar el valor, exactamente lo que se necesita para esto en producción
también (vhost catch-all, ver `04-arquitectura-multidominio.md §3`). Eso permite probar
todo el mecanismo con `curl -H "Host: lo-que-sea"` contra `localhost`, sin `/etc/hosts`,
sin DNS real, sin configurar un vhost nuevo:

- `Host: localhost` (el real) → panel admin normal, `route()`/`url()` generan bien el
  prefijo `/domain/public`, login/logout/redirects sin cambios.
- `Host: <dominio-inexistente>` → 404 en cualquier path.
- `Host: <dominio real con is_for_sale=1>` → landing 200 con el nombre del dominio.
- `Host: <mismo dominio for_sale>` pidiendo `/domains` → **no** expone el panel real
  (confirmado inspeccionando el HTML: solo aparece "For Sale", nada de la tabla de
  dominios ni "Nuevo dominio") — el middleware intercepta *cualquier* path bajo ese
  host y siempre sirve la misma landing, que es el comportamiento esperado de un sitio
  de una sola página.

### Pendiente para producción (no aplica en local)

Cuando esto se despliegue de verdad, cada dominio comprado necesita su registro DNS
apuntando al servidor y un certificado SSL propio — ver `04-arquitectura-multidominio.md`
para el detalle (Let's Encrypt / Caddy / Traefik como opciones). Nada de esto es
necesario para desarrollar o probar el mecanismo de ruteo en sí, que ya quedó verificado
en local.

## Milestone 9 — Formulario de oferta + notificaciones ✅ (completado 2026-07-26)

- [x] Formulario público de oferta (name/email/amount/message) en la landing →
      `MarketplaceLandingController@storeOffer` crea `domain_offers` (status `pending`)
      + un `domain_logs` tipo `offer_received`.
- [x] Rate limiting: `RateLimiter` facade directo (5 intentos/hora por IP), no el
      middleware `throttle:` de rutas — ver nota sobre sesión/CSRF más abajo.
- [x] Anti-spam: honeypot (`<input name="website">` oculto vía CSS, invisible para
      humanos). Si viene lleno, se responde con el mismo mensaje de éxito (para no
      revelarle al bot que fue detectado) pero **no** se crea la oferta ni se consume el
      rate limit.
- [x] Notificación por email a todos los `role = admin` (`App\Mail\OfferReceivedMail`,
      markdown, con link directo a `domains.show` en el panel).
- [x] Acción de aceptar/rechazar: `DomainOfferController` (`domains.offers.accept` /
      `.reject`), nueva sección "Ofertas recibidas" en `domains/show.blade.php`. Al
      aceptar, un mensaje sugiere (no fuerza) cambiar el estado del dominio a "Vendido"
      — igual que estaba documentado en `03-fase-2-marketplace.md`.
- [x] La landing también muestra "N ofertas recibidas · oferta promedio $X" (excluye
      rechazadas) cuando hay al menos una — dato real, no el placeholder que se había
      evitado en el dashboard de Milestone 5.

### Sin sesión ni CSRF en el formulario público — por diseño, no por descuido

`ResolveMarketplaceHost` (Milestone 8) intercepta estos requests **antes** de que el
grupo de middleware `web` corra, así que no hay `StartSession` ni `VerifyCsrfToken` para
las rutas de marketplace. Consecuencias que hubo que resolver:

- **No se puede usar `redirect()->with()` / `withErrors()` / `withInput()`** (dependen de
  sesión). `storeOffer()` en vez de redirigir, **renderiza la misma vista directamente**
  pasando `offerSubmitted`, `formErrors` (array plano, no `$errors` de Blade) y `old`
  (array plano) como variables explícitas. La vista los usa con `{{ $old['email'] ?? '' }}`
  en vez de los helpers `old()`/`@error` de siempre.
- **El formulario no lleva `@csrf`** (no habría token de sesión contra el cual validarlo).
  Es aceptable acá: no hay sesión de un usuario logueado que un atacante pueda abusar
  (CSRF protege acciones autenticadas) — el rate limiting + honeypot son la defensa real
  para un formulario público anónimo.
- El rate limiting se implementó con la fachada `RateLimiter` **dentro** del controlador
  (`tooManyAttempts`/`hit` por IP), no con el middleware `throttle:` de rutas, porque
  tampoco hay rutas registradas para el marketplace (todo pasa por el middleware, no por
  `routes/web.php`).

### Feature no pedida explícitamente por Milestone 9, agregada a pedido del usuario: configuración de SMTP desde el panel

El usuario pidió poder cargar las credenciales SMTP desde la UI del admin más adelante,
en vez de editar `.env` a mano, y que **todo** lo que use email (ofertas, alertas de
vencimiento de Milestone 6) funcione automáticamente con esas credenciales.

- Tabla `mail_settings` (fila única, id 1: host/port/username/password/encryption/
  from_address/from_name). `password` con cast `encrypted` de Eloquent (usa `APP_KEY`,
  nunca se guarda en texto plano — verificado en MySQL directamente).
- `AppServiceProvider::boot()` sobreescribe `config('mail.mailers.smtp.*')` y
  `config('mail.default')`/`config('mail.from.*')` en cada request con lo que haya en
  `mail_settings`, **si** está "configurado" (`isConfigured()`: host+port+from_address
  presentes). Si no hay nada cargado, sigue usando lo que diga `.env`
  (`MAIL_MAILER=log`) sin romper nada. Guardado con `Schema::hasTable()` para no fallar
  en instalaciones nuevas antes de migrar.
- Página `/settings/mail`: formulario (el campo password nunca se re-muestra, solo se
  actualiza si se escribe uno nuevo) + botón "Enviar email de prueba"
  (`App\Mail\TestMail`) que manda un email real a la cuenta del admin logueado y muestra
  el error tal cual si falla (probado con credenciales SMTP inventadas: intentó
  autenticar contra el servidor real y mostró el error de credenciales inválidas de
  vuelta en el panel — confirma que usa lo cargado, no el driver `log` del `.env`).
- Como esto vive en `AppServiceProvider::boot()` (no en una ruta ni middleware
  específico), aplica automáticamente a **cualquier** `Mail::send()` de la app —
  `domains:check-expirations` (Milestone 6) y `OfferReceivedMail` (este milestone) ya
  quedan cubiertos sin tocarlos.

Todo probado end-to-end con curl (`-H "Host: ..."` para el lado público, login real
para el panel): oferta legítima, honeypot rechazado sin crear registro, rate limit
disparando en el intento correcto (5 permitidos + Jane Buyer previa = bloqueo esperado
en el 5to intento del loop), aceptar/rechazar desde el panel, y guardado/lectura de
configuración SMTP con contraseña cifrada. BD reseteada a su estado semilla al terminar.

## Milestone 10 — Tracking de visitas y métricas en landing ✅ (completado 2026-07-26)

- [x] Contador de visitas en `MarketplaceLandingController@show` (se decidió ponerlo ahí
      directamente y no en un middleware separado — ver "Decisión" abajo) →
      `domain_daily_stats`, una fila por dominio/día, `increment()` atómico.
- [x] "Únicos" sin cookies: hash `sha1(ip + user-agent + fecha)` guardado en `Cache` con
      TTL hasta fin de día. Si el hash ya estaba, se suma solo a `visits`; si es nuevo,
      también a `unique_visitors`. Sin fingerprinting agresivo, sin cookies de tracking.
- [x] Bots/crawlers excluidos por User-Agent (regex simple: bot/crawl/spider/slurp/
      facebookexternalhit/whatsapp/telegrambot) — **y también `curl`/`wget`**, a
      propósito, para que las pruebas con curl de esta misma sesión no ensuciaran los
      números reales.
- [x] Landing pública: "N ofertas recibidas · oferta promedio $X · N visitas este mes"
      (cada parte solo aparece si hay dato real, nunca se muestra "0 visitas" como si
      fuera una métrica real).
- [x] Panel admin (`domains/show.blade.php`): nueva sección "Métricas de la landing" con
      gráfico de área (ApexCharts) de visitas de los últimos 30 días + total histórico.
      Reutiliza `config.colors` del theme, mismo patrón que los donuts del dashboard
      (Milestone 5).

### Decisión: contar la visita en el controlador, no en un middleware aparte

El plan original hablaba de un "middleware/contador". Se implementó directamente dentro
de `MarketplaceLandingController@show()` en cambio, porque:

- `ResolveMarketplaceHost` (Milestone 8) ya es el único punto de entrada para requests de
  marketplace — agregar un SEGUNDO middleware solo para contar visitas sería una capa
  más sin necesidad real, cuando el propio controlador ya sabe exactamente qué dominio
  se está sirviendo (se lo pasa `resolveDomain()`).
- Mantiene todo el estado (honeypot, rate limit, ahora tracking) junto en un solo lugar
  fácil de leer, en vez de repartido entre middleware y controlador para una sola
  request.

Se probó con `curl` simulando tres escenarios en la misma corrida: User-Agent de `curl`
(excluido, 0 filas creadas), User-Agent de navegador real repetido dos veces desde el
mismo "visitante" (`visits=2`, `unique_visitors=1` — confirma que el hash diario
funciona), y un User-Agent de Googlebot (excluido, los números no cambiaron). El número
mostrado en la landing ("3 visits this month") coincidió exactamente con la suma en
`domain_daily_stats`. BD reseteada a su estado semilla al terminar.

**Fin de Fase 2 (MVP marketplace)**. Fases 1 y 2 completas: gestión privada de dominios
(Milestones 0-6) + marketplace público con ofertas y métricas (Milestones 7-10).

## Milestone 11 — reCAPTCHA, teléfono, SEO real y sitio multi-página del marketplace ✅ (completado 2026-07-26)

Pedido puntual del usuario después de cerrar la Fase 2, no estaba en el roadmap
original. Cinco piezas:

### reCAPTCHA (Google v2 "no soy un robot")

- Tabla `captcha_settings` (fila única, igual patrón que `mail_settings`): `site_key` +
  `secret_key` (cifrado con cast `encrypted`, verificado en MySQL).
- `/settings/captcha`: formulario simple, el secret nunca se re-muestra.
- **Se degrada con gracia**: si no está configurado, el formulario de oferta funciona
  sin captcha (no se rompe un install nuevo). Apenas se cargan las claves, el widget
  aparece en la landing y el backend **rechaza** la oferta si falta o es inválido el
  token (`Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', ...)`).
  Probado con claves inventadas: sin completar el captcha, la oferta se rechaza con
  "Please complete..." y no se crea el registro; con las claves cargadas el widget
  aparece con el `site_key` correcto en el HTML.

### Teléfono en el formulario de oferta

- Columna `phone` (nullable) en `domain_offers`. Opcional, igual que nombre y mensaje.
- Aparece en el formulario público, en la tabla de "Ofertas recibidas" del panel admin, y
  en el email de notificación (`OfferReceivedMail`). **No** aparece en la página pública
  de ofertas (ver más abajo) — ahí solo se muestra nombre enmascarado y monto.

### SEO real (título/descripción por dominio + bug del `<head>`)

- Columnas `seo_title` / `seo_description` en `domains`, editables desde el form de
  dominio (sección nueva "SEO de la landing pública"). Si se dejan vacíos, la landing
  usa un texto genérico de respaldo.
- **Bug encontrado en el camino**: `commonMaster.blade.php` (el `<head>` compartido de
  TODA la app) tenía `<meta name="robots" content="noindex, nofollow">` fijo, y el
  `<title>` siempre le pegaba "| Domain Manager - ..." al final, sin manera de
  overridearlo. Nada de esto importaba mientras el marketplace no existía, pero una vez
  que sí existe, esto significaba que **ninguna página de venta iba a ser indexada por
  Google jamás**, y el título mostraría la marca interna del panel admin a un comprador
  real. Se agregaron tres secciones opcionales (`@hasSection('full-title')`,
  `meta-description`, `robots`, `canonical`) que, si la vista las define, reemplazan el
  comportamiento por defecto — las páginas del panel admin (que no las definen) quedan
  exactamente igual que antes. Verificado: la landing ahora tiene
  `<title>{{ nombre del dominio o seo_title }}</title>` limpio (sin "Domain Manager"),
  `robots: index, follow`, y `canonical` apuntando al propio dominio.

### Sitio multi-página del marketplace con sidebar

Antes había una sola página (`/`). Ahora cada dominio en venta tiene 3, todas bajo el
mismo layout nuevo `layouts/marketplaceLayout.blade.php` (una barra lateral angosta con
ícono + inicial del dominio como "logo", y 3 links con ícono: Home/Metrics/Offers):

- **`/`** — la landing con el formulario de oferta (ya existía, se le agregó el
  sidebar).
- **`/metrics`** — página pública nueva con el total de visitas, visitas del mes, y el
  mismo gráfico de área de los últimos 30 días que ve el admin (Milestone 10), pero
  reutilizado acá como contenido de cara al comprador (varios marketplaces reales
  muestran tráfico como prueba social).
- **`/offers`** — página pública nueva con la lista de ofertas recibidas. **Sin login**,
  así que se decidió junto con el usuario qué tan detallada podía ser (ver pregunta
  respondida abajo): nombre enmascarado (`DomainOffer::masked_name`: primer nombre
  completo + inicial del apellido, ej. "Juan Torres" → "Juan T.") y monto, **sin
  email, sin teléfono, sin estado de la oferta**. Se excluyó el estado (pendiente/
  aceptada/rechazada) a propósito: mostrarlo le daría a cualquier visitante información
  de negociación del vendedor (qué ofertas rechazó y por qué), algo que marketplaces
  reales como Sedo o Afternic no exponen.
- `ResolveMarketplaceHost` (Milestone 8) ahora despacha por método+path:
  `GET /` → landing, `GET /metrics` → métricas, `GET /offers` → lista pública,
  `POST /offers` → enviar oferta (sin colisión, son métodos HTTP distintos sobre el
  mismo path).

Todo probado con curl (`-H "Host: mitienda.test"`, `-A` con user-agent de navegador real
para no activar la exclusión de bots): las 3 páginas cargan, el sidebar muestra la
inicial correcta ("M"), oferta con teléfono creada y verificada en BD/panel/email,
nombre correctamente enmascarado en `/offers` sin rastro de email/teléfono/nombre
completo. BD reseteada al terminar — **se recreó `mitienda.test`** porque el usuario ya
la tenía configurada en su `/etc/hosts` y navegador para probar visualmente.

### Bug encontrado por el usuario probando en el navegador: links del sidebar rotos

El sidebar y el `action` del formulario usaban rutas absolutas hardcodeadas
(`href="/metrics"`, `href="/offers"`, `action="/offers"`). Mis pruebas con `curl` no lo
detectaron porque `curl -H "Host: ..." http://localhost/domain/public/...` arma la URL a
mano en cada request — nunca sigue un link generado por la página anterior. En un
navegador real, un link `href="/metrics"` navega a la **raíz del host**
(`http://mitienda.test/metrics`), no a donde vive la app en este entorno local
(`http://mitienda.test/domain/public/metrics`) — Apache devolvía su propio 404 (ni
siquiera llegaba a Laravel) porque `metrics` no existe como carpeta en el htdocs
compartido de XAMPP.

En producción esto no pasaría (un dominio real apunta directo a la raíz de la app, sin
subdirectorio), pero acá sí. Se corrigió calculando la base real de cada request:

```php
// MarketplaceLandingController::siteRoot()
rtrim($request->getSchemeAndHttpHost() . $request->getBaseUrl(), '/')
```

`getBaseUrl()` es de Symfony, ya resuelve exactamente este caso (devuelve
`/domain/public` acá, y devuelve cadena vacía en un dominio real servido desde la raíz)
— no hubo que hardcodear nada específico de este entorno. Se pasa `siteRoot` a las 3
vistas y se usa para el sidebar y el `action` del form en vez de rutas absolutas sueltas.
Confirmado con curl seguido de una verificación manual de que los `href` generados en el
HTML apuntan exactamente a donde debían (con el host correcto del dominio visitado, no
el del panel admin).

### Ajuste de UX pedido por el usuario: la landing no debe scrollear nunca

El hero (nombre del dominio + métricas) más el formulario completo al lado (nombre,
email, teléfono, monto, mensaje, recaptcha) no entraban siempre en una pantalla y hacían
scrollear la página completa — el usuario no lo quería así. Se resolvió sacando el
formulario del hero: ahora el hero es solo nombre + métricas + un botón "Make an offer",
y el formulario vive en un modal de Bootstrap (mismo componente que ya se usaba para
"Cambiar estado" y categorías en el panel admin).

- `html, body { overflow: hidden; height: 100% }` + `.dm-hero { height: 100vh; overflow:
  hidden }` en el `page-style` de esta vista únicamente (no afecta a `/metrics` ni
  `/offers`, que si necesitan poder scrollear con listas largas).
- El modal (`.modal-dialog-scrollable`) scrollea internamente si el contenido no entra
  (por ejemplo con el widget de reCAPTCHA visible) — el body de la página nunca se
  mueve, que es exactamente el comportamiento nativo de un modal de Bootstrap (bloquea
  el scroll de fondo mientras está abierto).
- Si el servidor devuelve la vista con errores de validación o con la oferta ya enviada
  (`$offerSubmitted`), un pequeño script abre el modal automáticamente al cargar la
  página (`$shouldOpenOfferModal = $offerSubmitted || count($formErrors) > 0`), para que
  el resultado no quede escondido detrás de un modal cerrado.

Se detectó en las pruebas que el usuario ya había cargado sus claves reales de
reCAPTCHA en `/settings/captcha` (no las de prueba que yo había puesto) — por lo que las
pruebas por curl del envío ya no pueden completar el captcha de verdad (es la conducta
correcta y esperada). No se tocó esa configuración real para no perderla.

## Pendiente de decidir antes de Milestone 8 (no bloquea Fases anteriores)

Ver `03-fase-2-marketplace.md §6` — dominio canónico propio, "Buy Now" vs. solo ofertas,
analytics propio vs. herramienta externa, estrategia de certificados SSL por dominio en
producción (`04-arquitectura-multidominio.md §3`).

## Importación de la cartera real (2026-07-27)

El usuario tenía sus dominios reales trackeados en Notion (`notion/` en la raíz del
repo: un CSV + un `.md` por dominio con contexto de "por qué lo compraste" e historial
de renovación). Se importaron los 40 dominios reales, reemplazando los 8 de
Faker/seeder (se conservó `mitienda.test`, el dominio de prueba del marketplace que el
usuario ya tenía configurado en su navegador).

- **Campo nuevo**: `priority` (Baja/Media/Alta) en `domains` — dato real que Notion sí
  trackeaba y no teníamos modelado. Enum `App\Enums\DomainPriority`, visible en
  listado/ficha/formulario.
- **Comando**: `php artisan domains:import-notion`
  (`app/Console/Commands/ImportNotionDomains.php`) — parsea el CSV, cruza cada fila con
  su `.md` correspondiente (busca por el título `# DOMINIO.COM` dentro del archivo, no
  por nombre de archivo), y por cada dominio nuevo crea: el registro `Domain`, un log
  `system` de importación, y un log `note` con el texto de "POR QUE COMPRASTE ESTE
  DOMINIO" fechado en la fecha de compra real (no en la fecha de hoy — el primer intento
  tenía ese bug: `created_at` no es mass-assignable en el modelo `DomainLog`, Eloquent lo
  pisaba con la fecha actual; se corrigió con `forceFill(...)->save()` después de crear).
  Es idempotente (`updateOrCreate` por nombre) — correrlo de nuevo no duplica nada.
- **Mapeo de estados** (decisión, no había una opción exacta para cada caso de Notion):
  - `ESTADO=Activo` → `watching`, salvo que `ADS=En Venta` → ahí pasa a `for_sale` +
    `is_for_sale=true` (dos dominios: `demandium.com` y `ridergo.co`).
  - `ESTADO=Vencido` → `expired`.
  - `ESTADO=Por Comprar` (`gotrip.com`) y `Sin Verificar` (`qaova.om`) → no existe un
    estado equivalente en nuestro enum (asume que todo lo que está en `domains` ya es
    tuyo), así que se importaron como `watching` con una nota automática
    ("PENDIENTE DE COMPRA" / "SIN VERIFICAR") para que quede visible que hay que
    confirmar su estado real a mano.
  - Fecha de vencimiento faltante (mismos dos casos) → se asumió compra + 1 año como
    placeholder, no es un dato confirmado.
- **Costo**: se parseó tanto `"10.000.000 COP"` (Mariachis.co, formato colombiano con
  punto de miles) como `"30USD"` (Mercatinos.com) correctamente a monto + moneda de 3
  letras. Sin valor → `0 USD`.
- **`MARIACHIS.CO`** es el único con historial de renovación real en su `.md` (sección
  "RENOVACIONES"): se agregó un log tipo `renewal` a mano con esos datos exactos (no se
  generalizó un parser para un caso único).
- No se crearon `projects` ni se enlazó ningún dominio a un proyecto automáticamente,
  aunque varias notas mencionan para qué empresa/proyecto se compró cada uno (ej. "GIGI",
  "Mariachis Marketplace", "Handyman") — graduarlos a un `Project` real es una decisión
  de negocio que le corresponde al usuario hacer manualmente desde el panel
  (`Cambiar estado → Proyecto activo`), no algo para inferir automáticamente.
- Tampoco se crearon `domain_categories` nuevas ni se asignó ninguna — se dejaron las 3
  de ejemplo (Tech/Música/Finanzas) disponibles para que el usuario categorice a mano si
  quiere.

Verificado con curl (login real): dashboard muestra 41 dominios / 3 en venta
correctamente, el listado pagina bien con la columna de prioridad, y el detalle de
`mariachis.co` muestra tanto la nota real como el log de renovación con sus fechas
correctas (no la fecha de hoy). El comando de importación y la carpeta `notion/` con los
archivos fuente quedan en el repo por si hace falta re-importar o consultar el original;
se pueden borrar sin problema una vez que los datos ya estén validados en el panel.

## Corrección de paginación (2026-07-27)

El usuario reportó la paginación del listado de dominios "rota". Causa: Laravel usa
vistas de paginación con clases de **Tailwind CSS** por defecto
(`resources/views/vendor/pagination`), pero este theme es 100% Bootstrap — nunca se
cargó Tailwind en ningún lado, así que los links de paginación se renderizaban con
clases que no hacían nada (visualmente rotos/sin estilo). Fix de una línea en
`AppServiceProvider::boot()`: `Paginator::useBootstrapFive()`, que activa la vista de
paginación Bootstrap 5 que Laravel ya trae integrada. Es un fix global — corrige
`/domains` y cualquier otro listado paginado del proyecto (`/projects` en cuanto tenga
más de 15 registros), no hubo que tocar cada vista individualmente.

## Rediseño del flujo de renovación (2026-07-27)

El usuario hizo una observación de fondo sobre el modelo: al comprar un dominio no sabés
todavía cuándo ni cuánto vas a pagar por renovarlo el año siguiente (el precio puede
subir, bajar, o podés transferirlo a otro proveedor) — así que pedir "fecha de
renovación" y "costo de renovación" en el mismo formulario de alta/edición no tenía
sentido; eso debería ser una **acción propia** que ocurre cuando el dominio está por
vencer, no un dato que se inventa al crear el registro.

- Se sacaron `renewal_date` y `renewal_cost` del formulario de alta/edición
  (`_form.blade.php`) y de su validación — ya no son campos editables de forma libre.
- Nueva acción **"Renovar dominio"** (botón en `domains/show.blade.php`, coloreado según
  urgencia igual que el badge de vencimiento — verde/amarillo/rojo según
  `days_until_expiration`) que abre un modal con: registrador (pre-cargado con el
  actual, pero editable — así se cubre tanto "renovar con el mismo proveedor" como
  "transferir a otro y renovar ahí"), nueva fecha de vencimiento (sugerida
  automáticamente como +1 año sobre la actual, editable), costo de esta renovación y
  moneda.
- `DomainController::renew()` (ruta `POST /domains/{domain}/renew`, nombre
  `domains.renew`): valida que la nueva fecha sea posterior a la fecha de vencimiento
  actual (no a "hoy" — así permite corregir/registrar una renovación de un dominio que
  ya estaba vencido), actualiza `registrar`/`expiration_date`/`renewal_date` (pasa a ser
  "fecha de la última renovación", ya no "próxima renovación programada")/`renewal_cost`/
  `currency`, y si el dominio estaba `expired` o `pending_renewal` lo vuelve a
  `watching` automáticamente. Crea un log tipo `renewal` con el historial completo en
  `meta` (registrador y vencimiento anterior/nuevo, costo) — si el registrador cambió,
  la descripción lo aclara explícitamente ("transferido de X a Y").
- Ficha del dominio: "Fecha de renovación" pasó a llamarse **"Última renovación"** (o
  "Nunca renovado" si `renewal_date` es null) y muestra el costo de esa renovación al
  lado.
- No se tocó el esquema (no se eliminó ninguna columna) — se reinterpretó
  `renewal_date`/`renewal_cost` de "dato manual al crear" a "resultado de la última vez
  que se ejecutó la acción Renovar", evitando una migración destructiva.

Probado end-to-end sobre dominios reales importados de Notion: `askqo.com` (vencido,
sin registrador) renovado con GoDaddy → pasó automáticamente de `expired` a `watching`,
log correcto; `tecwi.co` (Namecheap) renovado transfiriéndolo a Cloudflare → log dice
"transferido de Namecheap a Cloudflare"; intento de renovar con una fecha anterior a la
actual → rechazado por validación, no se guardó nada. Ambos dominios se restauraron a su
estado real importado después de las pruebas (se habían quedado con datos de prueba
tipo GoDaddy/Cloudflare a $15.99/$9.99 que no son reales).
