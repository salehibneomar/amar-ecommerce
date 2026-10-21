# Playbook: first version of a feature

A v1 is the smallest slice that works end to end, is tested and follows the architecture. Polish comes later.

## 0. Frame it (no code yet)
Write three lines, and ask only if one is unclear:
- **Outcome:** what a user can do after this ships.
- **Actor and access:** guest, customer or staff (which `PermissionEnum`).
- **Out of scope for v1.**

## 1. Copy the closest sibling (read one, not the folder)

| Building | Copy from |
|---|---|
| Admin CRUD | Faqs: `FaqService`, `Livewire/Backoffice/Faqs/Index.php`, `components/backoffice/faqs/index.blade.php`, `FaqController`, `backoffice/faqs/index.blade.php` |
| Admin with media | Banners, or Products for a multi-image gallery |
| Storefront page | `PageController` + `PageService` + `frontend/<page>/index.blade.php` |
| Interactive storefront | `Livewire/Frontend/Cart/*` + `CartService` |
| Report or stats | `SalesReportService` + `Livewire/Backoffice/SalesReports` |

## 2. Build bottom-up (read each layer's rule file from `.ai/rules/index.md`)
1. **Enum:** `app/Enums/<Domain>/<Name>Enum.php`, TitleCase cases, `label()`, `color()`.
2. **Schema:** new `create_<table>` migration (or edit per `DEV_CAN_MIGRATE_FRESH`). Money `unsignedInteger`, index filter and sort columns.
3. **Model + factory:** `php artisan make:model <Name> -f --no-interaction`, casts, relations, `@property` PHPDoc, factory states. Seeder only if demo data helps.
4. **Service:** `app/Services/{Backoffice|Frontend}/<Domain>/<Name>Service.php` holds every query and write. Public reads cached, cache cleared through the model.
5. **Access:** named route in its `routes/web.php` group with `can:` and a new `PermissionEnum` case, plus a `config/backoffice.php` nav item.
6. **Controller + page shell:** `Http/Controllers/{Backoffice|Frontend}/<Name>Controller.php` returns `resources/views/{backoffice|frontend}/<area>/index.blade.php`, which hosts the Livewire component.
7. **Livewire + view:** `app/Livewire/{Backoffice|Frontend}/<Area>/Index.php` and `resources/views/components/{backoffice|frontend}/<area>/index.blade.php`, built from existing components.
8. **Navigation:** backoffice sidebar `x-backoffice.layout.nav-item` gated like the route, or storefront `components/frontend/layout/nav-links.blade.php`.
9. **Config:** `.env.example` → `config/*.php` → helper in `config_helpers.php`.

## 3. Prove it
- `tests/Feature/{Backoffice|Frontend}/<Name>Test.php`: happy path, one validation failure, one authorization failure.
- Pint, then the safe test command from `AGENTS.md` on that file only.
- UI change: `npm run build` once.

## 4. Close the loop
- Unless `DEV_SKIP_OPTIONAL_DOCS=true`: update `TECHNICAL.md` / `DIAGRAM.md` only for new tables, flows or cache keys. New `.env` keys go in `SETUP.md`. Update `INCOMPLETE.md` with fixed items and v1 gaps.
- Report in three bullets: changed, tested, left for v2.

## v1 checklist
- [ ] No model in Livewire, no `@php` in Blade, no `env()` outside config
- [ ] Money integers, shown with `formatMoney()`
- [ ] Lists paginated, relations eager-loaded, public reads cached
- [ ] Routes named and protected, strings in `__()`
- [ ] Pint clean, feature test green
