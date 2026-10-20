---
paths:
  - app/**
---

# Naming, folders and traits

## The folder sets the suffix
The name before the suffix says the purpose, not the storage.
- `Services/**` → `*Service` (resolver-picked implementations too: `AiAssistantGeminiService`, `ManualPaymentGatewayService`, `MaintenanceModeService`), `Traits` → `*Trait`, `Enums` → `*Enum`, `Resolvers` → `*Resolver`, `Interfaces` → `*Interface`, `Http/Controllers` → `*Controller`, `Http/Middleware` → `*Middleware` (named after its route alias), `Mail` → `*Mail`, `Console/Commands` → `*Command`, `Policies` → `*Policy`, `Providers` → `*ServiceProvider`.
- Exempt: models, Livewire components (`Index`), Livewire concerns (`InteractsWith*`), `app/Actions/Fortify`, `app/Concerns`.
- A kind with no folder: ask first. Config keys name the purpose (`APP_MAINTENANCE_DRIVER=system`). Enum cases are TitleCase.

## Domain sub-folders
- Never put a class at the root of `Services/Backoffice`, `Services/Frontend`, `Enums`, `Traits`, `Resolvers`, `Http/Middleware`, `Mail` or `Console/Commands`.
- Reuse a domain first: Catalog, Content, Sales/Order, People/User, System, Notification, AiAssistant, Shop, Account, Pdf, Payment, Auth, Access (`Services/Backoffice/Catalog/ProductService.php`, `Enums/Order/OrderStatusEnum.php`). A new domain gets its own sub-folder.
- Models, policies, providers, `Actions/Fortify` and `Concerns` stay flat.
- Moving a class: update its namespace, every `use`, config, `phpstan.neon` and the docs, and drain queued mail first.

## Traits
- A trait groups every method one feature needs and isn't tied to one caller.
- Create one only when two unrelated classes use it, it swaps with a same-shaped trait, or it is a loosely coupled module planned for reuse (`MediaTrait`, `FrontendCacheTrait`, `ThrottleTrait`, `ProductAiAssistantTrait`, `MpdfTrait`).
- Otherwise put the method in the owning service or the feature's existing trait.
- Livewire concerns in `app/Livewire/Concerns` follow the same rule (`InteractsWithTable`).
