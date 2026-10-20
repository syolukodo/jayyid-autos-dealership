# Jayyid Autos

Showroom platform for Jayyid Autos: the public vehicle catalogue, a customer
portal (saved cars, inquiries, reservation checkout, instalment schedules) and
a role-based staff back office. Built on **Laravel 12 / PHP 8.2+** with MySQL.

## Requirements

- PHP 8.2+ with `pdo_mysql`, `mbstring`, `openssl`, `curl`, `fileinfo`
- Composer 2
- MySQL 8 or MariaDB 10.4+ (XAMPP works)

No Node build step: the site ships plain CSS/JS from `public/assets`.

## Local setup (XAMPP)

```bash
composer install
cp .env.example .env          # then fill in DB_* (and gateway/mail keys if you have them)
php artisan key:generate
php artisan migrate --seed    # fresh database with demo data
```

Open <http://localhost/jayyid-autos>. The root `index.php` + `.htaccess` route
requests into Laravel and serve static files from `public/`, so no virtual
host is needed. On a server you control, point the document root at
`public/` instead.

Demo sign-ins (password `jayyid2026`):

| Email                  | Role          |
|------------------------|---------------|
| admin@jayyidautos.ng   | Administrator |
| buyer@jayyidautos.ng   | Customer      |

Seeded staff (sales officer, accountant) have no password yet. Send them an
invite from **Admin > Users & Staff**.

### Adopting an existing database

The migrations mirror the pre-Laravel schema and skip tables that already
exist, so an existing `jayyid_autos` database (created from the old
`jayyid_autos.sql`) is adopted in place:

```bash
php artisan migrate   # records the migrations, adds Laravel's own tables, never drops data
```

One schema fix is applied on MySQL: `payment_transactions.payment_method` gains
`PayPal` (settled PayPal instalments previously failed to record).

## Configuration

Everything is in `.env` (see `.env.example` for every key):

- **Paystack**: `PAYSTACK_SECRET_KEY` / `PAYSTACK_PUBLIC_KEY`. Without a secret key the checkout runs in preview mode.
- **PayPal**: `PAYPAL_CLIENT_ID` / `PAYPAL_SECRET` / `PAYPAL_WEBHOOK_ID`, `PAYPAL_MODE=sandbox|live`.
- **Mail**: set `MAIL_MAILER=resend` and `RESEND_API_KEY`. With `MAIL_MAILER=log`, sign-up skips email
  verification and staff invite links are shown to the administrator instead of emailed.

Business rules (fees, tax rate, deposit share, instalment count, invite TTL)
live in `config/showroom.php`. Business contact details and the home page
popup advert are edited in **Admin > Settings**.

### Webhooks

Register these in the gateway dashboards:

| Gateway  | URL                          |
|----------|------------------------------|
| Paystack | `{APP_URL}/webhooks/paystack` |
| PayPal   | `{APP_URL}/webhooks/paypal`   |

The old `/api/paystack-webhook.php` and `/api/paypal-webhook.php` URLs still
work, so existing dashboard settings keep delivering.

## Architecture

| Path | What lives there |
|------|------------------|
| `routes/web.php` | Every route: public site, auth, `/account` portal, `/admin` back office, session-authenticated JSON under `/api`, webhooks, and 301s for the old `*.php` URLs |
| `app/Http/Controllers` | Thin controllers, grouped `Account/`, `Admin/`, `Api/`, `Auth/`, `Webhooks/` |
| `app/Http/Requests` | Form request validation (messages match the original UI copy) |
| `app/Http/Middleware` | `customer`, `staff:{module}` role guards and the suspended-account check |
| `app/Enums` | Roles, back-office modules and every status/choice column |
| `app/Models` | Eloquent models over the existing tables |
| `app/Services/Payments` | Paystack/PayPal clients, server-side amount resolution (`ChargeResolver`) and the payment ledger that applies a payment **exactly once** |
| `app/Services/Promo` | Promo code rules (dates, usage limits, minimum order, brand/category/vehicle targeting) |
| `app/Services/Selection` | Session-backed vehicle selection (mini-cart) and checkout totals |
| `app/Actions` | Inquiry creation and customer registration |
| `resources/views` | Blade layouts (`public`, `account`, `admin`, `auth`), pages, components and emails |
| `public/assets` | CSS, JS, fonts and images (unchanged design) |
| `public/uploads` | Back-office uploads (vehicle photos, adverts), git-ignored |

Access by role (`app/Enums/UserRole.php`):

- **Administrator**: every module
- **Sales Officer**: vehicles, add vehicle, inquiries
- **Finance & Accountant**: inquiries, payments

## SEO

Nigeria first, international second:

- Pages declare `en-NG` (plus an `x-default` alternate), prices are NGN and titles target Lagos/Nigeria searches.
- **Landing pages** at `/cars-for-sale/{slug}` for each make, body type and condition (`/cars-for-sale/toyota`, `/cars-for-sale/tokunbo`, `/cars-for-sale/suv`), with copy built from live stock. Single-filter `/inventory?make=...` queries 301 to them; other filter/sort combinations are `noindex` with a canonical to `/inventory`.
- **Vehicle URLs** carry the title (`/vehicles/5-2022-toyota-land-cruiser-prado-tx-l`); id-only and stale slugs 301 to the canonical URL.
- **Structured data**: AutoDealer (address, geo, hours, phones), WebSite, Car + Offer (NGN, availability, condition), BreadcrumbList and ItemList.
- `/sitemap.xml` (pages, landings, vehicles with images) and `/robots.txt` are generated; robots allows crawling only when `APP_ENV=production`.
- Open Graph/Twitter cards, geo meta, canonical tags; portals and auth pages are `noindex`.
- CSS/JS URLs are cache-busted (`?v=mtime`) and `public/.htaccess` sets long cache lifetimes and compression (enable `mod_expires` and `mod_deflate` on Apache).

After launch: set the SEO keys in `.env`, verify the domain in Google Search Console and Bing, submit `/sitemap.xml`, and keep the Google Business Profile address and hours identical to Admin > Settings.

## Tests

```bash
php artisan test
```

The suite runs on in-memory SQLite and never calls a real gateway (HTTP is
faked; stray requests fail the test).

## Deployment checklist

- `APP_ENV=production`, `APP_DEBUG=false`, real `APP_URL` (used for gateway return URLs and emails)
- `composer install --no-dev --optimize-autoloader`
- `php artisan migrate --force`
- `php artisan config:cache route:cache view:cache`
- `storage/` and `public/uploads/` writable by the web server
- Optional: `QUEUE_CONNECTION=database` plus a `php artisan queue:work` process
