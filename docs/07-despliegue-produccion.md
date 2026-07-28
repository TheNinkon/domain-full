# Despliegue a Producción (cPanel)

Guía para publicar Domain Manager en un hosting cPanel con acceso Terminal/SSH.
Asume una sola cuenta cPanel sirviendo: (1) el panel admin privado en su propio
dominio/subdominio, y (2) N dominios "en venta" que deben resolver al **mismo**
código Laravel (ver [`04-arquitectura-multidominio.md`](04-arquitectura-multidominio.md)
— el ruteo es 100% por header `Host`, no hay un deploy por dominio).

## 1. Requisitos previos en cPanel

- PHP 8.2 seleccionado como versión del dominio (MultiPHP Manager). Verificar
  extensiones: `mbstring`, `openssl`, `pdo_mysql`, `tokenizer`, `xml`, `ctype`,
  `json`, `bcmath`, `fileinfo` (todas suelen venir activas en cPanel/LiteSpeed).
- Una base de datos MySQL + usuario con todos los privilegios sobre ella
  (creados desde "MySQL Databases").
- Acceso Terminal/SSH habilitado (ya confirmado).
- Composer disponible por SSH. Si no está instalado a nivel de sistema:
  ```bash
  curl -sS https://getcomposer.org/installer | php
  # genera composer.phar en el home del usuario; usar `php composer.phar ...`
  ```

## 2. Dominios: el panel admin + los dominios en venta

El middleware `ResolveMarketplaceHost` compara `Host` de cada request contra
`parse_url(config('app.url'), PHP_URL_HOST)`. Todo lo que **no** coincida se
trata como un posible dominio en venta y se sirve desde `MarketplaceLandingController`.
Esto implica, en cPanel:

1. **Dominio/subdominio del admin** (ej. `admin.tudominio.com` o un dominio
   dedicado): se configura como el dominio principal de la cuenta, o como
   subdominio, con su **document root apuntando a `public/`** del proyecto
   (no a la raíz del repo).
2. **Cada dominio en venta** (ej. `mitienda.test` → en producción,
   `mariachis.co`, etc.): se agrega en cPanel como **Addon Domain**, pero con
   su document root apuntando **a esa misma carpeta `public/`** — no a la
   carpeta que cPanel crea por defecto para el addon domain. Esto es lo que
   permite que los N dominios en venta compartan el mismo código sin
   duplicar el deploy. En "Addon Domains" al crear cada uno, edita la ruta
   de "Document Root" antes de guardar para que apunte al `public/` del
   proyecto principal.
3. Cada dominio (admin + cada uno en venta) necesita su propio certificado
   SSL. AutoSSL de cPanel cubre esto automáticamente para dominios y addon
   domains apuntados correctamente vía DNS — solo espera a que el DNS
   propague antes de forzar el reintento de AutoSSL.

Da igual si el dominio en venta ya tenía una landing previa en otro hosting:
el DNS debe apuntar (A record o nameservers) al servidor cPanel para que esto
funcione.

## 3. Subir el código

Por SSH, en el home del usuario cPanel (fuera de `public_html` si es posible,
o en una subcarpeta, según cómo se haya configurado el document root del
paso anterior):

```bash
git clone https://github.com/TheNinkon/domain-full.git domain-manager
cd domain-manager
```

Si el repo es privado en el futuro, usar un token de acceso personal o una
deploy key en vez de la URL pública.

## 4. Dependencias PHP

```bash
composer install --no-dev --optimize-autoloader
```

`--no-dev` evita instalar herramientas de desarrollo/test. `vendor/` nunca se
sube por git (está en `.gitignore`), así que este paso es obligatorio en el
servidor.

## 5. Assets del frontend (Vite/Vuexy)

`public/build/` está en `.gitignore` — no viaja con git. Dos opciones:

- **Recomendado**: compilar en tu máquina local (`npm run build`) y subir la
  carpeta `public/build/` resultante por SFTP/SCP al servidor.
- **Alternativa**: si el hosting permite "Setup Node.js App" en cPanel,
  instalar dependencias y correr `npm run build` directamente en el servidor.

Sin `public/build/` presente, las vistas Blade que usan `@vite(...)` fallarán.

## 6. Archivo `.env` de producción

Copiar `.env.example` como base y completar con valores reales:

```bash
cp .env.example .env
php artisan key:generate --force
```

Valores clave a revisar/cambiar respecto al ejemplo:

| Variable | Valor en producción |
|---|---|
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` (**crítico** — con `true` se filtran stack traces y rutas del servidor a cualquier visitante, incluidos los de las landings públicas) |
| `APP_URL` | URL exacta del dominio del **admin** (ej. `https://admin.tudominio.com`), sin `/public` al final. Este valor es la clave que usa `ResolveMarketplaceHost` para distinguir "es el admin" de "es un dominio en venta" — si está mal, el panel admin se serviría como landing pública. |
| `DB_*` | credenciales reales de la base de datos MySQL creada en el paso 1 |
| `ADMIN_EMAIL` / `ADMIN_PASSWORD` | solo se usan si corres el seeder (`db:seed`) por primera vez; cambia `ADMIN_PASSWORD` a algo fuerte y único antes de sembrar, y cambia la contraseña real desde el panel después del primer login |
| `SESSION_DOMAIN` | dejar `null` salvo que necesites compartir sesión entre subdominios del admin |
| `MAIL_MAILER` | puede quedar en `log`: el SMTP real se configura desde el propio admin (Settings → Mail), no por `.env` — ver [`06-roadmap.md`](06-roadmap.md) |

No se necesita configurar `MAIL_*` real ni las keys de reCAPTCHA en `.env`:
ambas se guardan cifradas en la base de datos y se configuran desde la UI del
admin después del primer deploy.

## 7. Base de datos

```bash
php artisan migrate --force
```

`--force` es obligatorio porque `APP_ENV=production` bloquea migraciones
interactivas por seguridad. Solo corre `--seed` si es la primera vez y ya
cambiaste `ADMIN_PASSWORD` en `.env` a algo que no sea el valor de ejemplo.

## 8. Cachés de producción

Después de que `.env` esté completo y correcto (repetir cada vez que cambie
`.env` o las rutas):

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Si algo falla de forma rara tras un deploy, lo primero a probar es
`php artisan config:clear && php artisan route:clear && php artisan view:clear`
y volver a cachear.

## 9. Permisos

```bash
chmod -R 775 storage bootstrap/cache
```

En cPanel el usuario del proceso PHP suele ser el mismo dueño de los
archivos subidos por SSH/git, así que normalmente no hace falta tocar
usuario/grupo — solo asegurar que `storage/` y `bootstrap/cache/` sean
escribibles.

## 10. Cron para el scheduler

El comando `domains:check-expirations` (alertas de vencimiento por email)
corre diariamente vía el scheduler de Laravel (`routes/console.php`), que a
su vez necesita **un solo cron** que lo dispare cada minuto. En cPanel →
"Cron Jobs":

```
* * * * * cd /home/usuario/domain-manager && php artisan schedule:run >> /dev/null 2>&1
```

Ajustar la ruta al directorio real del proyecto. Sin este cron, las alertas
de vencimiento simplemente no se envían — nada se rompe visiblemente, así
que es fácil olvidarlo.

## 11. Verificación post-deploy

- Entrar al dominio del admin y hacer login.
- Entrar a uno de los dominios marcados `is_for_sale` y confirmar que carga
  la landing pública (no el panel admin, no un error 404/500).
- Enviar una oferta de prueba desde esa landing y confirmar que llega el
  email al admin (una vez configurado SMTP desde Settings) y que la oferta
  aparece en la vista de ofertas del dominio.
- Confirmar que `APP_DEBUG=false` realmente está activo: forzar un error
  (ej. URL rota) y verificar que se ve la página de error genérica de
  Laravel, no un stack trace.
- Revisar que `https://` (candado) funcione tanto en el dominio admin como
  en cada dominio en venta.

## 12. Actualizaciones futuras

Para desplegar cambios después del primer deploy:

```bash
git pull origin main
composer install --no-dev --optimize-autoloader   # si cambiaron dependencias
php artisan migrate --force                        # si hay migraciones nuevas
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

Si cambió algo en `resources/assets` (frontend), repetir el build de Vite
(paso 5) y subir `public/build/` de nuevo.
