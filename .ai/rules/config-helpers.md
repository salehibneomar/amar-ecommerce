---
paths:
  - config/**
  - app/Helpers/**
  - .env.example
---

# Config and helpers

## Settings flow
`.env` → `config/<file>.php` → typed helper in `app/Helpers/config_helpers.php` → code. Nothing outside `config/` calls `env()`. Every new key goes into `.env.example` (and the settings table in `SETUP.md`) with a safe default in the config file.

## Where things live
- App-wide settings: `config/app.php`, including `currency` and `currency_symbol`.
- Shop contact channels and the order prefix: `config/shop.php`.
- Enabled payment gateways, the gateway class map, manual payment numbers and gateway keys: `config/payment.php` (read keys with `paymentGatewaySetting('<gateway>', '<key>')`).
- Rate limits: `config/throttle.php`.
- Media rules: `config/product.php`, `config/category.php`, `config/banner.php`, `config/user.php` and `config/system.php`. Leave out `disk` so files follow `MEDIA_DISK` (`config('filesystems.media')`: `public`, `assets` or `r2`). Add `disk` only to send one media type somewhere else. Never hard-code `Storage::disk('public')` or `/storage/` URLs for media; go through `MediaTrait` and `mediaDisk()`.

Universal helpers such as `formatMoney()`, `formatDateTime()` and `sanitizeRichText()` live in `app/Helpers/helpers.php` and never mention a specific page.

## Helper files stay generic
- `config_helpers.php`: one function per config key, returning `string`, `int`, `float`, `bool`, `?string` or a plain `array`. Add the helper together with the key (`paymentGateways()`, `throttleAttempts()`).
- `helpers.php`: only project-agnostic utilities that could be copied into another project unchanged (`plainText()`, `generateMediaName()`).
- Neither file imports or returns app classes (enums, models, services, interfaces), queries the database, or holds domain rules or table names.
- Domain logic built on a setting goes in the service that owns the data, and callers inject it. Example: order transaction ID validation belongs in `Backoffice/Sales/OrderService`, not in a helper.

## Boolean helpers start with is
Every helper that returns `bool` starts with `is` and reads as a yes/no question: `isGlobalOfferOnly()`, `isAiAssistantEnabled()`, `isFrontendCacheEnabled()`, `isMediaImage()`. Never `offerGlobalOnly()`, `assistantEnabled()` or `mediaIsImage()`.
