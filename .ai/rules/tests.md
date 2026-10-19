---
paths:
  - tests/**
---

# Tests

## Safe runner only
`php artisan optimize:clear && env $(grep -oE '^[A-Z_][A-Z0-9_]*' .env | sed 's/^/-u /') php artisan test --compact <path>`. A plain run wipes the dev database.

## Shape
Use Pest feature tests, one file per feature in `tests/Feature/{Auth,Backoffice,Frontend,Settings}`. Name each test with a sentence that states the behaviour. Drive Livewire with `Livewire::actingAs($user)->test(Component::class)`. Use factory states rather than manual attributes.

## Scope
Test the changed behaviour and its key failure mode (validation, authorization, rounding), nothing more. Don't test copy or styling. Assert money as integers.
