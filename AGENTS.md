# নতুন কৃষক: agent instructions

This is the single source of instructions for any AI coding agent (Claude Code, Codex, Cursor, Copilot, Gemini…). `CLAUDE.md` imports this file. When two rules conflict, the more specific one wins: `.ai/rules/*` beats this file, and this file beats generic framework guidance.

## Project in one paragraph

Laravel 13 / PHP 8.4 bookshop selling digital (PDF) and printed books in Bangladesh. Customers browse the shop, read free previews, register, check out, then pay manually with bKash or Nagad. Staff verify the payment and the books unlock in the customer's library. Staff use the backoffice, where every staff member has a role with default permissions (Super Admin does everything) plus optional per-person permissions. Stack: Livewire 4, Flux UI 2, Tailwind 4, Fortify, Socialite, spatie permission and medialibrary, Pest 5, Larastan and Pint. Architecture and data model: `TECHNICAL.md`.

## Context map (read on demand, never all at once)

| Need | Read |
|---|---|
| Rules for the files you are about to touch | `.ai/rules/index.md`, then only the matching rule files |
| Build a new feature or a first version | `.ai/playbooks/new-feature.md` |
| Architecture, tables, flows, caching | the relevant section of `TECHNICAL.md` |
| ERD, class and sequence diagrams | the relevant section of `DIAGRAM.md` (update it when schema, relations or flows change) |
| Install, `.env` settings | `SETUP.md` |
| Every command (install, fresh, embed, tests, deploy) | `SETUP_COMMANDS.md` |
| AI assistant / RAG internals | `OUR_RAG.md` |
| Everyday tweaks (titles, links, colors, limits, .env) | `HOW_TO.md` |
| Known gaps | `INCOMPLETE.md` |

## Golden rules

1. **Services own logic.** Business logic, queries, transactions and cache live in `app/Services/{Auth,Backoffice,Frontend,Payment,Mail,AiAssistant}`, each grouped by domain (rule 13). Controllers and Livewire stay thin so a future API can reuse the services.
2. **Livewire never touches models.** Inject services in `boot()`, expose data with `#[Computed]` and pass IDs or scalars to services.
3. **Blade is markup only.** No `@php` and no queries. Data arrives through props, `compact()` or computed properties.
4. **Config through helpers.** Never call `env()` outside `config/`. Read settings through the typed helpers in `app/Helpers/config_helpers.php`.
   **Helper files stay generic.** `config_helpers.php` only maps one config key to a scalar or plain array (`bookPreviewPages()`, `paymentDefaultMethod()`). A helper that returns `bool` starts with `is` (`isGlobalOfferOnly()`, `isAiAssistantEnabled()`). `helpers.php` only holds utilities any project could copy unchanged (`formatMoney()`, `sanitizeRichText()`). Neither imports app classes (enums, models, services), queries the database or holds domain rules or table names. Put those in the service that owns the data, which callers inject.
5. **Money is whole units.** Store money as unsigned integers and round once, at the calculation. Display it with `formatMoney()`. Take the currency from `APP_CURRENCY` / `APP_CURRENCY_SYMBOL`. Never hard-code `৳` or `BDT`.
6. **Public cache is invalidated centrally.** A model whose data is cached uses `ClearsFrontendCacheTrait` and lists its `FrontendCacheKeyEnum` keys in `frontendCacheKeys()`; saving, deleting or changing its media clears them after commit, from any caller. Call `FrontendCacheKeyEnum::…->forget()` by hand only for writes that fire no model event (pivot `sync()`, bulk `query()->update()`).
7. **Validation and authorization.** Validate in the Livewire component or a Form Request. Authorize with permissions, never role names: `can:<PermissionEnum>` on routes, `@can` in Blade, policies for models. A new backoffice area adds a `PermissionEnum` case and a `config/backoffice.php` nav item (see `TECHNICAL.md` §4).
8. **Naming: the folder sets the suffix.** A class ends with the kind of the folder it lives in, and the name before the suffix says its purpose, not its storage or mechanism. `app/Services/**` → `*Service` (`BookService`, `MaintenanceModeService`, which implements Laravel's maintenance contract), `app/Traits` → `*Trait`, `app/Enums` → `*Enum`, `app/Resolvers` → `*Resolver`, `app/Interfaces` → `*Interface`, `app/Http/Controllers` → `*Controller`, `app/Http/Middleware` → `*Middleware`, `app/Mail` → `*Mail`, `app/Console/Commands` → `*Command`, `app/Policies` → `*Policy`, `app/Providers` → `*ServiceProvider` (Laravel service providers only). A class whose kind has no folder takes the suffix of the folder it is placed in, or gets its own folder after asking (rule 10). Interface implementations picked by a resolver (an AI vendor, a payment gateway, the maintenance driver) are services: `AiAssistantGeminiService`, `ManualPaymentGatewayService`, `MaintenanceModeService`. Middleware is named after its route alias (`customer` → `CustomerMiddleware`). Only models, Livewire components (`Index`), Livewire concerns (`InteractsWith*`) and framework-scaffolded classes (`app/Actions/Fortify`, `app/Concerns`) are exempt. Config keys name the purpose too (`APP_MAINTENANCE_DRIVER=system`, not `database`). Enum cases are TitleCase.
9. **Migrations follow `APP_IS_LIVE`.** Check it in `.env` first. `false` (pre-launch): edit the existing migration; the schema is rebuilt with `composer serve:dev`, which only the user runs. `true` (live): never edit existing migrations or run fresh; always add a new migration that alters the table and migrates existing rows.
10. **No new dependencies or base folders** without asking.
11. **Code to the installed versions.** Check the version in `composer.lock` or `package.json` first, then use that version's current docs (Boost `search-docs`), APIs and practices. Never use features from a newer major the app doesn't run (Laravel 13 means no Laravel 14 APIs). Prefer the modern approach over deprecated or legacy ones, and don't add code for unsupported versions.
12. **Traits are complete, reusable features.** Create a trait only when it groups every method one feature needs, isn't tied to one caller, and is used by at least two unrelated classes, swaps with a same-shaped trait, or is a module planned for reuse (`MediaTrait`, `FrontendCacheTrait`, `ThrottleTrait`, `PdfLibTrait`/`GhostscriptTrait`). We build modular on purpose: a trait with one user today is fine when it is a scalable, loosely coupled module meant for future features (`ProductAiAssistantTrait`, `MpdfTrait`). Otherwise put the method in the owning service or the existing trait for that feature. Livewire concerns in `app/Livewire/Concerns` follow the same rule (`InteractsWithTable`).
13. **Group by domain sub-folder.** Never drop a class at the root of `Services/Backoffice`, `Services/Frontend`, `Enums`, `Traits`, `Resolvers`, `Http/Middleware`, `Mail` or `Console/Commands`. Put it in the domain sub-folder it belongs to, and reuse the existing domains before adding one: Catalog, Content, Sales/Order, People/User, System, Notification, AiAssistant, plus Shop and Account on the frontend, Pdf, Payment, Auth and Access where they apply. Example: `app/Services/Backoffice/Catalog/BookService.php`, `app/Enums/Order/OrderStatusEnum.php`. A new domain gets its own sub-folder even with one class. Models, policies, providers, `Actions/Fortify` and `Concerns` stay flat, because Laravel and the database rely on their class names. Moving a class later means updating its namespace, every `use`, config entries, `phpstan.neon` paths and the docs, and draining the queue first if it holds queued mail.

## Working loop

Each task follows the same loop. Stop and ask only when a decision belongs to the user.

1. **Scope:** restate the task in one line and do only that. Ask before you expand scope.
2. **Locate:** `grep` or `rg` for the symbol or route first, then open only the parts you need. Read one sibling file to copy its shape.
3. **Rules:** check `.ai/rules/index.md` for the paths you will touch.
4. **Act:** make the smallest complete change, matching the sibling file's structure and idioms.
5. **Verify:**
   - Run `vendor/bin/pint --dirty --format agent`.
   - Run only the affected test file or `--filter`, never the full suite unless the change is app-wide.
   - Fix the failures and repeat until green.
6. **Report:** list the files changed, the tests run and anything left open. Keep it short; no plan narration.

## Token budget

- Never read whole large files or directories "for context". Use the context map, grep, and the `offset`/`limit` options of file reads.
- Don't re-read a file you just edited, and don't reprint code in the reply.
- Prefer the Laravel Boost MCP tools (`database-schema`, `search-docs`, `last-error`, `browser-logs`) to guesswork or tinker.
- Batch independent reads and searches in parallel.
- Unsure of an API? Check the installed version and run Boost `search-docs` first (golden rule 11).
- Load a skill from `.claude/skills` only when you're unsure of that package's API or are building something new with it, not for small edits. Skills are large.
- Scaffold with `php artisan make:<type> --no-interaction`. No verification scripts or tinker when a test can prove it.
- Hand broad codebase sweeps to a sub-agent only when asked. Keep the main context for the change itself.

## Tests (critical)

The shell exports `.env`. A plain `php artisan test` would run against MySQL and **wipe the dev database**. Always use this command:

```bash
php artisan optimize:clear && env $(grep -oE '^[A-Z_][A-Z0-9_]*' .env | sed 's/^/-u /') php artisan test --compact <path-or---filter>
```

- Never run `composer serve:dev`, `migrate:fresh`, `app:fresh` or seeders against the dev database.
- Add or update Pest feature tests for behaviour changes, not for copy or styling. One file per feature in `tests/Feature/{Auth,Backoffice,Frontend,Settings}`.
- Optional type check on changed files: `vendor/bin/phpstan analyse <files>`. Some older warnings already exist; don't add new ones.

## Style

- Follow the existing architecture, folder layout and naming. New files go where their siblings live. Write like the original developer and copy the shape of the nearest sibling file.
- Keep code simple and direct. No speculative abstractions, extra layers, config flags, or helpers for one-off use. Write the least code that solves the task.
- Keep queries efficient. Eager-load relations to avoid N+1, select only the columns you need, use `withCount`/`withSum` and aggregates instead of loading collections, and paginate lists.
- No prose comments. Keep only the PHPDoc types PHPStan needs, with array shapes.
- User-facing text (`__()` strings, Blade copy, messages, mail, PDFs, seeded content) never uses a semicolon or an em dash. Split the sentence or use a comma, and show empty values as `-`.
- Frontend: Flux UI and Tailwind, with Alpine only for client-only behaviour. Build from components: reuse `resources/views/components` before you write markup, and extract a Blade component when markup repeats. Link with named routes and `wire:navigate`.
- Run `npm run build` when a Tailwind change doesn't show.
