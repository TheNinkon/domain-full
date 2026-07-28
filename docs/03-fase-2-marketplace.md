# Fase 2 — Marketplace Público de Venta de Dominios

Alcance: cuando el admin activa `is_for_sale = true` en un dominio (Fase 1), ese dominio
—apuntado por DNS al mismo servidor— sirve una landing pública en inglés, reutilizando
una única plantilla para los N dominios en venta.

## 1. Experiencia del visitante (cliente potencial)

Un visitante entra a, por ejemplo, `musicos.com` y ve una landing (una sola plantilla
Blade reutilizada, alimentada con los datos de `domains` + agregados de
`domain_offers`/`domain_daily_stats`) con:

- Nombre del dominio como título ("musicos.com is for sale").
- Copy genérico de venta (texto template en inglés, no requiere redacción por dominio).
- **Prueba social / métricas**:
  - Oferta promedio recibida (rango, ej. "offers between $1,000–$5,000").
  - Número de ofertas recibidas hasta la fecha.
  - Visitas al día / mes (o simplemente "X people viewed this domain this month").
- **Formulario de contacto / oferta**: nombre, email, monto ofertado, mensaje opcional.
- Sin checkout ni pago en línea en esta iteración — la venta se cierra manualmente
  (email, transferencia, marketplace externo) una vez hay una oferta aceptable.

## 2. Qué pasa al enviar una oferta

1. Se valida el formulario (rate limiting básico para evitar spam/bots).
2. Se crea un registro en `domain_offers` (`status = pending`).
3. Se crea un `domain_logs` (`type = offer_received`) para que quede en la bitácora del
   dominio dentro del panel admin.
4. Se notifica al admin (email, usando el `MAIL_MAILER` configurado).
5. El admin, desde el panel privado (Fase 1 + esta extensión), puede marcar la oferta
   como `accepted`/`rejected` — si se acepta, sugiere (no fuerza) pasar el dominio a
   estado `sold`.

## 3. Tracking de visitas

- Registro simple de "page view" por request a la landing pública (excluyendo bots
  conocidos por user-agent y al propio admin autenticado).
- Se agrega a `domain_daily_stats` (incrementa `visits` del día actual; opcionalmente
  `unique_visitors` vía hash de IP+User-Agent con expiración diaria, sin usar cookies
  invasivas).
- Nivel de detalle intencionalmente simple para MVP; si más adelante se necesita algo más
  serio, se reemplaza por una integración de analytics (Plausible/Umami/GA) sin tocar el
  modelo de datos del resto del sistema.

## 4. Vista de métricas en el panel admin

Extensión de la vista de detalle de dominio (`/domains/{id}`) del panel privado: cuando
`is_for_sale = true`, se habilita una pestaña/sección con:

- Gráfico de visitas (últimos 30 días).
- Lista de ofertas recibidas con su estado.
- Botón para aceptar/rechazar oferta.

## 5. Idioma

- Landing pública: **solo inglés** (según decisión del alcance — suficiente para el
  público objetivo de compradores de dominios).
- Panel administrativo: idioma que ya maneje el theme (el proyecto trae soporte de
  locales vía `LanguageController`, evaluar si se deja en español o inglés para el admin,
  es independiente del idioma de la landing pública).

## 6. Consideraciones que quedan abiertas (a decidir cuando se aborde esta fase)

- ¿La landing debe tener un dominio "canónico" propio (ej. `tuplataforma.com/musicos-com`)
  además de responder en el dominio real, para poder compartir el link antes de que el
  DNS esté propagado?
- ¿Se permite listar precio fijo ("Buy Now") además de "Make an offer", o solo ofertas?
- ¿Analytics propio (tabla `domain_daily_stats`) es suficiente o se prefiere integrar
  una herramienta externa desde el día 1 de esta fase?

Estas preguntas no bloquean Fase 1; se resuelven al iniciar el trabajo de Fase 2.

Ver la mecánica técnica de "cómo un mismo servidor sirve landings distintas según el
dominio solicitado" en [`04-arquitectura-multidominio.md`](./04-arquitectura-multidominio.md).
