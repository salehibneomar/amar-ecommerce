# AmarEcommerce Lite: agent instructions

Single source of instructions for every AI coding agent. `CLAUDE.md` imports it. The more specific rule wins: `.ai/rules/*` beats this file, which beats generic framework guidance.

## Project

Laravel 13 / PHP 8.4 ecommerce boilerplate that client shops start from, so shared code stays generic. It ships as a simple generic shop: one flat `products` table whose base `Product` model can host child product types, up to five images per product (the first is the thumbnail), tracked stock, and Stripe/SSLCommerz-style orders (order status, payment status, a `payments` row per attempt). Staff turn payment gateways on and set their checkout order in Settings > Payment Gateways (`cod`, `manual` for bKash or Nagad verified by staff, `stripe`, `sslcommerz`, `bkash`). `PAYMENT_GATEWAYS` only seeds the first install, and no enabled gateway means no payment step. Staff use the backoffice with role permissions (Super Admin does everything) plus per-person permissions. Stack: Livewire 4, Flux UI 2, Tailwind 4, Fortify, Socialite, spatie permission and medialibrary, Pest 5, Larastan, Pint.

## Agent mode (DEV_AI_AGENT)

Read once per session: `grep -E '^DEV_(AI_AGENT|CAN_MIGRATE_FRESH)=' .env` (missing means `free` and `false`).

- **Docs:** `README.md` and its guides are for buyers. Never read or update them unless asked.

- **free (lean):** follow Golden rules, Tests and Replies only. Read a rule file only when the change clearly depends on it. Smallest change, one Pint run, one targeted test run. Skip PHPStan, skills, sub-agents and screenshots unless asked. If a fix fails twice, stop and ask.
- **paid (full):** the whole file, including the Working loop and the context map. Still no needless iterations: at most three fix attempts before asking.

## Context map (read on demand, never all at once)

| Need | Read |
|---|---|
| Rules for files you touch | `.ai/rules/index.md`, then only matching files |
| New feature or first version | `.ai/playbooks/new-feature.md` |

## Golden rules

1. **Services own logic.** Queries, transactions, cache and side effects live in `app/Services/{Auth,Backoffice,Frontend,Payment,Mail,AiAssistant}/<Domain>`. Controllers and Livewire stay thin.
2. **Livewire never touches models.** Inject services in `boot()`, expose data with `#[Computed]`, pass IDs or scalars.
3. **Blade is markup only.** No `@php`, no queries.
4. **Config through helpers.** No `env()` outside `config/`. Read settings with typed helpers in `app/Helpers/config_helpers.php`. Helper files stay generic (`.ai/rules/config-helpers.md`).
5. **Money is whole units.** Unsigned integers, round once, show with `formatMoney()`, currency from config. Never hard-code `৳` or `BDT`.
6. **Public cache clears centrally** through `ClearsFrontendCacheTrait` and `frontendCacheKeys()`. Call `FrontendCacheKeyEnum::…->forget()` only for writes with no model event (`sync()`, bulk `update()`).
7. **Validate** in the Livewire component or a Form Request. **Authorize with permissions**, never role names (`can:<PermissionEnum>`, `@can`, policies). A new backoffice area adds a `PermissionEnum` case and a `config/backoffice.php` nav item.
8. **Naming and folders:** the folder sets the class suffix, and classes sit in a domain sub-folder (`.ai/rules/structure.md`).
9. **Migrations follow `DEV_CAN_MIGRATE_FRESH`** (`.ai/rules/models-database.md`). `true`: edit the existing migration. `false` or missing: only add new migrations.
10. **No new dependencies or base folders** without asking.
11. **Code to installed versions.** Check `composer.lock` / `package.json`, use Boost `search-docs`, no APIs from a newer major, no deprecated approaches.
12. **Traits are complete reusable features** (`.ai/rules/structure.md`).

## Working loop

1. **Scope:** do only the task. Ask before expanding it.
2. **Locate:** `rg` the symbol first, open only the needed lines, read one sibling to copy its shape.
3. **Rules:** check `.ai/rules/index.md` for the paths you touch.
4. **Act:** smallest complete change, matching the sibling.
5. **Verify:** `vendor/bin/pint --dirty --format agent`, then only the affected test file or `--filter`. Optional: `vendor/bin/phpstan analyse <files>` (don't add new warnings).
6. **Report:** as in Replies.

## Tests (critical)

The shell exports `.env`, so a plain `php artisan test` hits MySQL and **wipes the dev database**. Always run:

```bash
php artisan optimize:clear && env $(grep -oE '^[A-Z_][A-Z0-9_]*' .env | sed 's/^/-u /') php artisan test --compact <path-or---filter>
```

Never run `composer serve:dev`, `migrate:fresh`, `app:fresh` or seeders against the dev database. Test shape: `.ai/rules/tests.md`.

## Replies

- Short bullet points in plain, easy English. No intro, recap or plan narration.
- Only what changed, what ran, and what is left or needs the user's decision.
- No code in the reply unless asked. Point to files as links.
- Between tool calls, write nothing or one short line.

## Token budget

- **One task per session.** Every turn re-sends the whole conversation.
- **Warn when the session gets big.** In one line at the end of a reply, tell the user to run `/compact` (same task) or `/clear` (new task) when an unrelated task starts, the session passes about 20 turns, many large files or long outputs were read, or the context is about half full.
- Keep tool output small: `head`/`tail`, `--compact`, never print whole files, diffs or `git status` lists.
- Never read whole large files or folders "for context". Use grep and `offset`/`limit`. Batch independent reads.
- Don't re-read a file you just edited. Don't reprint code.
- Test once: affected tests while working, the full suite only once at the end of an app-wide change.
- No screenshots, browser runs or dev servers unless asked.
- Prefer Boost tools (`database-schema`, `search-docs`, `last-error`) to guessing or tinker.
- Load a skill only when unsure of a package API or building something new with it.
- Scaffold with `php artisan make:<type> --no-interaction`. No verification scripts when a test can prove it.
- Sub-agents only when asked.

## Style

- Copy the nearest sibling's structure and naming. Least code that solves the task. No speculative abstractions, flags or one-off helpers.
- Efficient queries: eager-load, select needed columns, `withCount`/`withSum`, paginate lists.
- No prose comments. Keep only PHPDoc PHPStan needs, with array shapes.
- Labels, buttons, menus, table headings and detail labels use Title Case (`Show Results`, `Last Updated by`). Sentences, hints and placeholders use sentence case.
- User-facing text never uses a semicolon or an em dash. Empty values use `appEmptyValue()` or `<x-ui.empty-value />`, never a hard-coded `-`.
- UI rules: `.ai/rules/views.md`.
