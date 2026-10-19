---
paths:
  - app/Models/**
  - database/**
---

# Models and database

## Models
Models hold relations, casts, accessors and scopes only, never workflows. Status and type columns cast to backed enums with `label()` and `color()`. Add `@property` PHPDoc for PHPStan.

## Migrations
How to change the schema depends on `APP_IS_LIVE` (see the rule below). Pre-launch: edit the existing `create_*` migration; only the user rebuilds with `composer serve:dev`. Index filter and sort columns, and add unique constraints for business rules.

## Snapshots
Orders snapshot names and prices (`*_snapshot`, `unit_price`, `line_total`) so later price changes never alter history.

## Factories and seeders
Every model has a factory with meaningful states (`superAdmin()`, `admin()`, `moderator()`, `editor()`, `paid()`, `physical()`, …). Demo seeding is gated by `DB_SEEDING_ENABLED`.

## Live apps get new migrations only (APP_IS_LIVE)
Before any schema change, check `APP_IS_LIVE` in `.env` (`grep '^APP_IS_LIVE=' .env`; missing means `false`).
- `false` (pre-launch): edit the existing `create_*` migration; the user rebuilds with `composer serve:dev`.
- `true` (live data): never edit or delete an existing migration and never suggest `composer serve:dev`/`migrate:fresh`. Always add a new migration (`php artisan make:migration --no-interaction`) that alters the table and migrates existing rows, with a working `down()`.
