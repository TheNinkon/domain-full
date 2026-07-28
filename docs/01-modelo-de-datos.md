# Modelo de Datos

Todas las tablas usan `id` bigint autoincrement, `created_at`/`updated_at`, y `deleted_at`
(soft delete) salvo que se indique lo contrario. Nombres en inglés (convención Laravel),
descripciones en español.

## 1. `users` (ya existe, se extiende)

| Campo | Tipo | Notas |
|---|---|---|
| `role` | enum/string (`admin`, `staff`) | Default `admin`. MVP: columna simple; si más adelante se necesitan permisos granulares, migrar a `spatie/laravel-permission` sin romper esta columna. |

## 2. `domain_categories`

Categorías libres definidas por el propio admin (ej: "Música", "Tech", "Finanzas", "Brandable", "Geo").

| Campo | Tipo | Notas |
|---|---|---|
| `name` | string, unique | |
| `color` | string, nullable | hex, para badges en la UI del theme |

## 3. `domains` — tabla central

| Campo | Tipo | Notas |
|---|---|---|
| `name` | string, unique | El dominio, ej. `musicos.com` (sin protocolo, sin `www`) |
| `domain_category_id` | FK nullable → `domain_categories` | |
| `registrar` | string, nullable | Namecheap, GoDaddy, etc. |
| `status` | enum | `watching` (solo cartera), `active_project`, `for_sale`, `sold`, `expired`, `pending_renewal` — ver §3.1 |
| `purchase_date` | date | Fecha de compra |
| `renewal_date` | date, nullable | Próxima fecha de renovación programada |
| `expiration_date` | date | Fecha de vencimiento real ante el registrador |
| `purchase_cost` | decimal(10,2) | Costo inicial de adquisición |
| `renewal_cost` | decimal(10,2), nullable | Costo estimado/real de renovación (puede variar del inicial) |
| `currency` | string(3), default `USD` | Por si se compran dominios en distintas monedas |
| `notes` | text, nullable | Notas libres (aparte de la bitácora, para "resumen actual") |
| `project_id` | FK nullable → `projects` | Si el dominio "se graduó" a proyecto/empresa, se linkea aquí |
| `is_for_sale` | boolean, default `false` | Controla si la landing pública (Fase 2) se activa para este dominio |
| `auto_renew` | boolean, default `true` | Informativo, no automatiza nada en Fase 1 |

### 3.1 Estados (`status`) — máquina de estados simple

```
watching        -> el dominio está en cartera, sin uso activo (default al comprar)
active_project  -> se decidió usar para un proyecto/empresa (requiere project_id)
for_sale        -> publicado en el marketplace (Fase 2)
sold            -> vendido, se conserva el registro histórico
pending_renewal -> a N días de vencer (ver alertas en Fase 1, puede ser calculado o manual)
expired         -> venció y no se renovó
```

El estado es manual (el admin lo cambia), salvo `pending_renewal`/`expired` que también
pueden calcularse en la UI a partir de `expiration_date` para alertas, sin necesariamente
persistirlo.

## 4. `projects` — "en qué se convirtió" el dominio

Representa el proyecto o empresa al que un dominio pasó a pertenecer cuando deja de ser
solo un activo en cartera.

| Campo | Tipo | Notas |
|---|---|---|
| `name` | string | Nombre del proyecto o empresa |
| `description` | text, nullable | |
| `url` | string, nullable | Si el proyecto ya tiene su propio sitio activo |
| `status` | enum (`idea`, `in_progress`, `launched`, `paused`) | |

Relación: `domains.project_id` → `projects.id` (un proyecto puede tener 1+ dominios,
ej. dominio principal + variantes/redirects).

## 5. `domain_logs` — bitácora / seguimiento histórico

El "backlog" mencionado: timeline de todo lo que pasa con un dominio desde que entra a
la cartera.

| Campo | Tipo | Notas |
|---|---|---|
| `domain_id` | FK → `domains`, cascade on delete | |
| `user_id` | FK nullable → `users` | Quién hizo la entrada |
| `type` | enum | `note`, `status_change`, `renewal`, `price_change`, `offer_received`, `sale`, `system` |
| `description` | text | Texto libre del evento |
| `meta` | json, nullable | Datos estructurados del evento (ej: `{"from": "watching", "to": "active_project"}`) |

Sin `updated_at` relevante (es un log append-only); sí lleva `created_at`.

## 6. `domain_offers` — ofertas recibidas (Fase 2)

| Campo | Tipo | Notas |
|---|---|---|
| `domain_id` | FK → `domains` | |
| `name` | string, nullable | Nombre de quien ofrece (puede ser anónimo/opcional) |
| `email` | string | Para poder responder |
| `amount` | decimal(10,2) | |
| `currency` | string(3), default `USD` | |
| `message` | text, nullable | |
| `status` | enum (`pending`, `accepted`, `rejected`, `expired`) | default `pending` |
| `ip_address` | string, nullable | Anti-spam / auditoría básica |

Al recibir una oferta se crea también un `domain_logs` con `type = offer_received`.

## 7. `domain_daily_stats` — métricas de tráfico (Fase 2)

Agregado diario simple; evita guardar cada page-view individual desde el día 1 (se
puede reemplazar por una integración de analytics real más adelante sin tocar el resto
del modelo).

| Campo | Tipo | Notas |
|---|---|---|
| `domain_id` | FK → `domains` | |
| `date` | date | |
| `visits` | unsigned int, default 0 | |
| `unique_visitors` | unsigned int, default 0 | Opcional, vía cookie/hash de IP+UA |

Unique index en (`domain_id`, `date`).

## 8. Diagrama de relaciones (resumen)

```
domain_categories 1───* domains *───1 projects
domains 1───* domain_logs
domains 1───* domain_offers
domains 1───* domain_daily_stats
users   1───* domain_logs (autor)
```

## 9. Campos derivados que la UI necesita (no se persisten)

- **Días para vencer**: `expiration_date - hoy`. Usado para badges de alerta
  (rojo <15 días, amarillo <30 días, verde resto) y para el dashboard.
- **Oferta promedio de un dominio**: `AVG(domain_offers.amount)` filtrando por
  `status != rejected`. Se muestra en la landing pública y en el panel admin.
- **Total invertido en cartera**: `SUM(purchase_cost) + SUM(renewal_cost histórico)` —
  este segundo sumando requiere que cada renovación quede registrada como un
  `domain_logs` de tipo `renewal` con el costo en `meta`, o una tabla `domain_renewals`
  si se quiere detalle año a año (evaluar en Fase 1 si el nivel de detalle actual basta).
