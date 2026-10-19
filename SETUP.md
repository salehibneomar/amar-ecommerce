# Installation and setup

This guide installs নতুন কৃষক on your computer for development, on a Linux server (VPS) and on cPanel shared hosting. For what the app does, see the [README](README.md). For how it is built, see [TECHNICAL.md](TECHNICAL.md).

## Contents

- [Requirements](#requirements)
- [Book preview engines](#book-preview-engines)
- [Local installation](#local-installation)
- [Production installation (VPS)](#production-installation)
- [cPanel (shared hosting)](#cpanel-shared-hosting)
- [All environment settings](#2-configure-the-environment-all-settings)

## Requirements

| Software | Version |
|---|---|
| PHP | 8.4 with `pdo_mysql`, `mbstring`, `intl`, `fileinfo`, `gd` (with WebP), `zip`, `exif`, `dom` |
| Composer | 2.x |
| Node.js / npm | 20+ (builds assets and, with pdf-lib, creates free book previews) |
| MySQL / MariaDB | MySQL 8+ or MariaDB 10.6+ |

Free book previews also need one of the two PDF engines described in [Book preview engines](#book-preview-engines). pdf-lib is the default.

By default the app needs no queue worker and no cron jobs, so it runs on shared hosting (cPanel) as well as on a VPS. Turning on the mail queue (`MAIL_QUEUE_ENABLED=true`) adds one worker so emails are retried when the mail server is down.

## Book preview engines

Paid books get a free preview made from their first `BOOK_PREVIEW_PAGES` pages. `App\Services\Backoffice\Catalog\BookPreviewService` counts the pages and trims the PDF through one of two interchangeable traits:

| Engine | Trait | Needs on the server | Setting |
|---|---|---|---|
| **pdf-lib** (default) | `App\Traits\Pdf\PdfLibTrait` | Node.js 20+ and `node_modules/pdf-lib` (installed by `npm install` / `npm ci`) | `NODE_BINARY` |
| **Ghostscript** | `App\Traits\Pdf\GhostscriptTrait` | Ghostscript 9.50+ (`gs`) | `GHOSTSCRIPT_BINARY` |

Both engines run as an external process, so PHP's `proc_open` must be enabled. If a preview cannot be created, the error is logged, the book stays uploaded without a preview, and the app tries again after 10 minutes.

### pdf-lib (default)

pdf-lib is a JavaScript library run by [resources/scripts/pdf-lib.js](resources/scripts/pdf-lib.js). It needs no system packages, so it suits hosts where Ghostscript cannot be installed, such as most cPanel accounts.

```dotenv
NODE_BINARY=node
```

Use the full path to `node` when PHP cannot find it on its `PATH` (common on cPanel). pdf-lib loads the whole PDF into memory, so very large books need enough memory for the Node process. It cannot copy pages from encrypted PDFs.

### Ghostscript

Ghostscript is faster with large PDFs and handles encrypted files, but it must be installed on the server:

```bash
sudo apt install ghostscript
```

```dotenv
GHOSTSCRIPT_BINARY=/usr/bin/gs
```

To switch to Ghostscript, edit `app/Services/Backoffice/Catalog/BookPreviewService.php`: replace `use App\Traits\Pdf\PdfLibTrait;` and `use PdfLibTrait;` with `GhostscriptTrait`, and replace the `nodeBinary()` method with:

```php
protected function ghostscriptBinary(): string
{
    return (string) bookPreviewConfig('ghostscript', 'gs');
}
```

Then run `php artisan optimize`. Run the same steps in reverse to switch back. The tests in `BookReaderTest` and `BookMediaTest` skip themselves when pdf-lib is not installed; change `skipWithoutPdfLib()` in `tests/Pest.php` to check for `gs` if you switch engines permanently.

## Local installation

### 1. Clone and install dependencies

```bash
git clone <repository-url> notun-krisok
cd notun-krisok
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
DB_DATABASE=notun_krisok
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

This runs the web server, log viewer and Vite together. Open `http://localhost:8100`.

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
composer serve:dev
```

This clears Laravel's caches, deletes every media library item with `media-library:clear`, removes leftover media files (book PDFs, previews, covers and banners), runs `migrate:fresh --seed`, rebuilds the caches with `php artisan optimize`, then embeds the books for the AI assistant with `php artisan ai-assistant:embed` (it only prints a notice when no embedding model is set), and finally starts the dev servers with `composer run dev`. Use it instead of `php artisan migrate:fresh`, which leaves orphaned files in `storage/`.

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
git clone <repository-url> /var/www/notun-krisok
cd /var/www/notun-krisok
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
APP_IS_LIVE=true
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

NODE_BINARY=/usr/bin/node
```

Set `GHOSTSCRIPT_BINARY` instead when using the [Ghostscript engine](#ghostscript).

Review the app-specific settings as well:

| Setting | Purpose |
|---|---|
| `APP_CATEGORY_LEVEL` | How deep categories go: `1` root only, `2` root › sub (default), `3` root › sub › sub-sub (max). After lowering it, deeper categories are ignored, not deleted |
| `SESSION_SECURE_COOKIE` | `true` on a live HTTPS site, so the login cookie is never sent over plain http. Keep `false` locally |
| `SEO_INDEXING_ENABLED`, `SEO_DESCRIPTION`, `SEO_IMAGE` | Search and link previews: `false` hides every page from search engines (staging), the default description, and the default share image (empty uses the logo). Book pages use their own description and cover |
| `ADMIN_ROUTE_PREFIX` | URL prefix of the whole admin area: `/{prefix}/login`, `/{prefix}/dashboard`, `/{prefix}/manage/…`, `/{prefix}/settings/…`. Default `backoffice`. Use a hard-to-guess value on a live site, then run `php artisan optimize`. Staff must use the new login URL |
| `APP_IS_LIVE` | `true` once the site holds real data. From then on schema changes go in new migrations, never edits to existing ones, and `composer serve:dev` must not be run. Default `false` (pre-launch) |
| `APP_MAINTENANCE_DRIVER` | `system` (default): maintenance mode is stored in the `system_settings` table and managed from Settings → System, and `php artisan down`/`up` change the same setting. Needs no cron or queue |
| `MAIL_QUEUE_ENABLED`, `MAIL_QUEUE_BACKOFF_SECONDS`, `MAIL_QUEUE_RETRY_HOURS` | `false` (default) sends order emails right after the response, with no worker; if SMTP is down the email goes to the log. `true` queues them and retries every 300 seconds for 24 hours until the mail server answers; needs a queue worker (step 6, or the cPanel cron below). Verification and password reset emails are always sent at once, because their links expire in minutes |
| `APP_SEARCH_DEBOUNCE_MS` | Wait after the last keystroke before any search box searches, in milliseconds. Default `300`; applies to every search in the shop, the navbar and the admin panel |
| `APP_CURRENCY`, `APP_CURRENCY_SYMBOL` | Currency code (invoices) and symbol (screens, emails, messages). Amounts are always whole units, for example ৳550 |
| `VALID_PHONE_LENGTH` | Exact number of digits a profile phone number must have. Default `11` (`01XXXXXXXXX`) |
| `SHOP_ORDER_PREFIX`, `SHOP_OWNER_EMAIL` | Order ID prefix (`NK-XXXXXX`) and the address that receives "new order" emails |
| `OFFER_GLOBAL_ONLY` | `true`: staff create whole-shop offers only (the type and categories are hidden, and the shop ignores category offers). `false` (default) also allows offers on chosen root categories. Either way two global offers cannot be active with overlapping dates |
| `SHOP_MAX_EXTRA_DISCOUNT` | Highest extra discount (whole %) staff can add to an order on request, applied after offer discounts. Default `50` |
| `SUPPORT_EMAIL`, `WHATSAPP_NUMBER`, `WHATSAPP_COUNTRY_CODE`, `FACEBOOK_MESSENGER_URL`, `FACEBOOK_PAGE_URL`, `FACEBOOK_GROUP_URL` | Contact channels shown after checkout and on the contact page |
| `PAYMENT_DEFAULT_METHOD`, `PAYMENT_BKASH_NUMBER`, `PAYMENT_NAGAD_NUMBER` | Manual payment methods and the numbers customers pay to |
| `GOOGLE_*`, `FACEBOOK_*` | Social login credentials |
| `BOOK_STOCK_TYPE` | `both` (default: staff pick digital or physical per book), `digital` or `physical`. `physical` hides the digital options: staff upload a short preview PDF that is shown as it is, and books cannot be free, read in full or downloaded. The stored data is not changed |
| `BOOK_PREVIEW_PAGES` | Number of pages in the free preview (`both` and `digital` only) |
| `FRONTEND_CACHE_ENABLED`, `FRONTEND_CACHE_STORE`, `FRONTEND_CACHE_TTL_MINUTES` | Turns the public page cache on or off (default `true`; `false` reads the database on every request), its cache store (empty uses `CACHE_STORE`; set `redis` to move it to Redis) and its lifetime (default 180 minutes). Admin changes clear the affected data immediately |
| `NODE_BINARY`, `GHOSTSCRIPT_BINARY` | Path to the binary used by the active [book preview engine](#book-preview-engines) |
| `BOOK_FILE_MAX_SIZE_MB`, `BOOK_COVER_MAX_SIZE_MB`, `BANNER_IMAGE_MAX_SIZE_MB`, `CATEGORY_IMAGE_MAX_SIZE_MB`, `USER_AVATAR_MAX_SIZE_MB`, `LIVEWIRE_TEMPORARY_FILE_UPLOAD_MAX_SIZE_MB` | Upload size limits |
| `AI_ASSISTANT_ENABLED`, `AI_ASSISTANT_PROVIDER`, `AI_ASSISTANT_HISTORY_MESSAGES`, `AI_ASSISTANT_MESSAGE_MAX_LENGTH`, `AI_ASSISTANT_TIMEOUT_SECONDS`, `AI_ASSISTANT_CANDIDATES`, `AI_ASSISTANT_RESULTS`, `AI_ASSISTANT_CACHE_MINUTES`, `AI_ASSISTANT_MAX_OUTPUT_TOKENS`, `AI_ASSISTANT_EXAMPLE_PROMPTS_UI`, `GEMINI_URL`, `GEMINI_API_KEY`, `GEMINI_MODEL`, `GEMINI_EMBEDDING_MODEL`, `GEMINI_EMBEDDING_DIMENSIONS`, `GEMINI_THINKING_BUDGET` | AI shopping assistant (beta). It shows only when enabled and the provider's key and model are set. Use the current free-tier Gemini flash model ID. With `GEMINI_EMBEDDING_MODEL` set, run `php artisan ai-assistant:embed` once to embed the catalogue (new and edited books are embedded automatically). Without it, product search uses keywords. `AI_ASSISTANT_CACHE_MINUTES` reuses identical answers to save tokens (0 turns it off). `AI_ASSISTANT_EXAMPLE_PROMPTS_UI` sets the example cards, separated by `|`. Use `GEMINI_MODEL=gemini-flash-lite-latest` with `GEMINI_THINKING_BUDGET` empty (the free Flash quota is only 20 requests a day; for a thinking model set `GEMINI_THINKING_BUDGET=0` to stop billed "thinking" tokens), and `AI_ASSISTANT_MAX_OUTPUT_TOKENS` caps each reply |
| `THROTTLE_*` | Rate limits per minute: login, registration, checkout, order tracking, password reset, opening reset links, verification email and assistant messages |
| `PASSWORD_RESET_RESEND_SECONDS`, `PASSWORD_RESET_EXPIRE_MINUTES` | Password reset resend wait and link lifetime |
| `PASSWORD_MIN_LENGTH` | Minimum password length for every account (registration, reset, staff, settings). Default `6`. On a live site passwords also need mixed case, numbers and symbols |
| `EMAIL_VERIFICATION_RESEND_SECONDS`, `EMAIL_VERIFICATION_EXPIRE_MINUTES` | Wait before a customer can resend the verification email, and how long a verification link works |
| `DB_SEEDING_ENABLED` | `false` makes `--seed` create only the roles and one super admin, never demo accounts, books or orders. Keep it `false` on a live site |
| `DB_SEED_ADMIN_EMAIL`, `DB_SEED_ADMIN_PASSWORD` | The super admin seeded when `DB_SEEDING_ENABLED=false`. Skipped when empty or when the email already exists. Clear the password after the first seed |
| `QUEUE_CONVERSIONS_BY_DEFAULT` | `false` resizes book covers during the upload instead of on a queue, so no queue worker is needed |

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

> **Never run `composer serve:dev`, `php artisan app:fresh` or `php artisan migrate:fresh` on a live site.** They delete all users, orders, uploaded books and banners.

### 4. Nginx

Point the site root to the `public` directory and allow uploads larger than the app limit:

```nginx
server {
    server_name your-domain.com;
    root /var/www/notun-krisok/public;
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

Without a worker, emails are sent right after the response and book covers are resized during the upload (`QUEUE_CONVERSIONS_BY_DEFAULT=false`). Set up the worker only when `MAIL_QUEUE_ENABLED=true`: order emails then wait in the `jobs` table and are retried every `MAIL_QUEUE_BACKOFF_SECONDS` for `MAIL_QUEUE_RETRY_HOURS` while the mail server is down. With the queue on and no worker running, no order email is sent.

Create `/etc/systemd/system/notun-krisok-queue.service`:

```ini
[Unit]
Description=নতুন কৃষক queue worker
After=network.target

[Service]
User=www-data
Restart=always
WorkingDirectory=/var/www/notun-krisok
ExecStart=/usr/bin/php artisan queue:work --tries=1 --max-time=3600

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl enable --now notun-krisok-queue
```

Add the scheduler to the `www-data` crontab (`sudo crontab -u www-data -e`):

```cron
* * * * * cd /var/www/notun-krisok && php artisan schedule:run >> /dev/null 2>&1
```

### 7. Updating a live site

```bash
cd /var/www/notun-krisok
php artisan down
git pull
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan optimize
php artisan ai-assistant:embed
php artisan up
```

### cPanel (shared hosting)

The app runs on cPanel if the host provides PHP 8.4, Terminal (SSH) access and Cron Jobs.

1. **Check the server** in cPanel → Terminal:

   ```bash
   php -v
   php -i | grep disable_functions
   ```

   Free book previews need `proc_open` to be missing from `disable_functions`, whichever engine you use.

2. **Set up the preview engine.**

   - **pdf-lib (default):** in cPanel → Setup Node.js App, create an app with Node.js 20+ for the project folder. This makes Node available and shows its path, for example `/opt/alt/alt-nodejs20/root/usr/bin/node`. Check it in Terminal with `ls /opt/alt/*/root/usr/bin/node`. If the host offers no Node.js at all, use Ghostscript or a VPS.
   - **Ghostscript:** run `which gs`. If it prints nothing, the host has not installed Ghostscript and only pdf-lib can be used.

3. **Build locally**:

   ```bash
   composer install --no-dev --optimize-autoloader
   npm ci
   npm run build
   ```

   Upload the whole project, including `vendor`, `public/build` and, for pdf-lib, `node_modules` (only `node_modules/pdf-lib`, `node_modules/@pdf-lib`, `node_modules/pako` and `node_modules/tslib` are needed at runtime), to a folder outside `public_html`, for example `~/notun-krisok`.

4. **Point the domain to `public`.** In cPanel → Domains, set the document root to `notun-krisok/public`. If the main domain's root cannot be changed:

   ```bash
   rm -rf ~/public_html && ln -s ~/notun-krisok/public ~/public_html
   ```

5. **Create the database** in cPanel → MySQL Databases, then fill in `.env` as in production step 2 (Configure the environment). Set `NODE_BINARY` to the full Node.js path from step 2, or `GHOSTSCRIPT_BINARY` to the path printed by `which gs` when using Ghostscript.

6. **Run the setup** in Terminal:

   ```bash
   cd ~/notun-krisok
   php artisan key:generate
   php artisan migrate --force --seed
   php artisan storage:link
   php artisan optimize
   php artisan ai-assistant:embed
   chmod -R 775 storage bootstrap/cache
   ```

   Then create your admin account as shown in production step 3.

7. **No Cron Jobs are needed** while `MAIL_QUEUE_ENABLED=false`; keep `QUEUE_CONVERSIONS_BY_DEFAULT=false` so book covers are resized during the upload. To retry emails when the mail server is down, set `MAIL_QUEUE_ENABLED=true` and add one cron job (cPanel → Cron Jobs, every minute) that runs the worker briefly:

   ```cron
   * * * * * cd /home/USER/notun-krisok && php artisan queue:work --stop-when-empty --max-time=50 >> /dev/null 2>&1
   ```

8. **Raise upload limits** in cPanel → MultiPHP INI Editor:

   ```ini
   upload_max_filesize = 128M
   post_max_size = 128M
   max_execution_time = 300
   memory_limit = 256M
   ```

   If large uploads still fail with "413 Request Entity Too Large", ask the host to raise the web server request body limit above 100 MB.
