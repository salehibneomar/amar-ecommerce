---
paths:
  - routes/**
  - app/Http/**
  - app/Policies/**
  - bootstrap/app.php
---

# Routes, controllers and access

## Controllers are thin
Every page, settings pages included, is a controller method that returns a page shell (`resources/views/{backoffice,frontend}/<area>/<page>.blade.php`) hosting a class-based Livewire component, or returns a file response. A controller calls a service and passes data with `compact()`. Never route to a Livewire component directly (`Route::livewire`) and never build single-file (`⚡`) pages.

## Route files
`routes/web.php` only requires the route files. Each file holds one area:
- `routes/frontend/<domain>.php`: `shop` (storefront pages and cart), `payment` (gateway checkout, return and webhooks), `auth` (customer login, register, password reset, email verification and social login), `account` (signed-in customer pages under `user/`).
- `routes/backoffice/<domain>.php`: `sales`, `catalog`, `content`, `people`, `marketing`, `system`. They load inside the `adminRoutePrefix()` group in `web.php`. A new admin module goes in the file of its domain, and a new domain gets a new file required from `web.php`.

Every route is named and grouped with `Route::controller()->prefix()->name()`. Each backoffice file declares its own middleware stack, starting with `auth`, `verified`, `not-blocked`, `backoffice`.

## Access
- Storefront account pages use `auth`, `verified`, `not-blocked` and `customer`.
- Backoffice modules sit under `manage/` (`manage.` names) and add `can:<PermissionEnum value>`. Settings pages sit under `settings/` in `routes/backoffice/system.php`. Never check role names. A new staff area adds a `PermissionEnum` case.
- Sensitive pages add `password.confirm`: security, system, branding, offers, roles, analytics trackers and SEO. Keep it on any page that changes access, site-wide output or scripts.
- Signed URLs protect order confirmation, PDF streams and email verification.
