---
paths:
  - routes/**
  - app/Http/**
  - app/Policies/**
  - bootstrap/app.php
---

# Routes, controllers and access

## Controllers are thin
A controller calls a service and returns a view with `compact()`, or returns a file response. Page shells host a Livewire component.

## Access
- Storefront account pages use `auth`, `verified`, `not-blocked` and `customer`.
- Backoffice pages sit under `manage/` and use `backoffice`, plus `can:<PermissionEnum value>`. Never check role names.
- Signed URLs protect order confirmation, PDF streams and email verification.

## Routes
Every route is named and grouped with `Route::controller()->prefix()->name()`, matching `routes/web.php`. A new staff area adds a `PermissionEnum` case.
