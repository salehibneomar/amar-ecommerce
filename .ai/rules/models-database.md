---
paths:
  - app/Models/**
  - database/**
---

# Models and database

## Models
Models hold relations, casts, accessors and scopes only, never workflows. Status and type columns cast to backed enums with `label()` and `color()`. Add `@property` PHPDoc for PHPStan.

## Product types
A child type extends `Product` and declares `$detailTable` and `$detailColumns` (`Shirt extends Product`, table `shirts`, keyed by `product_id`). `Product` joins, scopes and saves the detail table, and hydrates each row by its `type`, so generic code queries `Product` and never names a subclass. The detail table never repeats `products` columns (no `id` or timestamps). Polymorphic rows (`media`, embeddings, `seo_metas`) always store `App\Models\Product` (`Product::getMorphClass()`).

## Migrations
Index filter and sort columns, and add unique constraints for business rules.

## Snapshots
Orders snapshot names and prices (`*_snapshot`, `unit_price`, `line_total`) so later price changes never alter history.

## Factories and seeders
Every model has a factory with meaningful states (`superAdmin()`, `admin()`, `moderator()`, `editor()`, `paid()`, `stock()`, `outOfStock()`, …). Demo seeding is gated by `DB_SEEDING_ENABLED`.

## Fresh migrations only when allowed (DEV_CAN_MIGRATE_FRESH)
Before any schema change, check `DEV_CAN_MIGRATE_FRESH` in `.env` (`grep '^DEV_CAN_MIGRATE_FRESH=' .env`; missing means `false`).
- `true` (pre-launch): edit the existing `create_*` migration; the user rebuilds with `php artisan app:fresh --seed`.
- `false` (real data): never edit or delete an existing migration and never suggest `app:fresh`/`migrate:fresh`. Always add a new migration (`php artisan make:migration --no-interaction`) that alters the table and migrates existing rows, with a working `down()`.
