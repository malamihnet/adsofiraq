# cPanel Deployment Guide — Ads of Iraq

This guide covers deploying Ads of Iraq on standard shared cPanel hosting (no VPS, Redis, Docker, or queue workers required).

## Pre-deployment Checklist

- [ ] Build assets locally: `npm run build`
- [ ] Set `APP_ENV=production` and `APP_DEBUG=false`
- [ ] Generate app key: `php artisan key:generate`
- [ ] Run migrations locally or on server
- [ ] Change default admin password

## 1. Create MySQL Database

In cPanel → **MySQL Databases**:

1. Create database (e.g. `username_adsofiraq`)
2. Create user with strong password
3. Add user to database with **ALL PRIVILEGES**

## 2. Upload Files

Upload the entire project to your account. Common layouts:

### Option A: Document root = `/public` (recommended)

Upload project to `/home/username/adsofiraq/` and point the domain document root to `/home/username/adsofiraq/public`.

### Option B: Subfolder install

If you must use `public_html/`:

1. Upload Laravel files **outside** `public_html` (e.g. `/home/username/adsofiraq/`)
2. Copy contents of `public/` into `public_html/`
3. Edit `public_html/index.php` paths:

```php
require __DIR__.'/../adsofiraq/vendor/autoload.php';
(require_once __DIR__.'/../adsofiraq/bootstrap/app.php')
```

Adjust folder names to match your setup.

## 3. Configure `.env`

Create `.env` on the server (copy from `.env.example`):

```env
APP_NAME="Ads of Iraq"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=username_adsofiraq
DB_USERNAME=username_dbuser
DB_PASSWORD=your_secure_password

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
FILESYSTEM_DISK=public
```

## 4. Run Artisan Commands

Via cPanel **Terminal** or SSH:

```bash
cd /home/username/adsofiraq
composer install --optimize-autoloader --no-dev
php artisan key:generate
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 5. File Permissions

```bash
chmod -R 775 storage bootstrap/cache
chown -R username:username storage bootstrap/cache
```

On some hosts, `755` for storage is sufficient. Ensure the web server user can write to `storage/` and `bootstrap/cache/`.

## 6. Storage & Uploads

Campaign thumbnails and stills are stored in:

```
storage/app/public/campaigns/
```

The `php artisan storage:link` command creates:

```
public/storage → storage/app/public
```

Verify uploads work by submitting a test campaign after deployment.

### cPanel without `storage:link` (public_html layout)

If Laravel lives outside `public_html` and `public_html/storage` is **not** symlinked to `storage/app/public`, set in `.env`:

```env
PUBLIC_STORAGE_SYNC_PATH=/home/adsofiraq/public_html/storage
```

After importing or repairing campaign media, run:

```bash
php artisan storage:sync-public
```

Or use **Admin → Import Campaign → Sync Public Storage**. Files are copied from `storage/app/public/campaigns/` to `public_html/storage/campaigns/` so URLs like `https://adsofiraq.com/storage/campaigns/{id}/still-1.webp` work.

## 7. PHP Configuration

In cPanel → **MultiPHP INI Editor**, ensure (recommended for campaign video uploads):

- `upload_max_filesize` = 100M or higher
- `post_max_size` = 120M or higher (must be larger than `upload_max_filesize`)
- `max_file_uploads` = 20 or higher
- `max_execution_time` = 300
- `max_input_time` = 300
- `memory_limit` ≥ 256M
- PHP version ≥ 8.2

Match `.env` upload limits:

```env
UPLOAD_MAX_THUMBNAIL=2048
UPLOAD_MAX_ASSET=5120
UPLOAD_MAX_VIDEO=51200
UPLOAD_MAX_VIDEOS=5
FFMPEG_PATH=ffmpeg
```

## 8. SSL & HTTPS

Enable **AutoSSL** or Let's Encrypt in cPanel. Update `APP_URL` to `https://`.

If using `.htaccess` in `public/`, force HTTPS:

```apache
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

## 9. Cron Jobs (Optional)

For scheduled tasks (future use):

```
* * * * * cd /home/username/adsofiraq && php artisan schedule:run >> /dev/null 2>&1
```

Not required for v1 — no scheduled tasks are active yet.

## 10. Email (Resend API)

Transactional email uses [Resend](https://resend.com) (verification, password reset, notifications).

1. Add and verify domain `adsofiraq.com` in the Resend dashboard.
2. Configure DNS (SPF, DKIM) exactly as Resend instructs — required for Gmail inbox delivery.
3. Create an API key and set in `.env`:

```env
MAIL_MAILER=resend
RESEND_KEY=re_your_api_key_here
MAIL_FROM_ADDRESS=verify@adsofiraq.com
MAIL_FROM_NAME="Ads of Iraq"
```

4. The sender `verify@adsofiraq.com` must be authorized on your verified Resend domain.

**Local debugging:** set `MAIL_MAILER=log` to write messages to `storage/logs/laravel.log` without calling Resend.

**On failure:** transport errors are logged automatically. For automatic failover to the log mailer, set `MAIL_MAILER=failover` (tries Resend, then logs the message).

**cPanel:** no SMTP credentials or `mail.adsofiraq.com` ports are required — only outbound HTTPS to Resend’s API.

## 11. SEO Post-deploy

- Submit sitemap when generated (`/sitemap.xml` — add in future iteration)
- Verify campaign pages have correct OG tags
- Confirm `/admin`, `/login`, `/bookmarks` return `X-Robots-Tag: noindex`

## Troubleshooting

| Issue | Fix |
|-------|-----|
| 500 error | Check `storage/logs/laravel.log`, verify permissions |
| Images not loading | Run `php artisan storage:link`, check `public/storage` symlink |
| CSS/JS missing | Run `npm run build` locally, upload `public/build/` |
| Database connection failed | Verify cPanel DB host is `localhost`, check credentials |
| Route not found | Run `php artisan route:cache`, verify `.htaccess` and mod_rewrite |

## Updating the Site

```bash
git pull   # or upload changed files
composer install --optimize-autoloader --no-dev
php artisan migrate --force
npm run build   # locally, then upload public/build/
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## What NOT to Use on cPanel v1

- Redis
- Meilisearch / Elasticsearch
- Docker
- Horizon / queue workers
- Node.js server-side rendering

All features in v1 work with MySQL, file storage, and standard PHP.
