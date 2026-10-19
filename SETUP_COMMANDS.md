# Setup commands

Every command this project needs, in one place. Each section says when to run it. For explanations and `.env` settings, see [SETUP.md](SETUP.md).

> **Never run `composer serve:dev`, `php artisan app:fresh` or `php artisan migrate:fresh` on a live site.** They delete every user, order, uploaded book and banner.

## Contents

- [First install (local)](#first-install-local)
- [Everyday development](#everyday-development)
- [Database](#database)
- [AI assistant](#ai-assistant)
- [Tests and code quality](#tests-and-code-quality)
- [Caches](#caches)
- [Production: first install](#production-first-install)
- [Production: update a live site](#production-update-a-live-site)
- [cPanel](#cpanel)
- [Composer shortcuts](#composer-shortcuts)

## First install (local)

```bash
git clone <repository-url> amar-ecommerce-lite
cd amar-ecommerce-lite
composer install
npm install
cp .env.example .env
php artisan key:generate
# edit .env: database, mail, GEMINI_API_KEY …
php artisan migrate --seed
php artisan storage:link
php artisan ai-assistant:embed
npm run build
```

`composer setup` runs the install, key, migration and build steps in one go.

## Everyday development

```bash
composer dev          # web server, log viewer and Vite together (http://localhost:8200)
npm run build         # rebuild assets when a Tailwind change doesn't show
php artisan queue:work   # only when MAIL_QUEUE_ENABLED=true: sends queued emails and retries failed ones
php artisan down / up    # maintenance mode (same setting as Settings → System)
```

## Database

```bash
php artisan migrate               # apply new migrations (keeps data)
composer serve:dev                # local only: wipe media + rebuild the database with demo data + embed books, then start the dev servers
php artisan migrate:status        # see which migrations ran
```

`composer serve:dev` runs, in order:

```bash
php artisan optimize:clear
php artisan debugbar:clear
php artisan blaze:trace:clear
php -r "array_map('unlink', glob('storage/logs/*.log'));"
php artisan app:fresh --seed       # media-library:clear, remove media folders, migrate:fresh --seed
php artisan storage:link --force   # point public/storage at this project (fixes broken image previews after a folder move or rename)
php artisan optimize
php artisan ai-assistant:embed     # AI search vectors for the seeded books
composer run dev                   # start the dev servers (php artisan dev)
```

## AI assistant

```bash
php artisan ai-assistant:embed             # embed new or changed books (skips unchanged ones)
php artisan ai-assistant:embed --sleep=0   # no pause between calls (paid quota or few books)
php artisan config:clear                   # after editing AI_ASSISTANT_* or GEMINI_* in .env
```

Run `ai-assistant:embed` after the first install, after `composer serve:dev`, after changing `GEMINI_EMBEDDING_MODEL` or `GEMINI_EMBEDDING_DIMENSIONS`, and after bulk imports. Books saved in the backoffice are embedded automatically. Without `GEMINI_EMBEDDING_MODEL` the command only prints a notice, and search uses keywords. How it works: [OUR_RAG.md](OUR_RAG.md).

## Tests and code quality

Your shell exports `.env`, so a plain `php artisan test` would run against MySQL and **wipe the dev database**. Always use:

```bash
php artisan optimize:clear && env $(grep -oE '^[A-Z_][A-Z0-9_]*' .env | sed 's/^/-u /') php artisan test --compact
php artisan optimize:clear && env $(grep -oE '^[A-Z_][A-Z0-9_]*' .env | sed 's/^/-u /') php artisan test --compact tests/Feature/Frontend/AiAssistantTest.php
```

```bash
vendor/bin/pint --dirty           # fix code style of changed files
composer lint:check               # check style without changing files
composer types:check              # PHPStan
vendor/bin/phpstan analyse <files>
```

## Caches

```bash
php artisan optimize              # cache config, routes, views (production)
php artisan optimize:clear        # clear them all (before tests, after .env edits)
php artisan config:clear          # config only
php artisan view:clear            # compiled Blade views only
```

## Production: first install

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
cp .env.example .env && php artisan key:generate   # then fill in .env
php artisan migrate --force --seed
php artisan storage:link
php artisan optimize
php artisan ai-assistant:embed
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

Create the first admin once:

```bash
php artisan tinker --execute 'App\Models\User::forceCreate(["name" => "Admin", "email" => "you@your-domain.com", "password" => "a-strong-password", "email_verified_at" => now()])->assignRole("super_admin");'
```

## Production: update a live site

```bash
php artisan down
php artisan queue:work --stop-when-empty   # only when MAIL_QUEUE_ENABLED=true: send waiting emails before code changes
git pull
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan optimize
php artisan ai-assistant:embed
php artisan up
```

## cPanel

Build locally, upload the project outside `public_html`, then in cPanel → Terminal:

```bash
php -v                                   # PHP 8.4 required
php -i | grep disable_functions          # proc_open must not be listed (book previews)
cd ~/amar-ecommerce-lite
php artisan key:generate
php artisan migrate --force --seed
php artisan storage:link
php artisan optimize
php artisan ai-assistant:embed
chmod -R 775 storage bootstrap/cache
```

With `MAIL_QUEUE_ENABLED=true`, add a cPanel → Cron Jobs entry (every minute):

```cron
* * * * * cd ~/amar-ecommerce-lite && php artisan queue:work --stop-when-empty --max-time=50 >> /dev/null 2>&1
```

If the domain root can't point to `public`:

```bash
rm -rf ~/public_html && ln -s ~/amar-ecommerce-lite/public ~/public_html
```

## Composer shortcuts

| Command | Runs |
|---|---|
| `composer setup` | install, `.env`, key, migrate, `npm install`, build |
| `composer dev` | `php artisan dev` (server, logs, Vite) |
| `composer serve:dev` | local database rebuild with demo data and AI embeddings, then starts the dev servers (see [Database](#database)) |
| `composer lint` / `composer lint:check` | Pint fix / check |
| `composer types:check` | PHPStan |
| `composer test` | config clear, lint check, PHPStan, tests. Unset the exported `.env` first, as in [Tests](#tests-and-code-quality) |
