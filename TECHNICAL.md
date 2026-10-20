# Technical guide

How AmarEcommerce Lite, the ecommerce boilerplate for client shop projects, is built: layers, folders, data model, flows and conventions. For installation see [SETUP.md](SETUP.md). For known gaps see [INCOMPLETE.md](INCOMPLETE.md). AI agents start at [AGENTS.md](AGENTS.md).

## Contents

1. [Stack](#1-stack)
2. [Architecture](#2-architecture)
3. [Folder map](#3-folder-map)
4. [Roles and access](#4-roles-and-access)
5. [Database](#5-database)
6. [Core flows](#6-core-flows)
7. [Money and currency](#7-money-and-currency)
8. [Caching](#8-caching)
9. [Security](#9-security)
10. [Configuration and helpers](#10-configuration-and-helpers)
11. [Frontend](#11-frontend)
12. [Testing](#12-testing)
13. [Conventions](#13-conventions)
14. [Future scope](#14-future-scope)

---

## 1. Stack

| Layer | Technology |
|---|---|
| Language | PHP 8.4 |
| Framework | Laravel 13 |
| UI | Livewire 4, Flux UI 2, Blade components, Alpine.js, Tailwind CSS 4, Vite |
| Auth | Fortify (staff login, password reset, 2FA), custom customer auth, Socialite (Google, Facebook) |
| Roles | spatie/laravel-permission: roles `super_admin`, `admin`, `moderator`, `editor`, custom roles and `user`, with `PermissionEnum` permissions |
| Files | spatie/laravel-medialibrary (product galleries, category and banner images, avatars, brand images) |
| PDF | mpdf/mpdf via `MpdfTrait` (invoices): Hind Siliguri for Bangla and English, bundled mPDF fonts for other scripts via `MpdfFontResolver` |
| Database | MySQL / MariaDB (tests: in-memory SQLite) |
| Cache | Database store by default, swappable to Redis through `.env` |
| Quality | Pest 5, Larastan (PHPStan), Pint, Laravel Boost (MCP and agent guidelines) |

## 2. Architecture

The app uses a **Service–Controller** pattern. All business logic lives in services, so a future API can reuse them.

```
Route ─► Middleware ─► Controller / Livewire ─► Service ─► Model ─► Database
                             │
                             └─► Blade view (+ reusable components)
```

| Layer | Responsibility | Must not |
|---|---|---|
| Routes (`routes/web.php`, `routes/settings.php`) | URL, name, middleware | Contain logic |
| Middleware (`app/Http/Middleware`) | `admin`, `backoffice`, `customer`, `not-blocked`, throttles | Query business data |
| Controllers (`app/Http/Controllers/{Frontend,Backoffice,Auth}`) | Call a service, return a view with `compact()` or a file | Hold business logic |
| Livewire (`app/Livewire/{Frontend,Backoffice}`) | UI state, input validation, calling services | Import or query models |
| Services (`app/Services/{Auth,Backoffice,Frontend,Payment}`) | Queries, transactions, cache, mail | Return HTTP responses (except files) |
| Models (`app/Models`) | Relations, casts, accessors, scopes | Contain workflows |
| Views (`resources/views`) | Markup | Contain `@php` or queries |

## 3. Folder map

```
app/
├── Concerns/                 Shared validation rule sets (password, profile)
├── Console/Commands/         Database/ (app:fresh: wipe media + migrate:fresh), AiAssistant/ (ai-assistant:embed)
├── Enums/                    Backed enums with label() and color(), by domain: Catalog/, Content/, Order/, User/,
│                             Notification/, AiAssistant/, System/
├── Helpers/
│   ├── config_helpers.php    Typed accessors for config (appCurrency(), shopOrderPrefix(), …). Scalars and arrays only, no app classes
│   └── helpers.php           Universal helpers (formatMoney(), formatDateTime(), sanitizeRichText(), media rules)
├── Http/Controllers/         Auth/ (social login), Backoffice/ (page shells), Frontend/ (pages, account, cart)
├── Http/Middleware/          Auth/ (Guest, NotBlocked, PasswordResetThrottle), Access/ (Backoffice, Customer, AppOwner)
├── Interfaces/               AiAssistant/ (AiAssistantInterface for opt-in models, AiAssistantProviderInterface for AI vendors), Payment/ (PaymentGatewayInterface)
├── Livewire/
│   ├── Backoffice/<Area>/Index.php   Banners, Products, Categories, Faqs, Staff, Roles, StaffPayments,
│   │                                 Offers, Orders, SalesReports, Users, Notifications
│   ├── Frontend/<Area>/              Auth/*, Cart/{Index,Counter,Checkout}, Orders/Track, Profile,
│   │                                 Settings, Shop, Search, Notifications, AiAssistant
│   └── Concerns/             InteractsWithTable (admin tables), InteractsWithAvatar, InteractsWithAddresses, InteractsWithCategoryLevels
├── Mail/Order/               OrderPlaced, OrderRequested (owner), OrderProcessing, OrderShipped, OrderCompleted, OrderCancelled
├── Models/                   User, Category, Product, Banner, Offer, Order, OrderItem, Payment, Faq,
│                             StaffProfile, StaffPayment, SystemSetting, AiAssistantEmbedding
├── Resolvers/                Payment/ (PaymentGatewayResolver), Pdf/ (MpdfFont), AiAssistant/ (AiAssistantProviderResolver)
├── Services/
│   ├── Auth/                 EmailVerificationService, PasswordResetService, SocialAuthService
│   ├── Backoffice/           Catalog/ (Product, Category), Content/ (Banner, Faq, Offer),
│   │                         Sales/ (Order, SalesReport, Dashboard), People/ (User, Staff, StaffPayment, Role),
│   │                         System/ (System, SystemSetting, MaintenanceMode), Notification/
│   ├── Frontend/             Shop/ (PageService: cached public data, Cart, Order), Account/ (Account),
│   │                         AiAssistant/ (AiAssistant, AiAssistantSearch)
│   ├── AiAssistant/          AiAssistantGeminiService, AiAssistantCacheService (response cache)
│   ├── Mail/                 MailService (sends now or queues, per MAIL_QUEUE_ENABLED)
│   └── Payment/              PaymentService, Cod/Manual/StripePaymentGatewayService, InvoiceService
└── Traits/                   Model/ (Media, CmsUpdateTracker), Query/ (TableQuery, CategoryTree), Cache/ (FrontendCache, ClearsFrontendCache),
                              Catalog/ (Stock), Pdf/ (Mpdf), AiAssistant/ (3), Mail/ (MailQueue), Security/ (Throttle)

resources/views/
├── backoffice/<area>/index.blade.php    Admin page shells (layout + Livewire component)
├── frontend/<page>/                     Storefront page shells (landing, shop, orders, legal, …)
├── components/backoffice/               layout/ (sidebar, nav), management/ (table kit), form/, per-area views
├── components/frontend/                 layout/, cards/, content/, orders/, drawers/, per-area views
├── components/{ui,brand,navigation,notifications,media,form,auth}/   Shared components by purpose
├── mail/, pdf/invoice.blade.php, layouts/, pages/ (Fortify + settings), errors/
.ai/                                     Agent rules (rules/) and playbooks (playbooks/)
```

### Traits

| Trait | Purpose |
|---|---|
| `TableQueryTrait` | Search, filter, sort and paginate a query for admin tables |
| `FrontendCacheTrait` | Remember and forget public data; store models as plain arrays |
| `MediaTrait` | Config-driven media collections, conversions, uploads and URLs |
| `ThrottleTrait` | Rate-limit an action and raise a validation error |
| `MailQueueTrait` | Retry settings for every Mailable when the mail queue is on: `backoff()` and `retryUntil()` from `MAIL_QUEUE_*` |
| `CategoryTreeTrait` | Ancestor path and root of categories at any depth (one query per level). Used by offer search and offer matching |
| `CmsUpdateTrackerTrait` | Sets `updated_by` to the staff user who saves the model (categories, products, banners, offers, faqs, orders) |
| `StockTrait` | The only place stock changes: atomic `takeStock()` and `restoreStock()` |
| `AiAssistantTrait` | Model side of the AI assistant: `aiAssistantEmbedding()` relation and a default `aiAssistantEmbeddingQuery()`. Pair with `AiAssistantInterface` |
| `AiAssistantEmbeddingTrait` | Service side: `syncAiAssistantEmbeddingLater($model)` embeds after the response with `defer()`; `syncAiAssistantEmbedding($model)` embeds now when the text hash changed. Used by `ProductService` and `ai-assistant:embed`; does nothing for models without `AiAssistantInterface` |

A trait is a complete, self-contained feature that unrelated classes plug in as a whole: all media handling (`MediaTrait`), public caching (`FrontendCacheTrait`), rate limiting (`ThrottleTrait`), or stock changes (`StockTrait`). It is not a place to park a method or two. Create a new trait only when it groups every method the feature needs, isn't tied to one caller, and at least two unrelated classes use it or it swaps with a trait of the same shape. The codebase is modular on purpose, with scalable, loosely coupled and reusable modules in mind for future features, so a trait that has one user today is also fine when it is such a module (`ProductAiAssistantTrait` for more product types, `AiAssistantTrait` for any model with `AiAssistantInterface`, `MpdfTrait` as a swappable PDF renderer). Otherwise put the method in the service that owns the data, or in the existing trait for that feature. Livewire concerns in `app/Livewire/Concerns` (`InteractsWithTable`, `InteractsWithNotifications`) follow the same rules.

## 4. Roles and access

Admin logins below use the default `ADMIN_ROUTE_PREFIX=backoffice`.

| Role | Login | Default permissions |
|---|---|---|
| `user` (customer) | `/user/login`, Google, Facebook | None. Storefront account pages only (`auth`, `verified`, `not-blocked`, `customer`) |
| `super_admin` | `/backoffice/login` | Everything. `Gate::before` grants every ability. Exactly one, created from the command line; cannot be edited, deactivated or assigned in the UI |
| `admin` | `/backoffice/login` | Set per admin: a new admin gets every permission directly and can be narrowed one by one. Only admins can hold `manage_roles` / `manage_system`. Only the Super Admin creates or changes admins |
| `moderator` | `/backoffice/login` | `manage_orders`, `manage_products`, `manage_categories` |
| `editor` | `/backoffice/login` | `manage_faqs` (future content areas go here) |
| custom roles | `/backoffice/login` | Whatever the admin ticks on the Roles screen |

Any role other than `user` is staff (`User::isBackofficeUser()`, `User::staff()` / `User::customers()` scopes). Roles and Offers open from **Settings** tabs and both ask for the password again (`password.confirm`). `manage_roles` and `manage_system` are admin-only (`PermissionEnum::isAdminOnly()`). System settings need `manage_system`. Role `name` is a fixed key and `label` is the editable display name (Admin cannot be renamed). Built-in roles and their defaults live in `UserRoleEnum::defaultPermissions()` and are applied by `RoleSeeder`. Admins create custom roles on **Roles**, and add staff with a role on **Staff**. A staff member can also hold extra per-user permissions on top of their role. Only admins can assign the `admin` role or change admins.

Two auth providers share the `users` table through a role-filtering provider, so customer credentials never open the backoffice and staff credentials never open the storefront account. Backoffice routes sit under `manage/` behind `backoffice`, and each area adds `can:<PermissionEnum>`.

**Adding a backoffice area:** add a `PermissionEnum` case (with `label()` and `group()`), protect the route with `can:`, add a nav item with that permission in `config/backoffice.php`, and add any dashboard widget to `DashboardService::forUser()` with the same permission. The sidebar, dashboard and role screens pick it up with no other changes.

## 5. Database

The full entity relationship diagram is in [DIAGRAM.md](DIAGRAM.md#1-entity-relationship-diagram).

| Table | Purpose and key columns |
|---|---|
| `users` | Customers and staff. `status` (active/blocked), `email_verified_at`, `google_id`, `facebook_id`, `phone` (unique, nullable, exactly `VALID_PHONE_LENGTH` digits, set from the profile page), `shipping_address`, `billing_address` (saved from either profile page through `Livewire\Concerns\InteractsWithAddresses`, prefilled at checkout, and filled from the first order when still empty) |
| `roles`, `permissions`, `model_has_*`, `role_has_permissions` | spatie/laravel-permission. `roles.name` is the fixed key and `roles.label` the editable display name. Role permissions are defaults, `model_has_permissions` holds per-person extras |
| `categories` | Self-referencing tree (`parent_id`) with `depth` (root = 1), `status`, `sort_order`, `is_featured`, soft deletes, and an optional square image (`category.media.image`, cropped to `small` 64² for the admin table, `regular` 400² for the home page cards, `large` 600² for the admin media view; uploaded from the row's Upload action, like products). `APP_CATEGORY_LEVEL` (1–3, default 2) caps the depth: `CategoryService` validates parents against it and shifts a moved branch's depth, and queries never read deeper categories. Admin pickers stack `x-ui.load-more-select` per level (`x-backoffice.management.category-level-select` + `InteractsWithCategoryLevels`); only the root is required. The storefront never loads every category: the shop sidebar fetches a page of roots plus their sub-categories (one query per level, cached per page; "Load more" for the next page, `Shop\Index::CATEGORY_PAGE_SIZE`, kept in the session), the shop filter shows products of the ticked categories only (ticking or unticking a category does the same to its sub-categories, `PageService::selectCategories()`; a category link such as a home card or breadcrumb ticks its sub-categories, `linkedCategories()`), the breadcrumb (`getCategoryTrail()`) reads one category's parents, and the AI labels only the categories in-stock products use. Sidebar pages and featured categories are cached under `frontend.categories` |
| `products` | Generic sellable item shared by every product type: `sku` (unique, searchable in the backoffice and storefront), `price` (integer), `discount_percentage`, `quantity`, `status`, `badge` (`ProductBadgeEnum`), `is_featured`, `type` (null for plain products, otherwise the snake-case name of a child type), soft deletes. Up to `PRODUCT_IMAGE_MAX_FILES` images in the `images` media collection, in the order staff set, the first one being the thumbnail |
| `ai_assistant_embeddings` | One AI search vector per record of any model that implements `AiAssistantInterface` (polymorphic `embeddable`, unique): `model`, `content_hash` (sha256 of model and text, so unchanged records are never re-embedded), `vector` (json, unit length) |
| `media` | spatie/medialibrary, polymorphic: product `images` (ordered by `order_column`), category and banner `image`, user `avatar`, brand logo, icon and favicon |
| `banners` | Home slider: title, link, schedule (`starts_at`/`ends_at`), `status` (`BannerStatusEnum`), `sort_order`, and an optional wide image (`banner.media.image`, uploaded from the row's Upload action like products and categories; a banner without an image shows as a text slide) |
| `offers` | Percentage discount with an optional schedule and a required `headline` (the big line of the home page offer banner, also shown under the name in the admin list). `type` is `global` (whole shop) or `specific` (root categories in `offerables`; a match on any category resolves to its root, and the offer covers every level below it). At checkout each cart line gets the highest live offer that covers it, rounded once per offer. Two active global offers cannot overlap in dates (an empty start counts from today, an empty end never ends); `OFFER_GLOBAL_ONLY` hides and ignores specific offers |
| `offerables` | Polymorphic pivot (`offer_id`, `offerable_type`, `offerable_id`): what a specific offer covers. Categories today; products or other targets can be added without a new table |
| `orders` | `public_order_id` (`<PREFIX>-XXXXXX`), customer snapshot, `subtotal`, `discount_amount`, `applied_offers` (JSON snapshot of each offer at checkout: name, percentage, categories, amount), `extra_discount_percentage`, `extra_discount_amount`, `total` (integers; staff set the extra discount in Manage order, as a percentage of `subtotal − discount_amount`, capped by `SHOP_MAX_EXTRA_DISCOUNT`, locked once the order is paid or uses an online gateway), `status`, `payment_status`, `payment_method`, `transaction_id`, contact, `shipping_address`, `billing_address` (a copy of the shipping address when the customer ticks "same as delivery"), completion. `user_id` is null for guest orders |
| `order_items` | `product_name_snapshot`, `sku_snapshot`, `quantity`, `unit_price`, `discount_percentage`, `offer_name_snapshot`, `offer_discount_percentage` (the offer that covered this line at checkout), `line_total` (integers) |
| `staff_profiles` | One row per staff member: `compensation_type` (salary/percentage), `compensation_value` (integer), `joined_at`, `left_at` |
| `staff_payments` | One payment per staff member per month, with snapshots of sales and compensation |
| `faqs` | `title`, `description`, `status` |
| `notifications` | Laravel database notifications, one row per recipient. `type` is a `NotificationTypeEnum` value and `data` holds a translation key, its params, a route name and route params, so any feature can send one. `Backoffice\NotificationService` sends (order events to staff with `manage_orders` and to the order's customer, new active offers to active customers). `Auth\InboxService` reads, opens and marks read/unread for the signed-in user in both panels. Shown on page load, not real time |
| `system_settings` | One row: `maintenance_enabled`, `maintenance_starts_at`, `maintenance_ends_at` (empty start means now, empty end means until turned off), `maintenance_secret` (bypass link). Logo, icon and favicon are single-file media collections (`config/system.php`) that fall back to the default files in `public/assets` |
| `cache`, `sessions`, `jobs` | Laravel infrastructure |

**Design decisions**

- **Snapshots:** order items keep the name, unit price and discount, so later price edits never change past orders or invoices.
- **Stock:** `StockTrait` (`Traits/Catalog`, used by the checkout and the backoffice order services) is the only place stock changes. `takeStock()` is one atomic `UPDATE … WHERE quantity >= ?` inside the checkout transaction, so stock can never be oversold and no row lock is held; `restoreStock()` adds it back on cancel. Selling the last unit switches the product to `OutOfStock`, and returning stock to a product that ran out switches it back (a product an admin set to `OutOfStock` stays so). The shop page still lists `OutOfStock` products with an "Out of Stock" badge and a disabled cart button, the product page hides Buy and Add to cart, and the cart refuses anything not `InStock`. Only these switches clear the product cache (after commit); ordinary sales leave it warm because the storefront never shows `quantity`.
- **Enums as strings:** status columns store enum values, so the database stays readable.

## 6. Core flows

### Checkout and payment

See the checkout sequence and the order status diagram in [DIAGRAM.md](DIAGRAM.md#3-sequence-diagrams).

- Marking the payment as paid always completes the order. Cancelled orders are frozen.
- Customers can cancel their own order only while it is `Pending` and unpaid (`Order::isCancellableByCustomer()`). `Frontend\OrderService::cancelForUser()` checks ownership and reuses `Backoffice\OrderService::cancel(pendingOnly: true)`, which rechecks the status under the row lock, so stock, payment, emails and staff notifications match a staff cancel.
- Payment gateways use a strategy: `PaymentGatewayEnum` (`cod`, `manual`, `stripe`) names them, `config/payment.php` maps each to a `PaymentGatewayInterface` class (`CodPaymentGatewayService`, `ManualPaymentGatewayService`, `StripePaymentGatewayService`) and `PaymentGatewayResolver` builds it. Offline gateways share `OfflinePaymentGatewayTrait` (no redirect, no provider status, no webhook).
- `PAYMENT_GATEWAYS` lists the gateways offered at checkout. The customer's choice becomes the order's `payments` row, so `payments.gateway` is the order's payment type. Manual (TrxID, contact links) UI is gated per order with `PaymentService::isManual($order)`.
- Online flow (`PaymentController`, routes `payments.*`): a gateway with a payment page (`PaymentGatewayEnum::pageView()`) places no order at checkout. `OrderService::holdCheckout()` keeps the details in the session and `payments.checkout` shows the cart with the Payment Element in deferred mode (amount only, no PaymentIntent). Clicking Pay posts to `payments.place`, which places the order, opens the payment and creates the PaymentIntent, then the browser confirms it with the returned client secret. A declined card retries on the same intent. An unpaid order is paid later from the signed `payments.show` page, which creates a PaymentIntent lazily (idempotent on the payment reference). Both pages render the Stripe Payment Element styled from the theme tokens (`stripePayment` in `resources/js/app.js`). Stripe returns to `payments.return`, and `POST payments/webhook/{gateway}` (CSRF free, signature checked) also arrives. Both call `PaymentService::sync()`, which asks the gateway for the real status and updates the payment and the order. The confirmation page shows a Pay button while an online payment is pending or failed.
- Stripe amounts are sent in minor units (`× 100`, or `× 1` / `× 1000` for zero and three decimal currencies). Staff cancelling an order also cancels the PaymentIntent.
- Order emails go through `Mail\MailService::send()`. With `MAIL_QUEUE_ENABLED=false` they are sent with `defer()` after the response, so no worker is needed, and the `failover` mailer writes them to the log if SMTP is down. With `true` they are queued on the `smtp` mailer and `MailQueueTrait` retries them (`backoff()`, `retryUntil()`) until the mail server is back. Every new Mailable uses `MailQueueTrait` and is sent through `MailService`. Verification and password reset emails stay immediate because their links expire in minutes.
- Guests can check out without an account. They give a name and email, which become the order's customer snapshot and get the order emails. They can tick "Create an account with these details": the account is registered first (same rules as the register page), then the order is placed as a guest order.
- Guest orders join an account through `OrderService::claimGuestOrders()`, matched by email, once that email is verified: on email verification, social login and password login (`AccountService::login()`).
- Guests can track an order at `/track-order` (order ID + email, throttled).

### Product types

`Product` holds only what every product shares: price, discount, stock, status, category, badge, images and orders. Plain products need nothing else. A shop that sells a special kind of product adds one subclass model, picked by `products.type`, with its own columns in its own table (class table inheritance):

- A child type extends `Product` and declares `$detailTable` and `$detailColumns`. For example, `Shirt extends Product` with `$detailTable = 'shirts'` and `$detailColumns = ['size', 'color']` makes `$shirt->size` and `Shirt::create([..., 'size' => 'M'])` work like any other attribute. For the AI assistant it uses `ProductAiAssistantTrait` and only names its extra fields (`aiAssistantColumns()`).
- `Product` does the work for any type that declares a detail table: a `type` global scope, a left join of the detail table that selects its columns whenever `products.*` is selected, and a split save (the `products` row first, then an upsert into the detail table, only for changed detail columns). Aggregates and custom selects stay untouched. The detail table is keyed by `product_id` and must not repeat `products` column names (no `id` or timestamps), so unqualified `where`, `orderBy` and `latest()` stay unambiguous.
- Types follow a naming convention, so `Product` never references a subclass: `type = shirt` hydrates as `App\Models\Shirt` (`Product::newFromBuilder()`), and a subclass's `type` is its snake-case class name (`productType()`), used by the type scope and set on save. Generic code such as cart lines, order items, stock and categories queries `Product` and still gets the child instances.
- Polymorphic rows (`media`, `ai_assistant_embeddings`) store the concrete class, so Spatie builds conversions from the right model. Generic code that reaches products through `OrderItem` loads media per concrete class (`Frontend\OrderService::getOrderWithItems()`).

### AI shopping assistant (beta)

See the RAG sequence in [DIAGRAM.md](DIAGRAM.md#ai-assistant-suggestions-rag) and the full walkthrough in [OUR_RAG.md](OUR_RAG.md).

- `Livewire\Frontend\AiAssistant\Index` is the **Ask AI** page (`/ai-assistant`, route `assistant`, `customer` middleware). It returns 404 and its nav link is hidden when `isAiAssistantEnabled()` is false. History lives only in the component.
- **Opt-in models:** a model joins the assistant by implementing `AiAssistantInterface` and using `AiAssistantTrait` (like `HasMedia` + `InteractsWithMedia`), and by being listed in `ai-assistant.models`. Services only check `instanceof`. `aiAssistantDocument()` whitelists the public fields the provider may see, `aiAssistantEmbeddingText()` is what gets embedded, and `aiAssistantEmbeddingQuery()` picks which records to embed. `Product` is the first one.
- `AiAssistantSearchService::candidates()` picks up to `AI_ASSISTANT_CANDIDATES` in-stock products from the cached catalogue: cosine similarity on cached vectors when an embedding model is set, otherwise a weighted keyword score (name, category, description).
- `AiAssistantService::reply()` throttles, masks emails and phone numbers, sends the candidates with a `ref` number instead of the database id, the trimmed history and a JSON schema, maps the returned refs back to ids (max `AI_ASSISTANT_RESULTS`), and falls back to keyword matches when the provider fails. Cards read names and prices from the shop data, never from the model.
- Providers use a strategy like payments: `AiAssistantProviderInterface` (`embed()`, `generateJson()`) with `AiAssistantGeminiService`, picked by `AiAssistantProviderResolver`, which wraps it in `AiAssistantCacheService` when `AI_ASSISTANT_CACHE_MINUTES` is above 0. Identical requests (same prompt, candidates and history) and query embeddings are then served from the frontend cache store. Index-time embeddings are never cached.
- Any service that writes an opt-in model uses `AiAssistantEmbeddingTrait` and calls `syncAiAssistantEmbeddingLater($model)` (`ProductService` after create and update). `php artisan ai-assistant:embed` backfills every listed model. No service depends on the assistant services to stay indexed.

### Maintenance and branding

Settings → System (`manage_system`, password confirmation) holds branding, maintenance mode and the cache tools. Nothing needs cron, a scheduler or a queue:

- `APP_MAINTENANCE_DRIVER=system` selects `MaintenanceModeService`, registered on `MaintenanceModeManager` in `AppServiceProvider`. Laravel's `PreventRequestsDuringMaintenance` asks it `active()` on every request, which is `enabled` and inside the window. So a scheduled window starts and ends with the next visitor, and `php artisan down`/`up` write the same row.
- Visitors get a 503 (`errors/503.blade.php`: logo, message, "Expected back by", no buttons) with `Retry-After` when an end is set. The bypass link `/<secret>` sets Laravel's bypass cookie for that browser.
- Admin paths skip the check through the `except` list in `bootstrap/app.php` (`admin/*`, `dashboard`, `manage/*`, `settings*`, `logout`, password confirmation, `email/*`, `livewire-*`, `flux/*`). They still run their normal auth and permission middleware, so admins sign in as usual from any device.
- `SystemSettingService` is scoped (one instance per request) and keeps the whole row as one plain array in `frontend.system-settings`. A view composer passes `brandUrls()` only to `partials.head` and the `brand` logo components.

### Navbar search

The search icon opens `x-ui.popover` with `Livewire/Frontend/Search/Index`. From two characters it shows the top five in-stock products whose name matches, as `product-list-item` rows, plus "See more" (to `/shop?q=…`) when a sixth exists. `PageService::searchProductsByTitle()` runs one limited database query (in-stock, title `LIKE`, at most six rows), so memory stays flat as the catalogue grows.

### Staff pay

`StaffPaymentService::calculate()` suggests the month's amount: the salary, or `round(paid sales × percent / 100)`. The admin records the actual amount, once per month.

## 7. Money and currency

- All money is stored as **unsigned integers in whole currency units**. There are no fractions anywhere.
- Rounding happens once, where the value is calculated, with half-up rounding:
  - `Product::final_price` = `round(price × (100 − discount) / 100)`;
  - `line_total` = `final_price × quantity`;
  - offer discount = `round(subtotal × percent / 100)`;
  - `total` = `subtotal − discount`.
- The currency comes from `.env`: `APP_CURRENCY` (code, e.g. `BDT`, used on PDF invoices) and `APP_CURRENCY_SYMBOL` (e.g. `৳`, used on screen, in mail and in messages). Read them with `appCurrency()` and `appCurrencySymbol()`, and format with `formatMoney($amount)`.

## 8. Caching

Only **public, shared** data is cached. Admin screens and per-user data always read live.

| Key (`FrontendCacheKeyEnum`) | Contents | Cleared by (`ClearsFrontendCacheTrait` on the model) |
|---|---|---|
| `frontend.categories.*` | Shop sidebar batches (roots + sub-categories per "Load more" size) and featured categories (landing page), per category level | `Category` saves and deletes |
| `frontend.products.*` | Small separate entries, each as plain payloads: featured products, new arrivals (per limit), the in-stock count, sitemap slugs, the AI catalogue, and each shop page (categories, sort, price, page; not free-text search) + total | `Product` saves, deletes and media (not stock-only changes), `Category` changes, `StockTrait` when a product sells out or returns |
| `frontend.banners` | Active banners | `Banner` saves, deletes and media |
| `frontend.offers` | Active offers | `Offer` saves and deletes, `Category` changes, plus `OfferService` after its category `sync()` |
| `frontend.faqs` | Active FAQs | `Faq` saves and deletes |
| `frontend.system-settings` | Maintenance flag, window, secret and brand image URLs (one plain array) | `SystemSetting` saves and media |
| `frontend.ai-assistant-vectors` | `morph type → id → vector` for the current embedding model | `AiAssistantEmbeddingTrait` |
| `ai-assistant.json.*`, `ai-assistant.embed.*` | Provider replies and query embeddings, keyed by a hash of provider, models and request, for `AI_ASSISTANT_CACHE_MINUTES` | Expire only |

- Clearing is central: `ClearsFrontendCacheTrait` clears a model's `frontendCacheKeys()` after the transaction commits, and `AppServiceProvider` does the same when a media file of that model is saved or deleted. `FrontendCacheKeyEnum::forget()` removes a key and its version token.
- Filtered results use `rememberFrontendCacheVariant()`: the key holds a version token plus a hash of the filters, and `forgetFrontendCache()` drops the version, so every variant of that key is invalidated at once (old ones expire by TTL). A future API reuses the same service methods and cache.
- Every entry uses `Cache::flexible()`: fresh for the TTL, then served stale for one more TTL while a single background request (behind a cache lock) refreshes it, so an expiring entry never makes many visitors rebuild it at once. Writes still clear entries immediately, so stale data is only ever served after plain expiry, never after an admin change. The cache store must support locks (`database`, `redis`, `file`).
- `FRONTEND_CACHE_ENABLED=false` (default `true`) skips the cache entirely: `FrontendCacheTrait` runs the query on every request. The TTL is `FRONTEND_CACHE_TTL_MINUTES` (180). The store is `FRONTEND_CACHE_STORE`, falling back to `CACHE_STORE`.
- Laravel 13 refuses to unserialize objects from the cache, so `FrontendCacheTrait` stores models as attribute arrays and rebuilds them.
- Shop search and filtering query live.

## 9. Security

- `SESSION_SECURE_COOKIE=true` live keeps the login cookie on HTTPS. Pages outside the storefront layout send `noindex`.
- The whole admin area sits under `ADMIN_ROUTE_PREFIX` (default `backoffice`); a hard-to-guess value hides the admin login from bots.
- Role and permission middleware on every backoffice area. Blocked users are logged out by `not-blocked`.
- Signed URLs for order confirmation and email verification.
- Configurable rate limits (`config/throttle.php`): login, register, checkout, order tracking, password reset, verification email.
- Rich text is sanitised (`sanitizeRichText()`). Uploads are validated by extension, MIME type and size from config.
- No PHP objects in the cache.

## 10. Configuration and helpers

| File | Settings |
|---|---|
| `config/app.php` | Name, owner, developer, `currency`, `currency_symbol` |
| `config/shop.php` | Order prefix, owner/support email, WhatsApp, Messenger, Facebook page/group |
| `config/payment.php` | Enabled gateways, gateway → class map, bKash/Nagad numbers, Stripe keys |
| `config/product.php`, `config/banner.php`, `config/category.php`, `config/user.php` | Media collections, limits, sizes and conversions. `file_prefix` names stored files (avatars are `avatar-…`) |
| `config/throttle.php` | Rate limits and resend cooldowns |
| `config/system.php` | Brand media (logo, icon, favicon): types, sizes and the `default` file each one falls back to |
| `config/theme.php` | Brand and gold color palettes, plus the brand, dark-mode and foreground shades. `<x-brand.theme-colors />` (in `partials/head`) prints them as `--theme-*` CSS variables that the `@theme` tokens in `app.css` point at, and the mail theme (`vendor/mail/html/themes/brand.blade.php`) reads them too. Read with `themePalettes()`, `themeBrandColor()`, `themeBrandDarkColor()`, `themeBrandForeground()` |
| `config/ai-prompts.php` | All AI text: system prompt lines (`:shop`, `:results`, `:currency` placeholders), messages, masking tokens, stop words and per-model labels. Read with `aiPrompt()` / `aiPromptList()` |
| `config/ai-assistant.php` | Assistant switch, provider, history, message length, timeout, candidates, results, response cache minutes, `models` (classes that implement `AiAssistantInterface`), and per-provider class, URL, key, chat model, embedding model and dimensions |
| `config/backoffice.php` (`route_prefix`) | `ADMIN_ROUTE_PREFIX`: one URL prefix for the whole admin area (Fortify login and password reset, dashboard, `manage/*`, `settings/*`). Routes, the guest redirect and the maintenance exception read `adminRoutePrefix()`; `config/fortify.php` reads the same env key |
| `config/seo.php` | Indexing switch, default description and share image for `x-ui.seo` |
| `config/cache.php` (`frontend`) | Public cache store and TTL |
| `config/database.php` (`seeding_enabled`, `seed_admin`) | Demo data switch; super admin seeded when it is off |

Code reads settings through the typed helpers in `app/Helpers/config_helpers.php`, so a renamed key changes in one place.

Both helper files (autoloaded from `composer.json`) stay generic. `config_helpers.php` is only a thin bridge: each function reads one config key and returns a scalar or plain array, so code never calls `config()` or `env()` directly, and `env()` appears only inside `config/`. `helpers.php` holds only utilities that could be copied into any project unchanged, such as `formatMoney()`, `formatDateTime()` and `sanitizeRichText()`. Neither file imports or returns app classes (enums, models, services, interfaces, resolvers), queries the database, or holds domain logic such as validation rules or table names. Turning a setting into an enum, building domain validation rules, and anything else tied to a model belong in the service that owns that data, which callers inject.

## 11. Frontend

- Page shells are thin: a layout plus Livewire or Blade components.
- Reusable components take data through props:
  - backoffice: `x-backoffice.management.*`;
  - storefront: `x-frontend.layout.page`, `x-frontend.layout.section`, `x-frontend.cards.card`, `x-frontend.cards.product-card`, `x-frontend.cart.summary`, `x-frontend.orders.items` (compact receipt), `x-frontend.orders.summary` (totals for any order);
  - storefront rows and overlays: `x-frontend.cards.product-tile` (grid card) and `x-frontend.cards.product-list-item` (row card, also used by search, "New Products" and AI suggestions), `x-ui.drawer` + `x-ui.drawer-trigger` (menu, filters and cart all use one drawer wrapper, opened by name);
  - shared: `x-ui.price`, `x-ui.badge`, `x-ui.tabs`, `x-media.upload`, `x-ui.error-page`, `x-ui.search-input` (every live search; debounce from `APP_SEARCH_DEBOUNCE_MS`, instant clear), `x-ui.dropdown` (every action menu, wraps `flux:dropdown`), `x-ui.popover` (floating panels such as the navbar search), `x-ui.backdrop`, `x-ui.load-more-select` (select that loads options in batches with a "Load more…" item; used by admin filters and the parent category field), `x-ui.seo` (description, canonical and OpenGraph/Twitter tags; storefront layout only, defaults from `config/seo.php` (`SEO_DESCRIPTION`, `SEO_IMAGE`, `SEO_INDEXING_ENABLED`), product pages pass their description and first image, `/sitemap.xml` lists public pages and in-stock or out-of-stock products, never archived ones).
- Every admin table uses `InteractsWithTable` + `TableQueryTrait` + the `management-*` components.
- Alpine handles client-only behaviour.
- Brand colors come only from `config/theme.php` (see HOW_TO.md §4). Views use the `brand-*`, `gold-*` and surface tokens, never hex or raw palette names for brand elements. Changing a brand color needs no rebuild.
- Icons are Heroicons (through Flux) in the backoffice and Material Symbols on the storefront.
- Run `npm run build` after Tailwind changes.

## 12. Testing

- Pest feature tests live in `tests/Feature/{Auth,Backoffice,Frontend,Settings}`, one file per feature.
- Tests use in-memory SQLite and the array cache.
- Factories have states such as `superAdmin()`, `admin()`, `moderator()`, `editor()`, `blocked()`, `unverified()`, `featured()`, `stock()`, `outOfStock()`, `archived()`, `paid()`.
- **Never run tests with `.env` exported**:

```bash
php artisan optimize:clear
env $(grep -oE '^[A-Z_][A-Z0-9_]*' .env | sed 's/^/-u /') php artisan test --compact
vendor/bin/pint --dirty
composer types:check
```

## 13. Conventions

- **Domain sub-folders:** services, enums, traits, resolvers, middleware, mail and commands live in a domain sub-folder (`Services/Backoffice/Catalog/BookService`, `Enums/Order/OrderStatusEnum`), never at the folder root. Models, policies and providers stay flat because Laravel and the database rely on their class names (`.ai/rules/structure.md`).
- **Naming, where the folder sets the suffix:** every class ends with the kind of its folder (`Services` → `*Service`, `Traits` → `*Trait`, `Enums` → `*Enum`, `Resolvers` → `*Resolver`, `Interfaces` → `*Interface`, `Controllers`, `Middleware`, `Mail`, `Commands`, `Policies` likewise). A framework contract implementation follows the folder it lives in (`MaintenanceModeService`). The name says the purpose, not the storage. Models, Livewire components and concerns, and framework-scaffolded classes are exempt. See `.ai/rules/structure.md`.
- **One area, one folder:** `Backoffice/Faqs/Index.php` ↔ `components/backoffice/faqs/index.blade.php` ↔ `backoffice/faqs/index.blade.php`.
- **Services own logic and transactions.** Livewire never imports models. Blade has no `@php`.
- **Shared code is universal:** traits, helpers and components never mention a specific page.
- **Enums** use TitleCase cases with `label()` and `color()`.
- **No prose comments:** only the PHPDoc types PHPStan needs.
- **Migrations:** pre-launch, edit the existing migration and rebuild with `composer serve:dev`.
- **Keep it simple:** no repositories, DTOs or abstractions until a second real use appears.
- **New features:** follow [.ai/playbooks/new-feature.md](.ai/playbooks/new-feature.md).

## 14. Future scope

| Idea | Where it plugs in |
|---|---|
| JSON API / mobile app | Reuse the services; add API controllers and resources |
| New payment gateway (SSLCommerz, bKash API…) | A `PaymentGatewayEnum` case, a `PaymentGatewayInterface` class, one line in `config/payment.php` and, for an embedded page, a `frontend/payments/<gateway>` view |
| Other product types | A model that extends `Product` (like `Shirt`), named after its `type` value, and, if needed, a details table like `shirts` declared with `$detailTable` and `$detailColumns` (see Product types) |
| Redis cache | `FRONTEND_CACHE_STORE=redis` |
| Scheduled reminders or auto-cancel | A scheduled command calling `Backoffice\OrderService` (needs cron) |
