# Advanced installation and settings

For a simple step by step guide, see [SETUP.md](../SETUP.md). This guide installs AmarEcommerce on your computer for development, on a Linux server (VPS) and on cPanel shared hosting. For what the app does, see the [README](../README.md). For how it is built, see [TECHNICAL.md](TECHNICAL.md).

## Contents

- [Requirements](#requirements)
- [Local installation](#local-installation)
- [Production installation (VPS)](#production-installation)
- [cPanel (shared hosting)](#cpanel-shared-hosting)
- [All environment settings](#2-configure-the-environment-all-settings)

## Requirements

| Software | Version |
|---|---|
| PHP | 8.4 with `pdo_mysql`, `mbstring`, `intl`, `fileinfo`, `gd` (with WebP), `zip`, `exif`, `dom` |
| Composer | 2.x |
| Node.js / npm | 20+ (builds the front-end assets only) |
| MySQL / MariaDB | MySQL 8+ or MariaDB 10.6+ |

By default the app needs no queue worker and no cron jobs, so it runs on shared hosting (cPanel) as well as on a VPS. Turning on the mail queue (`MAIL_QUEUE_ENABLED=true`) adds one worker so emails are retried when the mail server is down.

## Local installation

### 1. Clone and install dependencies

```bash
git clone <repository-url> amar-ecommerce-lite
cd amar-ecommerce-lite
composer install
npm install
```

### 2. Configure the environment

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` and set the database connection:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=amar_ecom_lite
DB_USERNAME=your_user
DB_PASSWORD=your_password
```

For local email testing, point the mailer at [Mailpit](https://mailpit.axllent.org/) (web inbox at `http://127.0.0.1:8025`):

```dotenv
MAIL_MAILER=failover
MAIL_HOST=127.0.0.1
MAIL_PORT=1025
```

`failover` sends through SMTP and, if the mail server is down, writes the email to `storage/logs/laravel.log` instead of failing the request. `MAIL_TIMEOUT` (default 10 seconds) limits how long it waits for the server.

### 3. Create the database tables and sample data

```bash
php artisan migrate --seed
php artisan storage:link
php artisan ai-assistant:embed
```

### 4. Start the app

```bash
composer dev
```

This runs the web server, log viewer and Vite together. Open `http://localhost:8200`.

To open the dev site from a phone or another computer on the same network, set `SERVER_HOST=0.0.0.0` and `VITE_DEV_HOST` to this machine's LAN IP (`hostname -I`) in `.env`, then restart `composer dev` from a new terminal. Open `http://<LAN-IP>:8200`. Keep `SERVER_HOST=127.0.0.1` on shared or public networks, because debug mode shows error details to anyone who can reach the site.

New customers must verify their email before they can use their account: registering opens the verification page (with a resend button) without logging in, and logging in unverified sends a fresh link and opens the same page instead of logging in. Locally the verification emails arrive in Mailpit; the seeded accounts are already verified.

### Sample accounts

All seeded accounts use the password `123456`.

| Role | Email | Login page |
|---|---|---|
| Super Admin | `admin@example.com` | `/backoffice/login` |
| Moderator | `moderator@example.com` | `/backoffice/login` |
| Customer | `customer@example.com` | `/user/login` |

### Resetting the database

```bash
php artisan app:fresh --seed
```

This deletes every media library item with `media-library:clear`, removes leftover media files (product, category, banner and avatar images), then runs `migrate:fresh --seed`. Use it instead of `php artisan migrate:fresh`, which leaves orphaned files in `storage/`.

`composer serve:dev` keeps your data. It clears Laravel's caches, runs `php artisan migrate` (no seeding), rebuilds the caches with `php artisan optimize`, embeds the products for the AI assistant with `php artisan ai-assistant:embed` (it only prints a notice when no embedding model is set), and finally starts the dev servers with `composer run dev`.

### Running tests

Tests use an in-memory SQLite database defined in `phpunit.xml`. If your shell exports the values from `.env` (for example `DB_CONNECTION=mysql`), those values override `phpunit.xml` and the tests will reset your local MySQL database. Unset them when running tests:

```bash
env $(grep -oE '^[A-Z_][A-Z0-9_]*' .env | sed 's/^/-u /') php artisan test --compact
```

Run `php artisan optimize:clear` first if you ran `composer serve:dev` or `php artisan optimize`, because tests refuse to run with cached config.

Other checks:

```bash
vendor/bin/pint --dirty   # code style
composer types:check      # PHPStan
```

## Production installation

The steps below are for a Linux server (VPS) with Nginx and PHP-FPM. A cPanel section follows.

### 1. Upload the code and install dependencies

```bash
git clone <repository-url> /var/www/amar-ecommerce-lite
cd /var/www/amar-ecommerce-lite
composer install --no-dev --optimize-autoloader
npm ci
npm run build
```

### 2. Configure the environment (all settings)

```bash
cp .env.example .env
php artisan key:generate
```

Set at least the following in `.env`:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com
APP_IS_IN_PRODUCTION=true
DEV_CAN_MIGRATE_FRESH=false
SESSION_SECURE_COOKIE=true

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=your_database
DB_USERNAME=your_user
DB_PASSWORD=your_password
DB_SEEDING_ENABLED=false
DB_SEED_ADMIN_EMAIL=you@your-domain.com
DB_SEED_ADMIN_PASSWORD=a-strong-password

APP_CURRENCY=BDT
APP_CURRENCY_SYMBOL=৳
VALID_PHONE_LENGTH=11

MAIL_MAILER=failover
MAIL_HOST=your.smtp.host
MAIL_PORT=587
MAIL_USERNAME=your_smtp_user
MAIL_PASSWORD=your_smtp_password
MAIL_FROM_ADDRESS=no-reply@your-domain.com

```

Review the app-specific settings as well:

| Setting | Purpose |
|---|---|
| `APP_CATEGORY_LEVEL` | How deep categories go: `1` root only, `2` root › sub (default), `3` root › sub › sub-sub (max). After lowering it, deeper categories are ignored, not deleted |
| `SESSION_SECURE_COOKIE` | `true` on a live HTTPS site, so the login cookie is never sent over plain http. Keep `false` locally |
| `SEO_INDEXING_ENABLED`, `SEO_DESCRIPTION`, `SEO_IMAGE`, `SEO_GOOGLE_SITE_VERIFICATION` | Search and link previews. These are fallbacks: values saved in Settings → SEO win. `SEO_INDEXING_ENABLED=false` always hides every page from search engines (staging), whatever the backoffice switch says. Then the default description, the default share image (empty uses the logo) and the Google Search Console code |
| `SEO_PAGE_NOINDEX_ENABLED` | `true` shows the Hide From Search switch in the SEO form of products and categories and applies it (the page gets `noindex` and leaves the sitemap). Default `false` |
| `SEO_IMAGE_MAX_SIZE_MB` | Max size of an uploaded link preview image. Default `2` |
| `ADMIN_ROUTE_PREFIX` | URL prefix of the whole admin area: `/{prefix}/login`, `/{prefix}/dashboard`, `/{prefix}/manage/…`, `/{prefix}/settings/…`. Default `backoffice`. Use a hard-to-guess value on a live site, then run `php artisan optimize`. Staff must use the new login URL |
| `APP_IS_IN_PRODUCTION` | `true` when the app runs in production. A plain flag, read with `config('app.is_in_production')`. Default `false` |
| `DEV_AI_AGENT` | Development only, for AI coding agents. The app never reads it. `free` = lean mode (core rules, fewest tool calls and reads), `paid` = full `AGENTS.md` workflow. Default `free` |
| `DEV_CAN_MIGRATE_FRESH` | Development only, for AI coding agents. The app never reads it. `true` = agents may edit existing migrations and you rebuild with `php artisan app:fresh --seed`. `false` = the database holds real data, schema changes go in new migrations only and `app:fresh` must not be run. Default `false` |
| `APP_MAINTENANCE_DRIVER` | `system` (default): maintenance mode is stored in the `system_settings` table and managed from Settings → System, and `php artisan down`/`up` change the same setting. Needs no cron or queue |
| `MAIL_QUEUE_ENABLED`, `MAIL_QUEUE_BACKOFF_SECONDS`, `MAIL_QUEUE_RETRY_HOURS` | `false` (default) sends order emails right after the response, with no worker; if SMTP is down the email goes to the log. `true` queues them and retries every 300 seconds for 24 hours until the mail server answers; needs a queue worker (step 6, or the cPanel cron below). Verification and password reset emails are always sent at once, because their links expire in minutes |
| `APP_SEARCH_DEBOUNCE_MS` | Wait after the last keystroke before any search box searches, in milliseconds. Default `300`; applies to every search in the shop, the navbar and the admin panel |
| `APP_NOTIFICATION_LONG_POLL_SECONDS` | Seconds between refreshes of the notification bell (unread count and latest notifications) in the navbar and the admin panel. Default `300` (5 minutes) |
| `APP_CURRENCY`, `APP_CURRENCY_SYMBOL` | Currency code (invoices) and symbol (screens, emails, messages). Amounts are always whole units, for example ৳550 |
| `APP_EMPTY_VALUE` | Text shown for an empty field in admin tables, detail views and invoices (default `N/A`) |
| `VALID_PHONE_LENGTH` | Exact number of digits a profile phone number must have. Default `11` (`01XXXXXXXXX`) |
| `SHOP_ORDER_PREFIX`, `SHOP_OWNER_EMAIL` | Order ID prefix (`AE-XXXXXX`) and the address that receives "new order" emails |
| `OFFER_GLOBAL_ONLY` | `true`: staff create whole-shop offers only (the type and categories are hidden, and the shop ignores category offers). `false` (default) also allows offers on chosen root categories. Either way two global offers cannot be active with overlapping dates |
| `SHOP_MAX_EXTRA_DISCOUNT` | Highest extra discount (whole %) staff can add to an order on request, applied after offer discounts. Default `50` |
| `SUPPORT_EMAIL`, `WHATSAPP_NUMBER`, `WHATSAPP_COUNTRY_CODE`, `FACEBOOK_MESSENGER_URL`, `FACEBOOK_PAGE_URL`, `FACEBOOK_GROUP_URL` | Contact channels shown after checkout and on the contact page |
| `PAYMENT_GATEWAYS` | First install only: gateways that start enabled, in checkout order, comma separated: `cod`, `manual`, `stripe`, `sslcommerz`, `bkash`. After that, staff change them in Settings → Payment Gateways |
| `PAYMENT_BKASH_NUMBER`, `PAYMENT_NAGAD_NUMBER` | First install only: optional bKash and Nagad numbers staff share with customers. After that, staff change them in Settings → Payment Gateways |
| `STRIPE_PUBLISHABLE_KEY`, `STRIPE_SECRET_KEY`, `STRIPE_WEBHOOK_SECRET` | Stripe keys. Use `pk_test_`/`sk_test_` keys for the sandbox. Point the webhook at `/payments/webhook/stripe` (events `payment_intent.*`), or run `stripe listen --forward-to <APP_URL>/payments/webhook/stripe` locally |
| `SSLCOMMERZ_STORE_ID`, `SSLCOMMERZ_STORE_PASSWORD`, `SSLCOMMERZ_SANDBOX`, `SSLCOMMERZ_COUNTRY` | SSLCommerz store credentials. `SSLCOMMERZ_SANDBOX=true` uses the sandbox, `false` the live store. The IPN URL is `/payments/webhook/sslcommerz`. `SSLCOMMERZ_COUNTRY` is sent as the customer country |
| `BKASH_APP_KEY`, `BKASH_APP_SECRET`, `BKASH_USERNAME`, `BKASH_PASSWORD`, `BKASH_SANDBOX` | bKash Tokenized Checkout credentials. `BKASH_SANDBOX=true` uses the sandbox, `false` the live API. Live credentials come from the bKash merchant portal. Payments are in BDT only |
| `GOOGLE_*`, `FACEBOOK_*` | Social login credentials |
| `FRONTEND_CACHE_ENABLED`, `FRONTEND_CACHE_STORE`, `FRONTEND_CACHE_TTL_MINUTES` | Turns the public page cache on or off (default `true`; `false` reads the database on every request), its cache store (empty uses `CACHE_STORE`; set `redis` to move it to Redis) and its lifetime (default 180 minutes). Admin changes clear the affected data immediately |
| `PRODUCT_IMAGE_MAX_FILES`, `PRODUCT_IMAGE_MAX_SIZE_MB`, `BANNER_IMAGE_MAX_SIZE_MB`, `CATEGORY_IMAGE_MAX_SIZE_MB`, `USER_AVATAR_MAX_SIZE_MB`, `LIVEWIRE_TEMPORARY_FILE_UPLOAD_MAX_SIZE_MB` | Upload size limits |
| `AI_ASSISTANT_ENABLED`, `AI_ASSISTANT_HISTORY_MESSAGES`, `AI_ASSISTANT_MESSAGE_MAX_LENGTH`, `AI_ASSISTANT_TIMEOUT_SECONDS`, `AI_ASSISTANT_TOP_MATCHES`, `AI_ASSISTANT_RESULTS`, `AI_ASSISTANT_CACHE_MINUTES`, `AI_ASSISTANT_MAX_OUTPUT_TOKENS`, `AI_ASSISTANT_EXAMPLE_PROMPTS_UI`, `AI_CHAT_DRIVER`, `AI_CHAT_URL`, `AI_CHAT_API_KEY`, `AI_CHAT_MODEL`, `AI_CHAT_REASONING`, `AI_EMBEDDING_DRIVER`, `AI_EMBEDDING_URL`, `AI_EMBEDDING_API_KEY`, `AI_EMBEDDING_MODEL`, `AI_EMBEDDING_DIMENSIONS` | AI shopping assistant (beta). It shows only when enabled and `AI_CHAT_URL`, `AI_CHAT_API_KEY` and `AI_CHAT_MODEL` are set. Settings are per job, not per provider: `AI_CHAT_DRIVER` is the API format (`openai` for Groq, OpenAI, Mistral, Ollama and other compatible APIs, or `gemini`). The default is free Groq (`openai/gpt-oss-120b`, `AI_CHAT_REASONING=low`) for chat and Gemini (`gemini-embedding-001`) for embeddings, because Groq has no embedding models. Empty embedding driver, URL or key reuse the chat ones. With `AI_EMBEDDING_MODEL` set, run `php artisan ai-assistant:embed` once to embed the catalogue (new and edited products are embedded automatically). Without it, product search uses keywords. For Gemini chat use `gemini-flash-lite-latest` with `AI_CHAT_REASONING` empty (a number sets a thinking budget, 0 = off). `AI_ASSISTANT_CACHE_MINUTES` reuses identical answers to save tokens (0 turns it off), `AI_ASSISTANT_MAX_OUTPUT_TOKENS` caps each reply, and `AI_ASSISTANT_EXAMPLE_PROMPTS_UI` sets the example cards, separated by `|` |
| `THROTTLE_*` | Rate limits per minute: login, registration, checkout, order tracking, password reset, opening reset links, verification email and assistant messages |
| `PASSWORD_RESET_RESEND_SECONDS`, `PASSWORD_RESET_EXPIRE_MINUTES` | Password reset resend wait and link lifetime |
| `PASSWORD_MIN_LENGTH` | Minimum password length for every account (registration, reset, staff, settings). Default `6`. On a live site passwords also need mixed case, numbers and symbols |
| `EMAIL_VERIFICATION_RESEND_SECONDS`, `EMAIL_VERIFICATION_EXPIRE_MINUTES` | Wait before a customer can resend the verification email, and how long a verification link works |
| `DB_SEEDING_ENABLED` | `false` makes `--seed` create only the roles and one super admin, never demo accounts, products or orders. Keep it `false` on a live site |
| `DB_SEED_ADMIN_EMAIL`, `DB_SEED_ADMIN_PASSWORD` | The super admin seeded when `DB_SEEDING_ENABLED=false`. Skipped when empty or when the email already exists. Clear the password after the first seed |
| `QUEUE_CONVERSIONS_BY_DEFAULT` | `false` resizes product and other images during the upload instead of on a queue, so no queue worker is needed |

Customers must verify their email before they can log in and order, so the `MAIL_*` settings must work on the live site.

### 3. Set up the database and storage

```bash
php artisan migrate --force --seed
php artisan storage:link
php artisan optimize
php artisan ai-assistant:embed
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

With `DB_SEEDING_ENABLED=false`, `--seed` only creates the roles, the permissions and the super admin from `DB_SEED_ADMIN_EMAIL` / `DB_SEED_ADMIN_PASSWORD`. Sign in, change the password, then clear `DB_SEED_ADMIN_PASSWORD` in `.env`.

> **Never run `php artisan app:fresh` or `php artisan migrate:fresh` on a live site.** They delete all users, orders, products, uploaded images and banners.

### 4. Nginx

Point the site root to the `public` directory and allow uploads larger than the app limit:

```nginx
server {
    server_name your-domain.com;
    root /var/www/amar-ecommerce-lite/public;
    index index.php;

    client_max_body_size 110M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
        fastcgi_read_timeout 300s;
    }
}
```

```bash
sudo nginx -t && sudo systemctl reload nginx
```

### 5. PHP limits

In `/etc/php/8.4/fpm/php.ini`:

```ini
upload_max_filesize = 128M
post_max_size = 128M
max_execution_time = 300
memory_limit = 256M
```

```bash
sudo systemctl restart php8.4-fpm
```

The limits must follow this order: Nginx `client_max_body_size` ≥ `post_max_size` ≥ `upload_max_filesize` ≥ `LIVEWIRE_TEMPORARY_FILE_UPLOAD_MAX_SIZE_MB`.

### 6. Queue worker and scheduler (optional)

Without a worker, emails are sent right after the response and images are resized during the upload (`QUEUE_CONVERSIONS_BY_DEFAULT=false`). Set up the worker only when `MAIL_QUEUE_ENABLED=true`: order emails then wait in the `jobs` table and are retried every `MAIL_QUEUE_BACKOFF_SECONDS` for `MAIL_QUEUE_RETRY_HOURS` while the mail server is down. With the queue on and no worker running, no order email is sent.

Create `/etc/systemd/system/amar-ecommerce-lite-queue.service`:

```ini
[Unit]
Description=AmarEcommerce queue worker
After=network.target

[Service]
User=www-data
Restart=always
WorkingDirectory=/var/www/amar-ecommerce-lite
ExecStart=/usr/bin/php artisan queue:work --tries=1 --max-time=3600

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl enable --now amar-ecommerce-lite-queue
```

Add the scheduler to the `www-data` crontab (`sudo crontab -u www-data -e`):

```cron
* * * * * cd /var/www/amar-ecommerce-lite && php artisan schedule:run >> /dev/null 2>&1
```

### 7. Updating a live site

```bash
cd /var/www/amar-ecommerce-lite
php artisan down
git pull
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan optimize
php artisan ai-assistant:embed
php artisan up
```

### Free hosting without Terminal (InfinityFree)

InfinityFree serves only the `htdocs` folder and has no Terminal, symlinks or document root setting.

1. **Build locally**, with production values in `.env` (`APP_ENV=production`, `APP_DEBUG=false`, the host's database, `MEDIA_DISK=assets`), then run `composer install --no-dev --optimize-autoloader` and `npm run build`.
2. **Prepare the database locally:** run `php artisan migrate --seed` against a local copy, export it to a `.sql` file and import it in the host's phpMyAdmin.
3. **Upload the whole project** into `htdocs`, including `vendor`, `public/build` and the root `.htaccess` (`node_modules` is not needed). The root `.htaccess` sends every request to `public/` and blocks the other folders, so `.env` stays private.
4. **Check** that `https://your-domain/.env` and `https://your-domain/vendor` return an error page, and that an uploaded product image shows.

### cPanel (shared hosting)

The app runs on cPanel if the host provides PHP 8.4, Terminal (SSH) access and Cron Jobs.

1. **Check the server** in cPanel → Terminal:

   ```bash
   php -v
   php -i | grep disable_functions
   ```

2. **Build locally**:

   ```bash
   composer install --no-dev --optimize-autoloader
   npm ci
   npm run build
   ```

   Upload the whole project, including `vendor` and `public/build` (`node_modules` is not needed), to a folder outside `public_html`, for example `~/amar-ecommerce-lite`.

3. **Point the domain to `public`.** In cPanel → Domains, set the document root to `amar-ecommerce-lite/public`. If the main domain's root cannot be changed:

   ```bash
   rm -rf ~/public_html && ln -s ~/amar-ecommerce-lite/public ~/public_html
   ```

4. **Create the database** in cPanel → MySQL Databases, then fill in `.env` as in production step 2 (Configure the environment).

5. **Run the setup** in Terminal:

   ```bash
   cd ~/amar-ecommerce-lite
   php artisan key:generate
   php artisan migrate --force --seed
   php artisan storage:link
   php artisan optimize
   php artisan ai-assistant:embed
   chmod -R 775 storage bootstrap/cache
   ```

   Then create your admin account as shown in production step 3.

   **Images:** `MEDIA_DISK=assets` saves images straight into `public/assets/uploads`, so `storage:link` is not needed. `MEDIA_DISK=r2` saves them to a Cloudflare R2 bucket (fill every `R2_*` key, the site refuses to load while one is empty). Set `R2_ROOT` to share one bucket between projects. Switching an existing shop moves no files, so choose before the first upload, and run `php artisan optimize:clear` after a switch.

6. **No Cron Jobs are needed** while `MAIL_QUEUE_ENABLED=false`; keep `QUEUE_CONVERSIONS_BY_DEFAULT=false` so images are resized during the upload. To retry emails when the mail server is down, set `MAIL_QUEUE_ENABLED=true` and add one cron job (cPanel → Cron Jobs, every minute) that runs the worker briefly:

   ```cron
   * * * * * cd /home/USER/amar-ecommerce-lite && php artisan queue:work --stop-when-empty --max-time=50 >> /dev/null 2>&1
   ```

7. **Raise upload limits** in cPanel → MultiPHP INI Editor:

   ```ini
   upload_max_filesize = 128M
   post_max_size = 128M
   max_execution_time = 300
   memory_limit = 256M
   ```

   If large uploads still fail with "413 Request Entity Too Large", ask the host to raise the web server request body limit above 100 MB.
