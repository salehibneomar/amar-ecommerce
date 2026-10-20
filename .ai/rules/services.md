---
paths:
  - app/Services/**
---

# Services

## Naming
Every class in `app/Services/**` ends with `Service`, including classes that implement a framework contract (`MaintenanceModeService`). Interfaces go in `app/Interfaces` with an `Interface` suffix.

## Domain sub-folders
Every service lives in a domain sub-folder: `Backoffice/{Catalog,Content,Sales,People,System,Notification}`, `Frontend/{Shop,Account,AiAssistant}`. Reuse a domain before adding one; a new domain gets its own folder even with one class. The same applies to enums, traits, resolvers, middleware, mail and commands (`.ai/rules/structure.md`).

## Services own the logic
Queries, transactions, cache and side effects live here, grouped as `Auth`, `Backoffice` (admin writes), `Frontend` (customer reads and actions) and `Payment`. Return models, paginators or plain arrays shaped for the view. Return no HTTP responses, except file and download responses.

## Admin tables
List methods use `TableQueryTrait::paginateTableQuery()` and expose `sortableColumns()`. Follow `Backoffice/Content/FaqService.php`.

## Public cache
Frontend reads go through `FrontendCacheTrait::rememberFrontendCache()` and store models as payload arrays (`toFrontendCachePayload()` / `collectFromFrontendCachePayloads()`), never PHP objects. Filtered results (pages, batches) use `rememberFrontendCacheVariant()`. Invalidation is central: the cached model uses `ClearsFrontendCacheTrait` and returns its keys from `frontendCacheKeys()` (override `frontendCacheIgnoredAttributes()` for columns the storefront never shows, such as `quantity`); its media changes clear them too. Only writes without model events (pivot `sync()`, bulk `query()->update()`) call `FrontendCacheKeyEnum::…->forget()` themselves. A new public dataset gets a new `FrontendCacheKeyEnum` case.

## Writes
Wrap multi-row writes in `DB::transaction()` and use `lockForUpdate()` where status races. Change stock only through `StockTrait` (`takeStock()` / `restoreStock()`, used by both order services), which uses atomic conditional updates instead of locks. Change an order's payment status only through `Payment/PaymentService::record()`, which keeps the order and its `payments` row in step and asks the gateway to confirm. Checkout opens the payment with `PaymentService::start($order, $gateway)`, and online results come in through `PaymentService::sync()` (return page and webhooks), which asks the gateway for the real status. Gate gateway-specific UI and rules (manual contact links, TrxID, contact mode) per order with `PaymentService::isManual($order)`, never with config reads in views. A new gateway is a `PaymentGatewayEnum` case plus a `PaymentGatewayInterface` class in `Services/Payment` mapped in `config/payment.php`; offline gateways use `OfflinePaymentGatewayTrait`. Throw `ValidationException::withMessages()` for business-rule failures. Send mail with `defer()` after the response; there is no queue.

## Rate limits
Use `ThrottleTrait::throttle()` with `throttleAttempts('<key>')` from `config/throttle.php`.
