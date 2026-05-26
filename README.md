# Ads of Iraq

The Archive of Iraqi Advertising — a cPanel-friendly Laravel platform for browsing, submitting, and preserving advertising campaigns from Iraq and the region.

## Stack

- Laravel 11
- MySQL / MariaDB
- Blade + Tailwind CSS + Alpine.js
- Local file storage (`public/storage`)
- YouTube/Vimeo embeds only (no video uploads)

## Local Setup

### Requirements

- PHP 8.2+
- Composer
- Node.js 18+
- MySQL / MariaDB

### Installation

```bash
# From project root
composer install
cp .env.example .env
php artisan key:generate

# Configure .env database credentials, then:
php artisan migrate
php artisan db:seed
php artisan storage:link

npm install
npm run build

php artisan serve
```

Visit `http://localhost:8000`

### Default Admin Account

- **Email:** admin@adsofiraq.com
- **Password:** password

Change this immediately after first login.

## Features

| Page | Route | Access |
|------|-------|--------|
| Home | `/` | Public |
| Campaigns | `/campaigns` | Public |
| Campaign detail | `/campaigns/{slug}` | Public (approved only) |
| Submit campaign | `/campaigns/create` | Auth |
| User profile | `/users/{username}` | Public |
| Bookmarks | `/bookmarks` | Auth |
| Following feed | `/following` | Auth |
| Admin dashboard | `/admin` | Admin |

## Campaign Workflow

1. Logged-in users submit campaigns → status `pending`
2. Admin reviews in `/admin/campaigns`
3. Approve → status `approved`, visible publicly
4. Reject → status `rejected`, visible only to owner/admin
5. Feature → appears in homepage featured section

## cPanel Deployment

See [DEPLOYMENT.md](DEPLOYMENT.md) for full cPanel hosting instructions.

## Project Structure

```
app/
  Http/Controllers/     # Public, Auth, Admin controllers
  Models/               # Eloquent models
  Policies/             # Authorization policies
  Services/             # Video URL parser, uploads, taxonomy
database/migrations/    # Schema
database/seeders/       # Industries, mediums, countries, admin
resources/views/        # Blade templates + components
routes/web.php          # All web routes
```

## SEO

- Campaign pages include title, meta description, and OG image (thumbnail)
- Clean slug URLs (`/campaigns/campaign-title`)
- Auth, admin, bookmarks, and following pages use `noindex`

## Security

- CSRF protection on all forms
- Laravel policies for campaign access
- Video URL validation (YouTube/Vimeo only)
- Duplicate bookmark prevention
- Self-follow prevention
- Admin middleware on `/admin/*`

## License

MIT
