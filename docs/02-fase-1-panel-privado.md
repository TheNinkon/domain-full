# Fase 1 — Panel Privado de Gestión de Dominios

Alcance: 100% detrás de login, sin nada público. Objetivo: reemplazar la hoja de cálculo
mental/manual por un panel real dentro del theme Vuexy ya instalado.

## 1. Autenticación

- Reutilizar las vistas `auth-login-basic` / `auth-register-basic` del theme, pero:
  - Cablear `LoginBasic`/`RegisterBasic` a la autenticación real de Laravel
    (`Auth::attempt`, sesión, `RememberMe`, throttle de intentos).
  - **Deshabilitar el registro público** (o dejarlo solo accesible manualmente por el
    admin/seed) — este panel no es de alta libre.
  - Middleware `auth` en todas las rutas del módulo de dominios.
  - Columna `role` en `users` (`admin` por defecto); rutas de administración de usuarios
    quedan reservadas a `role = admin` (pensado para cuando se sume un `staff`).

## 2. Módulo de Categorías

CRUD simple (probablemente un modal, no una página completa) para `domain_categories`:
crear, renombrar, asignar color, eliminar (si no tiene dominios asociados).

## 3. Módulo de Dominios (núcleo del sistema)

### 3.1 Listado (`/domains`)

Tabla (usar el componente de datatable que ya trae el theme) con:

- Columnas: nombre del dominio, categoría (badge de color), estado (badge), fecha de
  vencimiento (con color según urgencia), costo de renovación, proyecto vinculado (si
  aplica).
- Filtros: por estado, por categoría, por rango de fecha de vencimiento, búsqueda por
  nombre.
- Orden por defecto: fecha de vencimiento ascendente (lo más urgente arriba).
- Acciones rápidas por fila: ver detalle, editar, cambiar estado, eliminar (soft delete).

### 3.2 Alta de dominio (`/domains/create`)

Formulario con: nombre, categoría, registrar, fecha de compra, fecha de vencimiento,
fecha de renovación (opcional, puede ser igual a vencimiento), costo inicial, costo de
renovación, moneda, notas iniciales, estado inicial (default `watching`).

Al guardar: se crea el `domains` + un `domain_logs` automático tipo `system`
("Dominio agregado a la cartera").

### 3.3 Detalle de dominio (`/domains/{id}`)

Vista central del proyecto — debe combinar:

- **Ficha de datos**: todos los campos editables inline o vía modal.
- **Bitácora / timeline** (`domain_logs`): lista cronológica de notas, cambios de estado,
  renovaciones, precios. Con un input rápido para "agregar nota" que crea un
  `domain_logs` tipo `note`.
- **Cambiar estado**: acción explícita (no solo editar el campo) que, si el nuevo estado
  es `active_project`, obliga a seleccionar o crear un `project` y lo linkea.
- Placeholder de la sección de Fase 2 (ofertas/métricas) — oculta o deshabilitada hasta
  que el dominio esté `for_sale`.

### 3.4 Edición y borrado

- Editar reabre el mismo formulario de alta con los valores actuales; cualquier cambio en
  `purchase_cost`/`renewal_cost`/fechas genera una entrada `domain_logs` tipo
  `price_change` o `renewal` con el valor anterior y nuevo en `meta`.
- Borrado = soft delete (se conserva el histórico; nunca se pierde información de un
  dominio que se dejó vencer o se vendió).

## 4. Módulo de Proyectos/Empresas

CRUD simple de `projects`: nombre, descripción, url, estado. Vista de detalle muestra
qué dominio(s) están vinculados a ese proyecto.

## 5. Dashboard (página principal del panel)

Reemplaza la `pages-home` demo del theme. Widgets sugeridos (usando los componentes de
stats/cards que ya trae Vuexy):

- Total de dominios activos en cartera.
- Dominios por vencer en los próximos 30 / 15 / 7 días (con link directo al filtro).
- Costo total invertido (compra + renovaciones históricas).
- Distribución por estado (gráfico donut: watching / active_project / for_sale / sold).
- Distribución por categoría.
- Últimos movimientos de la bitácora (feed global, últimas N entradas de todos los
  dominios).

## 6. Notificaciones de vencimiento (dentro del alcance de Fase 1, prioridad media)

- Cálculo en cada carga del dashboard/listado (no requiere cron todavía).
- Opcional/mejora incremental: comando `artisan domains:check-expirations` +
  scheduler diario que mande un email/resumen si hay dominios a ≤7 días de vencer.
  Se puede dejar para una iteración posterior sin bloquear el resto de Fase 1.

## 7. Fuera de alcance explícito en Fase 1

- Todo lo público (landing, ofertas, métricas de tráfico) → Fase 2.
- Integración con APIs de registradores (WHOIS, renovación automática, compra).
- Roles granulares / permisos por acción (solo `admin` vs `staff` a nivel de columna).

## 8. Checklist funcional de aceptación de Fase 1

- [ ] Puedo iniciar sesión con un usuario admin real (no demo).
- [ ] Puedo crear, listar, filtrar, editar y eliminar (soft delete) un dominio.
- [ ] Cada dominio tiene su bitácora visible y puedo agregar notas manuales.
- [ ] Cambiar el estado a "active_project" me obliga a vincular un proyecto/empresa.
- [ ] El dashboard muestra correctamente los dominios próximos a vencer y el total
      invertido.
- [ ] Toda la UI usa los componentes/estilo del theme Vuexy (no HTML suelto sin el
      layout).
