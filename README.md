# Domain Manager

A private Laravel admin panel for tracking a personal/business domain portfolio —
purchase and renewal dates, costs, categories, and priority — that also doubles as a
public, multi-domain marketplace: any domain marked "for sale" automatically serves
its own landing page (offer form, visit stats, SEO meta) on its real hostname, with
zero per-domain deployment.

Built on Laravel 12 and the [Vuexy](https://pixinvent.com/demo/vuexy-html-laravel-admin-dashboard-template/) Bootstrap 5 admin theme.

## What it does

**Private admin panel** (behind login)
- CRUD for domains, categories, and projects, with expiration alerts and a renewal
  flow (renew with the same registrar or transfer + renew, tracking price changes
  over time instead of guessing a renewal date at purchase time).
- Dashboard with portfolio-wide stats (upcoming renewals, spend, status breakdown).
- SMTP and Google reCAPTCHA v2 configured from within the admin UI — no `.env`
  redeploys needed to turn email or spam protection on.

**Public marketplace** (Phase 2, no login)
- Any domain flagged `is_for_sale` in the admin resolves — by its real `Host` header,
  no route/domain hardcoding — straight to a public landing page for that domain:
  hero, live visit/offer stats, and an offer form (name, email, phone, amount,
  message) protected by reCAPTCHA and a honeypot field.
- Per-domain SEO title/description, visit tracking, and an offers dashboard (masked
  names, amounts, dates) reachable from the admin's per-domain sidebar.
- Fully responsive: bottom app-style navigation on mobile instead of a collapsed
  sidebar.

## Stack

- **Backend**: Laravel 12, PHP 8.2
- **Frontend**: Vuexy (Bootstrap 5) theme, Vite, Blade
- **Database**: MySQL
- **Auth**: Laravel's built-in auth scaffolding (Vuexy's login UI)

## Documentation

The full project story — scope, data model, architecture decisions, and a
milestone-by-milestone build log with every bug found and fixed along the way —
lives in [`docs/`](docs/):

| File | Contents |
|---|---|
| [`00-vision-y-alcance.md`](docs/00-vision-y-alcance.md) | Project vision and scope |
| [`01-modelo-de-datos.md`](docs/01-modelo-de-datos.md) | Data model / schema |
| [`02-fase-1-panel-privado.md`](docs/02-fase-1-panel-privado.md) | Phase 1 — private admin panel |
| [`03-fase-2-marketplace.md`](docs/03-fase-2-marketplace.md) | Phase 2 — public marketplace |
| [`04-arquitectura-multidominio.md`](docs/04-arquitectura-multidominio.md) | Multi-domain host-based routing architecture |
| [`05-stack-tecnico.md`](docs/05-stack-tecnico.md) | Tech stack and repo conventions |
| [`06-roadmap.md`](docs/06-roadmap.md) | Full milestone-by-milestone build history |
| [`07-despliegue-produccion.md`](docs/07-despliegue-produccion.md) | Production deployment guide (cPanel) |

Docs are in Spanish (the language this project was built in); this README is in
English to match GitHub convention.

## Local setup

```bash
composer install
cp .env.example .env
php artisan key:generate

# Configure DB_* in .env, then:
php artisan migrate --seed   # seeds an admin user — see ADMIN_* in .env.example

npm install   # or yarn
npm run dev
```

Visit the app and log in with the `ADMIN_EMAIL` / `ADMIN_PASSWORD` from your `.env`.

To test the marketplace locally, mark a domain `is_for_sale` in the admin and point
a local hostname (e.g. via `/etc/hosts`) at your dev server — see
[`04-arquitectura-multidominio.md`](docs/04-arquitectura-multidominio.md) for how
host resolution works.

## Deploying to production

See [`docs/07-despliegue-produccion.md`](docs/07-despliegue-produccion.md) for the
full cPanel deployment guide, including multi-domain/addon-domain setup, SSL, and
the cron job the expiration-alert scheduler needs.

## License

Private project. The Vuexy theme itself is a commercial [ThemeForest](https://themeforest.net/)
license and is **not** relicensed by this repository — this repo documents and
extends an application built on top of it for personal use.
