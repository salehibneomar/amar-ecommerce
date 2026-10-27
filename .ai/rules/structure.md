---
paths:
  - app/**
---

# Naming, folders and traits

## The folder sets the suffix
The name before the suffix says the purpose, not the storage.
- `Services/**` → `*Service` (resolver-picked implementations too: `AiAssistantGeminiService`, `ManualPaymentGatewayService`, `MaintenanceModeService`), `Traits` → `*Trait`, `Enums` → `*Enum`, `Resolvers` → `*Resolver`, `Interfaces` → `*Interface`, `Http/Controllers` → `*Controller`, `Http/Middleware` → `*Middleware` (named after its route alias), `Mail` → `*Mail`, `Console/Commands` → `*Command`, `Policies` → `*Policy`, `Providers` → `*Provider` (Laravel and package defaults keep their names: `AppServiceProvider`, `FortifyServiceProvider`).
- Exempt: models, Livewire components (`Index`), Livewire concerns (`InteractsWith*`), `app/Actions/Fortify`, `app/Concerns`.
- A kind with no folder: ask first. Config keys name the purpose (`APP_MAINTENANCE_DRIVER=system`). Enum cases are TitleCase.

## Self-explanatory names
- A file or class name says plainly what it does, so a reader understands it without opening it (`DevServerProvider`, not `SystemProvider`).
- One suffix only, never two (`MediaProvider`, not `MediaServiceProvider`).
- Code backed by system settings uses the `SystemSetting` prefix (`SystemSettingProvider`, `SystemSettingService`).

## Providers
- `AppServiceProvider` keeps only Laravel defaults (`configureDefaults()`) and app-wide `scoped()` bindings. Never add hooks to it.
- Startup hooks (gates, auth providers, URL builders, view composers, model listeners, driver extensions) go in the provider of their domain: `AccessProvider`, `AuthMailProvider`, `MediaProvider`, `SeoMetaProvider`, `SystemSettingProvider`, `DevServerProvider`. A new domain gets a new `<Domain>Provider` listed in `bootstrap/providers.php`.
- Providers only register things. Work done during a request stays in services, and a provider never queries the database at boot.

## Domain sub-folders
- Never put a class at the root of `Services/Backoffice`, `Services/Frontend`, `Enums`, `Traits`, `Resolvers`, `Http/Middleware`, `Mail` or `Console/Commands`.
- Reuse a domain first: Catalog, Content, Sales or Order, People or User, System, Notification, Seo, AiAssistant, Analytics, Shop, Account, Pdf, Payment, Auth, Access, Database (`Services/Backoffice/Catalog/ProductService.php`, `Enums/Order/OrderStatusEnum.php`). A new domain gets its own sub-folder.
- Models, policies, providers, `Actions/Fortify` and `Concerns` stay flat.
- Moving a class: update its namespace, every `use`, config and `phpstan.neon`, and drain queued mail first.

## Traits
- A trait groups every method one feature needs and isn't tied to one caller.
- Create one only when two unrelated classes use it, it swaps with a same-shaped trait, or it is a loosely coupled module planned for reuse (`MediaTrait`, `FrontendCacheTrait`, `ThrottleTrait`, `ProductAiAssistantTrait`, `MpdfTrait`).
- Otherwise put the method in the owning service or the feature's existing trait.
- Livewire concerns in `app/Livewire/Concerns` follow the same rule (`InteractsWithTable`).
