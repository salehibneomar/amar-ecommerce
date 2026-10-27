---
paths:
  - app/Services/**
---

# Services

## Layout
- Every class ends with `Service`, including framework contract implementations (`MaintenanceModeService`). Interfaces go in `app/Interfaces/<Domain>` with an `Interface` suffix.
- Areas: `Auth`, `Backoffice` (staff reads and writes), `Frontend` (customer reads and actions), `Payment`, `Mail`, `AiAssistant`.
- Domains: `Backoffice/{Catalog,Content,Sales,People,System,Notification,Seo,Payment}`, `Frontend/{Shop,Account,AiAssistant,Analytics}`. Reuse a domain before adding one. A new domain gets its own folder, even for one class.

## Responsibilities
- Services own queries, transactions, cache and side effects. They return models, paginators or arrays shaped for the view, never HTTP responses (file and download responses excepted).
- Throw `ValidationException::withMessages()` for business-rule failures.
- Admin lists use `TableQueryTrait::paginateTableQuery()` and expose `sortableColumns()`. Reference: `Backoffice/Content/FaqService.php`.
- Rate limits use `ThrottleTrait::throttle()` with `throttleAttempts('<key>')` from `config/throttle.php`.

## Public cache
- Storefront reads go through `FrontendCacheTrait::rememberFrontendCache()` and cache payload arrays (`toFrontendCachePayload()` / `collectFromFrontendCachePayloads()`), never PHP objects. Filtered results use `rememberFrontendCacheVariant()`.
- Invalidation is central: the model uses `ClearsFrontendCacheTrait` and returns its keys from `frontendCacheKeys()` (override `frontendCacheIgnoredAttributes()` for columns the storefront never shows). Media changes clear the same keys.
- Only writes with no model event (pivot `sync()`, bulk `query()->update()`) call `FrontendCacheKeyEnum::…->forget()`. A new public dataset gets a new `FrontendCacheKeyEnum` case.

## Writes
- Wrap multi-row writes in `DB::transaction()`. Use `lockForUpdate()` where status changes can race.
- Stock changes only through `StockTrait` (`takeStock()` / `restoreStock()`), which uses atomic conditional updates.
- Mail goes only through `Mail/MailService::send()`, which defers it after the response or queues it when `MAIL_QUEUE_ENABLED=true`.

## Payments
- An order's payment status changes only through `Payment/PaymentService::record()`, which keeps the order and its `payments` row in step.
- Checkout opens a payment with `PaymentService::open($order, $gateway)`. Online results (return page and webhooks) come in through `PaymentService::sync()`, which asks the gateway for the real status.
- Gate gateway-specific UI and rules per order with `PaymentService::isManual($order)`, never with config reads in views.
- A new gateway is a `PaymentGatewayEnum` case plus a class in `Services/Payment/Gateway` that extends `PaymentGatewayService` and owns its label, icon and flow, mapped in `config/payment.php`. Embedded checkouts return a `pageView()`, hosted ones return the provider URL from `initiate()`, and both confirm through `status()`. Never add per-gateway `match` arms elsewhere.
