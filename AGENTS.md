# AmarEcommerce Lite: agent instructions

The one instruction file for every AI coding agent (`CLAUDE.md` imports it). It loads on every turn, so it stays short. The more specific rule wins: `.ai/rules/*` beats this file, which beats generic framework guidance.

## Project

Laravel 13 / PHP 8.4 ecommerce boilerplate that client shops start from, so shared code stays generic. Stack: Livewire 4, Flux UI 2, Tailwind 4, Fortify, Socialite, spatie permission and medialibrary, Pest 5, Larastan, Pint.

- **Catalog:** one `products` table. `Product` can host child types (`Shirt extends Product` with its own detail table), hydrated by the `type` column. Up to five images per product (the first is the thumbnail) and tracked stock.
- **Orders:** order status, payment status and a `payments` row per attempt. Gateways (`cod`, `manual`, `stripe`, `sslcommerz`, `bkash`) are turned on and ordered in Settings > Payment Gateways. `PAYMENT_GATEWAYS` only seeds the first install. No enabled gateway means no payment step.
- **Backoffice:** role permissions (Super Admin does everything) plus per-person permissions.

## Golden rules

1. **Services own logic.** Queries, transactions, cache and side effects live in `app/Services/<Area>/<Domain>/*Service.php`. Controllers and Livewire stay thin.
2. **Livewire never touches models.** Inject services in `boot()`, expose data with `#[Computed]`, pass IDs or scalars.
3. **Blade is markup only.** No `@php`, no queries. Reuse the existing `x-ui`, Flux and layout components.
4. **Config through helpers.** No `env()` outside `config/`. Read settings with the typed helpers in `app/Helpers/config_helpers.php`.
5. **Money is whole units.** Unsigned integers, round once, show with `formatMoney()`, currency from config. Never hard-code `৳` or `BDT`.
6. **Public cache clears centrally** through `ClearsFrontendCacheTrait` and `frontendCacheKeys()`. Call `FrontendCacheKeyEnum::…->forget()` only for writes with no model event (`sync()`, bulk `update()`).
7. **Validate** in the Livewire action or a Form Request. **Authorize with permissions**, never role names. A new backoffice area adds a `PermissionEnum` case and a `config/backoffice.php` nav item.
8. **The folder sets the class suffix** and classes sit in a domain sub-folder (`Services/Backoffice/Catalog/ProductService.php`).
9. **Schema changes follow `DEV_CAN_MIGRATE_FRESH`** in `.env` (missing means `false`). `true`: edit the existing migration. `false`: add a new migration that also moves existing rows.
10. **No new dependencies, base folders or architecture changes** without asking.
11. **Code to installed versions.** Check `composer.lock` or `package.json` and use Boost `search-docs`. No APIs from a newer major, nothing deprecated.
12. **Free hosting stays supported.** No Terminal, symlinks, cron or queue worker required. Paid or private files stay on the `local` disk.

## Rules on demand

Open `.ai/rules/index.md` and read only the rule files whose trigger matches the change. A new feature or first version follows `.ai/playbooks/new-feature.md`. `README.md`, `SETUP.md` and `docs/` are for buyers: don't read or edit them unless asked.

## Working loop

1. **Scope:** do only the task. Ask before expanding it.
2. **Locate:** `rg` the symbol, open only the lines you need, read one sibling and copy its shape.
3. **Act:** the smallest complete change.
4. **Verify:** `vendor/bin/pint --dirty --format agent`, then only the affected test file or `--filter`.
5. **Stop:** after two failed fix attempts, explain and ask.

## Tests (critical)

The shell exports `.env`, so a plain `php artisan test` hits MySQL and **wipes the dev database**. Always run:

```bash
php artisan optimize:clear && env $(grep -oE '^[A-Z_][A-Z0-9_]*' .env | sed 's/^/-u /') php artisan test --compact <path-or---filter>
```

Never run `composer serve:dev`, `migrate:fresh`, `app:fresh` or seeders against the dev database.

## Token budget

- One task per session. Warn in one line (`/compact` same task, `/clear` new task) when the session passes about 20 turns or many large files were read.
- Search before reading. Use `rg`, `offset`/`limit` and `head`/`tail`. Never read whole folders, large files, diffs or `git status` lists "for context".
- Batch independent reads. Don't re-read a file you just edited.
- Prefer Boost tools (`database-schema`, `search-docs`, `last-error`) to guessing or tinker.
- Load a skill only when unsure of a package API. No sub-agents, screenshots, browsers or dev servers unless asked.
- Scaffold with `php artisan make:<type> --no-interaction`. Prove behavior with a test, not a throwaway script.
- Run the full suite only once, at the end of an app-wide change.

## Style

- Copy the nearest sibling's structure and naming. Least code that solves the task. No speculative abstractions, flags or one-off helpers.
- Efficient queries: eager-load, select needed columns, `withCount`/`withSum`, paginate lists.
- No prose comments. Keep only the PHPDoc PHPStan needs, with array shapes.
- Labels, buttons, menus and table headings use Title Case. Sentences, hints and placeholders use sentence case. Every string goes through `__()`.
- User-facing text never uses a semicolon or an em dash. Empty values use `appEmptyValue()` or `<x-ui.empty-value />`.

## Replies

- Short bullets in plain English. No intro, recap or plan narration.
- Only what changed, what ran, and what needs the user's decision. Link files, no code unless asked.
- Between tool calls, write nothing or one short line.
