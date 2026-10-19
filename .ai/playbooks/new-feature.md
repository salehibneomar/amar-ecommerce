# Playbook: first version of a feature

Use this playbook to add a feature, an admin screen, a storefront page or a new model. A first version (v1) is the smallest slice that works end to end, is tested and follows the architecture. Polish comes in later passes.

## 0. Frame it (no code yet)

Write down three lines. Ask the user only if one of them is unclear:

- **Outcome:** what a user can do after this ships.
- **Actor and access:** guest, customer or staff (which `PermissionEnum`).
- **Out of scope for v1:** what you are deliberately leaving out.

## 1. Find the closest sibling and copy its shape

| Building | Copy from |
|---|---|
| Admin CRUD screen | Faqs: `FaqService`, `Livewire/Backoffice/Faqs/Index.php`, `components/backoffice/faqs/index.blade.php`, `FaqController`, `backoffice/faqs/index.blade.php` |
| Admin screen with media | Banners or Books |
| Storefront page | `PageController` + `PageService` + `frontend/<page>/index.blade.php` |
| Interactive storefront screen | `Livewire/Frontend/Cart/*` + `CartService` |
| Report or read-only stats | `SalesReportService` + `Livewire/Backoffice/SalesReports` |

Read one sibling, not the whole folder.

## 2. Build bottom-up (one layer at a time)

Each step has the files to touch and the rule to check:

1. **Enum(s):** `app/Enums/<Domain>/<Name>StatusEnum.php`, with TitleCase cases, `label()` and `color()`.
2. **Schema:**
   - Add a new `create_<table>` migration, or edit the existing migration of a table that already exists.
   - Money columns are `unsignedInteger`.
   - Index the filter and sort columns.
   - Rule: `.ai/rules/models-database.md`.
3. **Model and factory:** `php artisan make:model <Name> -f --no-interaction`. Add casts, relations, `@property` PHPDoc and useful factory states. Add a seeder only if demo data helps.
4. **Service:**
   - `app/Services/{Backoffice|Frontend}/<Domain>/<Name>Service.php` holds every query and write (reuse an existing domain sub-folder first).
   - Admin writes forget the affected `FrontendCacheKeyEnum` keys, and public reads are cached.
   - Rule: `.ai/rules/services.md`.
5. **Access:**
   - Add a route under the correct group in `routes/web.php`, with a name and middleware (`can:` with a new `PermissionEnum` case, plus a nav item in `config/backoffice.php`).
   - Rule: `.ai/rules/http-routes.md`.
6. **Controller and page shell:**
   - `Http/Controllers/{Backoffice|Frontend}/<Name>Controller.php` returns the view.
   - `resources/views/{backoffice|frontend}/<area>/index.blade.php` hosts the Livewire component.
7. **Livewire + view:**
   - `app/Livewire/{Backoffice|Frontend}/<Area>/Index.php`, with services in `boot()` and data in `#[Computed]`.
   - `resources/views/components/{backoffice|frontend}/<area>/index.blade.php`, built from the existing `backoffice/management/*` / `frontend/*` components.
   - Rules: `.ai/rules/livewire.md` and `.ai/rules/views.md`.
8. **Navigation:**
   - Backoffice: `x-backoffice.layout.nav-item` in `components/backoffice/layout/sidebar.blade.php`, gated like the route.
   - Storefront: `components/frontend/layout/nav-links.blade.php`.
9. **Config:** new settings go through `.env.example` → `config/*.php` → a helper in `config_helpers.php`. Rule: `.ai/rules/config-helpers.md`.

## 3. Prove it

- Add `tests/Feature/{Backoffice|Frontend}/<Name>Test.php` covering:
  - the happy path;
  - one validation failure;
  - one authorization failure (guest, or a user without the permission).
- Run `vendor/bin/pint --dirty --format agent`, then the safe test command (see `AGENTS.md`) on that file and any sibling tests you touched.
- If the change has UI, run `npm run build` (or ask the user to run `composer dev`) and check the page.

## 4. Close the loop

- Update `TECHNICAL.md` only where the feature changes the architecture, the tables or the cache keys. Add new `.env` keys to `SETUP.md`. Remove fixed items from `INCOMPLETE.md`, and add any deliberate v1 gaps to it.
- Report in three lines: what changed, what tests ran, and what is left for v2.

## v1 checklist

- [ ] No model import in Livewire, no `@php` in Blade, no `env()` outside config
- [ ] Money is integers, displayed with `formatMoney()`
- [ ] Lists paginated, relations eager-loaded, public reads cached and forgotten on write
- [ ] Every route named and protected, every string in `__()`
- [ ] Pint clean, feature test green
