# Domain Manager — Visión y Alcance del Proyecto

## 1. Qué es este proyecto

Un sistema privado para gestionar una cartera de dominios propios (compra, seguimiento,
vencimientos, notas, categorización) que, en una segunda fase, se convierte en una
plataforma pública de venta de esos dominios (marketplace tipo "domain for sale landing").

Construido sobre el theme **Vuexy HTML Admin Template** (Pixinvent) en su versión
**Laravel 12** ya integrada en este repo (`/Applications/XAMPP/xamppfiles/htdocs/domain`).
El theme aporta el panel administrativo (layout, componentes, formularios, tablas,
dashboard, autenticación visual); nosotros construimos la lógica de negocio encima.

Referencia del theme: https://demos.pixinvent.com/vuexy-html-admin-template/documentation/laravel-introduction.html

## 2. Motivación / problema a resolver

El propietario compra dominios de forma recurrente (inversión / especulación / proyectos
futuros) y hoy no tiene un registro centralizado de:

- Qué dominios tiene, cuándo vencen y cuánto cuesta renovarlos.
- Cuáles ya son "solo cartera" vs. cuáles se convirtieron en un proyecto real o una empresa.
- El historial/bitácora de decisiones sobre cada dominio (por qué se compró, en qué se
  pensó usarlo, cambios de estado, etc.).
- Qué dominios están en venta, qué ofertas ha recibido cada uno y qué tráfico generan.

## 3. Fases del proyecto

### Fase 1 — Panel privado de gestión de dominios (alcance de esta etapa)

Un CRUD robusto, **solo accesible para el/los administradores** (sin nada público),
para registrar y dar seguimiento a cada dominio de la cartera. Ver detalle completo en
[`02-fase-1-panel-privado.md`](./02-fase-1-panel-privado.md).

### Fase 2 — Marketplace público de venta de dominios

Cuando el admin marca un dominio como "en venta", ese dominio (apuntado por DNS al mismo
servidor) sirve una landing pública genérica en inglés, con información del dominio,
formulario de contacto/oferta y métricas sociales (visitas, oferta promedio, nº de
interesados). Ver detalle en [`03-fase-2-marketplace.md`](./03-fase-2-marketplace.md) y
la arquitectura multi-dominio en [`04-arquitectura-multidominio.md`](./04-arquitectura-multidominio.md).

## 4. Decisiones ya tomadas (2026-07-25)

| Decisión | Elección | Motivo |
|---|---|---|
| Base de datos | **MySQL** (vía XAMPP) | Entorno local ya trae MySQL/MariaDB; evita migrar desde SQLite más adelante para producción. |
| Autenticación | Auth real de Laravel + **roles** (`admin`, y espacio para roles futuros tipo `staff`) | Panel privado, pero se deja la puerta abierta a dar acceso a alguien más sin rehacer el sistema. |
| Arquitectura Fase 2 | **Una sola app Laravel, ruteo por `Host` header** | Todos los dominios comprados apuntan (DNS) al mismo servidor; la app resuelve qué landing servir según el dominio solicitado. Un solo despliegue escala a cientos de dominios. |
| Orden de trabajo | Documentar primero, implementar Fase 1 en una sesión posterior | Alinear alcance y modelo de datos antes de escribir código. |

## 5. Qué NO es este proyecto (por ahora)

- No es un registrador de dominios (no gestiona DNS, no compra/renueva automáticamente
  vía API de un registrador — al menos no en el alcance inicial).
- No es multi-cliente / multi-tenant para terceros; es una herramienta privada de un solo
  operador (con posibilidad de agregar staff interno).
- No incluye pasarela de pago en Fase 2 inicial: la venta se gestiona por contacto/oferta
  manual (como Afternic/Sedo en su modalidad "make an offer"), no checkout automático.

## 6. Stack técnico heredado del theme

- Laravel 12 / PHP 8.2
- Bootstrap 5 (vía el theme Vuexy), Vite para assets
- Menús dirigidos por JSON (`resources/menu/verticalMenu.json`, `horizontalMenu.json`)
- Configuración visual del theme en `config/custom.php`
- Estructura de vistas: `resources/views/content/<módulo>/...` + `resources/views/layouts`

Ver detalle técnico en [`05-stack-tecnico.md`](./05-stack-tecnico.md).

## 7. Documentos relacionados

- [`01-modelo-de-datos.md`](./01-modelo-de-datos.md) — entidades, campos, relaciones, migraciones planeadas.
- [`02-fase-1-panel-privado.md`](./02-fase-1-panel-privado.md) — features detalladas de Fase 1.
- [`03-fase-2-marketplace.md`](./03-fase-2-marketplace.md) — features detalladas de Fase 2.
- [`04-arquitectura-multidominio.md`](./04-arquitectura-multidominio.md) — cómo se sirve una landing por dominio.
- [`05-stack-tecnico.md`](./05-stack-tecnico.md) — decisiones técnicas y convenciones del repo.
- [`06-roadmap.md`](./06-roadmap.md) — backlog ordenado por milestones, listo para ejecutar.
