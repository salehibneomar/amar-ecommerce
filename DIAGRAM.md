# Diagrams

Diagrams for AmarEcommerce, written in Mermaid so they render on GitHub and in most editors. Keep them in step with the code: update the ERD when a migration changes, the class diagrams when a model relation or service dependency changes, and the sequences when a flow changes. Prose explanations live in [TECHNICAL.md](TECHNICAL.md).

1. [Entity relationship diagram](#1-entity-relationship-diagram)
2. [Class diagrams](#2-class-diagrams)
3. [Sequence diagrams](#3-sequence-diagrams)

## 1. Entity relationship diagram

Every application table. Laravel infrastructure tables (`cache`, `sessions`, `jobs`, `password_reset_tokens`) are left out. Money columns are unsigned integers in whole currency units. Dashed lines are polymorphic (morph) links.

```mermaid
erDiagram
    users {
        bigint id PK
        string name
        string email UK
        string status "UserStatusEnum"
        timestamp email_verified_at
        string password
        string google_id
        string facebook_id
        string phone UK
        text shipping_address
        text billing_address
        timestamp deleted_at
    }
    roles {
        bigint id PK
        string name UK "fixed key, UserRoleEnum for built-ins"
        string label "editable display name"
        string guard_name
    }
    permissions {
        bigint id PK
        string name UK "PermissionEnum value"
        string guard_name
    }
    model_has_roles {
        bigint role_id FK
        string model_type
        bigint model_id
    }
    model_has_permissions {
        bigint permission_id FK
        string model_type
        bigint model_id
    }
    role_has_permissions {
        bigint permission_id FK
        bigint role_id FK
    }
    staff_profiles {
        bigint id PK
        bigint user_id FK, UK
        string compensation_type "StaffCompensationTypeEnum"
        int compensation_value
        date joined_at
        date left_at
    }
    staff_payments {
        bigint id PK
        bigint user_id FK
        int amount
        tinyint period_month
        smallint period_year
        int total_sales_snapshot
        string compensation_type_snapshot
        int compensation_value_snapshot
        timestamp paid_at
        bigint paid_by FK
    }
    categories {
        bigint id PK
        bigint parent_id FK
        tinyint depth
        string name
        string slug UK
        string status "CategoryStatusEnum"
        int sort_order
        bool is_featured
        bigint updated_by FK
        timestamp deleted_at
    }
    products {
        bigint id PK
        bigint category_id FK
        string sku UK
        string name
        string slug UK
        int price
        tinyint discount_percentage
        int quantity
        string status "ProductStatusEnum"
        string badge "ProductBadgeEnum"
        bool is_featured
        string type "child type name, null for plain products"
        bigint updated_by FK
        timestamp deleted_at
    }
    ai_assistant_embeddings {
        bigint id PK
        string embeddable_type "morph, UK with id"
        bigint embeddable_id
        string model
        string content_hash
        json vector "unit length"
    }
    media {
        bigint id PK
        string model_type
        bigint model_id
        string collection_name "images, image, avatar, logo, icon, favicon"
        int order_column "gallery order, first is the thumbnail"
        string file_name
        string disk
        json generated_conversions
    }
    banners {
        bigint id PK
        string title
        string subtitle
        bool show_text
        string link
        int sort_order
        string status "BannerStatusEnum: active, inactive"
        timestamp starts_at
        timestamp ends_at
        bigint updated_by FK
    }
    offers {
        bigint id PK
        string name
        string headline "big line of the home page offer banner"
        string type "OfferTypeEnum: global, specific"
        tinyint discount_percentage
        bool is_active
        timestamp starts_at
        timestamp ends_at
        bigint updated_by FK
    }
    offerables {
        bigint offer_id PK, FK
        string offerable_type PK
        bigint offerable_id PK
    }
    orders {
        bigint id PK
        string public_order_id UK
        bigint user_id FK
        string customer_name
        string customer_email
        int subtotal
        int discount_amount
        json applied_offers
        int extra_discount_percentage
        int extra_discount_amount
        int total
        string status "OrderStatusEnum"
        string payment_status "PaymentStatusEnum"
        string payment_method "PaymentMethodEnum"
        string transaction_id
        string contact_mode "ContactModeEnum"
        string phone
        text shipping_address
        text billing_address
        timestamp completed_at
        bigint completed_by FK
        bigint updated_by FK
    }
    order_items {
        bigint id PK
        bigint order_id FK
        bigint product_id FK
        string product_name_snapshot
        string sku_snapshot
        int quantity
        int unit_price
        tinyint discount_percentage
        string offer_name_snapshot
        tinyint offer_discount_percentage
        int line_total
    }
    faqs {
        bigint id PK
        string title
        text description
        string status "FaqStatusEnum"
        bigint updated_by FK
    }
    system_settings {
        bigint id PK
        boolean maintenance_enabled
        timestamp maintenance_starts_at
        timestamp maintenance_ends_at
        string maintenance_secret
    }
    notifications {
        uuid id PK
        string type "NotificationTypeEnum"
        string notifiable_type
        bigint notifiable_id
        text data "message, params, route"
        timestamp read_at
    }

    roles ||--o{ model_has_roles : "assigned via"
    users ||..o{ model_has_roles : "has role"
    permissions ||--o{ model_has_permissions : "granted via"
    users ||..o{ model_has_permissions : "extra permissions"
    roles ||--o{ role_has_permissions : "default permissions"
    permissions ||--o{ role_has_permissions : "belongs to role"

    users ||--o| staff_profiles : "staff details"
    users ||--o{ staff_payments : "is paid"
    users ||--o{ staff_payments : "pays (paid_by)"

    categories ||--o{ categories : "parent of"
    categories ||--o{ products : contains
    products ||..o| ai_assistant_embeddings : "AI search vector (morph)"
    products ||..o{ media : "images (up to 5, ordered)"
    banners ||..o{ media : image
    categories ||..o{ media : image
    users ||..o{ media : avatar

    offers ||--o{ offerables : targets
    categories ||..o{ offerables : "targeted by"

    users ||--o{ orders : places
    orders ||--o{ order_items : contains
    products ||--o{ order_items : "sold as"
    users ||--o{ orders : "completes (completed_by)"


    users ||..o{ notifications : receives

    users ||--o{ categories : "last updated"
    users ||--o{ products : "last updated"
    users ||--o{ banners : "last updated"
    users ||--o{ offers : "last updated"
    users ||--o{ faqs : "last updated"
    users ||--o{ orders : "last updated"
```

## 2. Class diagrams

### Domain models

Eloquent models with their relations, traits and the domain methods other code relies on. Casts to enums are shown as attribute types.

```mermaid
classDiagram
    direction LR

    class User {
        +string name
        +string email
        +UserStatusEnum status
        +string|null avatar_url
        +isSuperAdmin() bool
        +isAdmin() bool
        +isBackofficeUser() bool
        +staff() scope
        +customers() scope
    }
    class Role {
        +string name
        +string label
        +isSystem() bool
        +isSuperAdmin() bool
        +isAdmin() bool
        +isLocked() bool
        +permissionNames() list
    }
    class StaffProfile {
        +StaffCompensationTypeEnum compensation_type
        +int compensation_value
        +Carbon joined_at
        +Carbon left_at
    }
    class StaffPayment {
        +int amount
        +int period_month
        +int period_year
    }
    class Category {
        +string name
        +CategoryStatusEnum status
    }
    class Product {
        +string sku
        +int price
        +int discount_percentage
        +ProductStatusEnum status
        +int final_price
        +list gallery_images
        +string thumbnail_url
    }
    class Order {
        +string public_order_id
        +int total
        +OrderStatusEnum status
        +PaymentStatusEnum payment_status
        +isCancellable() bool
    }
    class OrderItem {
        +int unit_price
        +int quantity
        +int line_total
    }
    class Offer {
        +OfferTypeEnum type
        +int discount_percentage
        +isGlobal() bool
        +appliesToCategoryPath(path) bool
        +isCurrentlyActive() bool
    }
    class Banner
    class Faq
    class MediaTrait {
        <<trait>>
        +mediaConfigPaths() list
        +mediaUrl(path, conversion) string
        +syncMedia(path, file, remove) void
    }
    class CmsUpdateTrackerTrait {
        <<trait>>
        +updatedBy() BelongsTo
        +lastUpdateLabel() string
    }

    User "1" --> "*" Role : roles
    User "1" --> "0..1" StaffProfile : staffProfile
    User "1" --> "*" StaffPayment : staffPayments
    User "1" --> "*" Order : orders
    Category "1" --> "*" Category : children
    Category "1" --> "*" Product : products
    Order "1" --> "*" OrderItem : items
    OrderItem "*" --> "1" Product : product
    Offer "*" --> "*" Category : categories (offerables)

    User ..|> MediaTrait
    Product ..|> MediaTrait
    Banner ..|> MediaTrait
    Category ..|> MediaTrait
    Category ..|> CmsUpdateTrackerTrait
    Product ..|> CmsUpdateTrackerTrait
    Banner ..|> CmsUpdateTrackerTrait
    Offer ..|> CmsUpdateTrackerTrait
    Faq ..|> CmsUpdateTrackerTrait
    Order ..|> CmsUpdateTrackerTrait
```

### Services

Services own the business logic. Arrows show constructor dependencies. Livewire components and controllers call these services and never touch models directly.

```mermaid
classDiagram
    direction LR

    namespace Frontend {
        class FrontendOrderService["OrderService"]
        class CartService
        class PageService
        class AccountService
        class AiAssistantService
        class AiAssistantSearchService
    }
    namespace Backoffice {
        class BackofficeOrderService["OrderService"]
        class NotificationService
        class OfferService
        class StaffService
        class StaffPaymentService
        class RoleService
        class DashboardService
        class ProductService
    }
    namespace Auth {
        class InboxService
        class EmailVerificationService
    }
    namespace System {
        class SystemSettingService
        class MaintenanceModeService
    }
    namespace Mail {
        class MailService {
            +send(to, mailable)
        }
    }
    namespace Payment {
        class PaymentGatewayInterface {
            <<interface>>
            +initiate(Payment) string
            +confirm(Payment) bool
            +status(Payment) PaymentStatusEnum
            +cancel(Payment)
            +webhookReference(Request) string
            +pageData(Payment) array
        }
        class PaymentService
        class CodPaymentGatewayService
        class ManualPaymentGatewayService
        class StripePaymentGatewayService
        class PaymentGatewayResolver
    }
    namespace AiAssistant {
        class AiAssistantProviderInterface {
            <<interface>>
            +embed(text) array|null
            +generateJson(system, messages, schema) array
        }
        class AiAssistantGeminiService
        class AiAssistantOpenAiCompatibleService
        class AiAssistantCacheService
        class AiAssistantProviderResolver
        class AiAssistantEmbeddingTrait {
            <<trait>>
            +syncAiAssistantEmbeddingLater(model)
            +syncAiAssistantEmbedding(model) bool
        }
        class AiAssistantInterface {
            <<interface>>
            +aiAssistantDocument() array
            +aiAssistantEmbeddingText() string
            +aiAssistantEmbeddingQuery() Builder
        }
    }

    FrontendOrderService --> CartService
    FrontendOrderService --> NotificationService
    FrontendOrderService --> MailService
    FrontendOrderService --> PaymentService
    BackofficeOrderService --> MailService
    BackofficeOrderService --> PaymentService
    MaintenanceModeService --> SystemSettingService
    CartService --> PageService
    AccountService --> EmailVerificationService
    AccountService --> FrontendOrderService
    EmailVerificationService --> FrontendOrderService
    BackofficeOrderService --> NotificationService
    OfferService --> NotificationService
    StaffPaymentService --> StaffService
    DashboardService --> BackofficeOrderService
    PaymentService --> PaymentGatewayResolver
    PaymentGatewayResolver --> PaymentGatewayInterface
    CodPaymentGatewayService ..|> PaymentGatewayInterface
    ManualPaymentGatewayService ..|> PaymentGatewayInterface
    StripePaymentGatewayService ..|> PaymentGatewayInterface
    AiAssistantService --> AiAssistantSearchService
    AiAssistantService --> AiAssistantProviderResolver
    AiAssistantSearchService --> PageService
    AiAssistantSearchService --> AiAssistantProviderResolver
    ProductService ..> AiAssistantEmbeddingTrait : uses
    AiAssistantProviderResolver --> AiAssistantProviderInterface
    AiAssistantGeminiService ..|> AiAssistantProviderInterface
    AiAssistantOpenAiCompatibleService ..|> AiAssistantProviderInterface
    AiAssistantCacheService ..|> AiAssistantProviderInterface
    AiAssistantProviderResolver --> AiAssistantCacheService
    AiAssistantSearchService --> AiAssistantInterface
```

## 3. Sequence diagrams

### Request during maintenance

```mermaid
sequenceDiagram
    actor Visitor
    participant Middleware as PreventRequestsDuringMaintenance
    participant Driver as MaintenanceModeService
    participant Settings as SystemSettingService
    participant Cache as frontend.system-settings

    Visitor->>Middleware: GET /shop
    alt admin path (admin/*, manage/*, settings*, …)
        Middleware-->>Visitor: normal auth and permission checks
    else public or customer path
        Middleware->>Driver: active()
        Driver->>Settings: isMaintenanceActive()
        Settings->>Cache: one cached row (enabled, window, secret)
        Settings-->>Driver: enabled and now inside the window
        Driver-->>Middleware: true + data() (secret, Retry-After)
        Middleware-->>Visitor: 503 "Back soon" (or the page, with a valid bypass cookie)
    end
```

### Checkout and payment

```mermaid
sequenceDiagram
    actor C as Customer
    participant App
    actor S as Staff
    C->>App: Add to cart (session), open checkout
    App-->>C: Signed-in customers get their phone and addresses prefilled
    C->>App: Place order (guests add name and email, optionally create an account)
    Note over C,App: Card skips this until Pay is clicked on the payment page
    App-->>C: Guest orders join the account once that email is verified or logs in
    App-->>C: Order ID + WhatsApp/Messenger/email links with the total pre-filled
    App-->>S: "New order" email to SHOP_OWNER_EMAIL
    alt Card (Stripe)
        App-->>C: Branded payment page with the cart (no order yet)
        C->>App: Pay places the order and creates the PaymentIntent
        C->>App: Return from Stripe, plus Stripe webhook
        App->>App: PaymentService::sync() asks Stripe, marks paid or failed
    else bKash or Nagad
        C->>S: Pay with bKash/Nagad, send TrxID
        S->>App: Record contact, enter TrxID, mark paid
    else Cash on delivery
        S->>App: Mark paid when the cash is collected
    end
    S->>App: Mark processing, shipped, completed
    App-->>C: Status emails and the invoice
```

### Order status

```mermaid
stateDiagram-v2
    [*] --> Pending
    Pending --> Processing: staff confirms, or payment is verified
    Processing --> Shipped
    Shipped --> Completed
    Pending --> Cancelled: staff or the customer cancels (stock restored)
    Processing --> Cancelled
    Shipped --> Cancelled
    Cancelled --> [*]: final
```

### Staff updates an order

```mermaid
sequenceDiagram
    actor S as Staff
    participant LW as Orders\Index (Livewire)
    participant OS as Backoffice\OrderService
    participant GW as PaymentGatewayResolver
    participant NS as NotificationService
    participant DB as Database
    actor C as Customer

    S->>LW: Save status / payment
    LW->>OS: save(id, status, paymentStatus, ...)
    OS->>DB: lockForUpdate() order (transaction)
    alt marked paid
        OS->>GW: resolve(method)->confirm(order)
    end
    OS->>DB: save order (updated_by = staff)
    OS-->>C: status email (defer)
    OS->>NS: notifyOrder(order)
    NS->>DB: insert rows for staff with manage_orders (not the actor)
    NS->>DB: insert row for the order's customer
    LW-->>S: toast
```

### Publishing an offer

```mermaid
sequenceDiagram
    actor A as Staff (manage_offers)
    participant LW as Offers\Index (Livewire)
    participant CS as CategoryService
    participant OS as OfferService
    participant NS as NotificationService
    participant DB as Database

    A->>LW: Choose Specific, type a search
    LW->>CS: searchRootOptions(search, chosen ids)
    CS-->>LW: root categories of every match, any depth (max 8)
    A->>LW: Click results (chips), Save
    LW->>OS: create(data with category_ids)
    OS->>DB: insert offer + offerables (transaction)
    OS->>OS: forget frontend offers cache
    alt offer is active
        OS->>NS: notifyOffer(offer)
        NS->>DB: chunked insert for every active customer
    end
```

### Opening a notification

```mermaid
sequenceDiagram
    actor U as User (staff or customer)
    participant DD as Notifications\Dropdown
    participant IS as InboxService
    participant DB as Database

    U->>DD: Load any page
    DD->>IS: latest(userId, 5), countUnread(userId)
    IS->>DB: select own notifications, newest first
    U->>DD: Click a notification
    DD->>IS: open(userId, id)
    IS->>DB: find own row, set read_at
    IS-->>DD: route(data.route, data.route_params)
    DD-->>U: redirect (wire:navigate)
```

### Uploading an avatar

```mermaid
sequenceDiagram
    actor U as User
    participant LW as Profile (InteractsWithAvatar)
    participant AS as AccountService
    participant M as User (MediaTrait)
    participant ML as Media library

    U->>LW: Pick a file (camera button)
    LW->>LW: validate (image, 2 MB max)
    LW->>AS: updateAvatar(user, file)
    AS->>M: syncMedia('user.media.avatar', file)
    M->>ML: store avatar-<random>-<time> (single file, replaces old)
    ML->>ML: square crops: small 64, medium 256 (webp)
    LW-->>U: new photo + toast
```

### AI assistant suggestions (RAG)

```mermaid
sequenceDiagram
    actor C as Customer
    participant LW as AiAssistant\Index
    participant AS as AiAssistantService
    participant SS as AiAssistantSearchService
    participant P as AI provider (Gemini, Groq…)

    C->>LW: "A gift for a 12 year old under 300"
    LW->>AS: reply(history, message, throttleKey)
    AS->>AS: throttle, mask emails and phone numbers
    AS->>SS: candidates(message)
    SS->>P: embed(message) [only when vectors exist]
    SS->>SS: apply filter and range words (sale, new, price), cosine rank cached vectors, else keyword score, no padding
    SS-->>AS: top AI_ASSISTANT_TOP_MATCHES rows: ref + aiAssistantDocument() + category
    AS->>P: generateJson(system prompt + candidates without ids, history, schema) [cached by request hash]
    alt provider error
        AS->>SS: matches(message) keyword only
        AS-->>LW: resting note + close matches, fallback = true
    else reply
        P-->>AS: {reply, suggestions: [{ref, reason}]}
        AS->>AS: map refs to ids, drop unknown refs, cap at AI_ASSISTANT_RESULTS
        AS-->>LW: reply + suggestions
    end
    LW->>SS: products(ids) for the cards (prices from the shop data)
    LW-->>C: reply bubble + clickable product list items
```

Product writes in `ProductService` call `syncAiAssistantEmbeddingLater()` from `AiAssistantEmbeddingTrait`, which embeds after the response. It re-embeds only when the text hash or the embedding model changed. `php artisan ai-assistant:embed` backfills every model listed in `ai-assistant.models`.
