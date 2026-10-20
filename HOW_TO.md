# How to: common tweaks

Quick recipes for everyday changes. Each one says **where** to change, a **small example**, and what to do **after**.

For install and server setup see `SETUP.md`. For architecture see `TECHNICAL.md`.

---

## 0. My change does not show up

| You changed | Run |
|---|---|
| `.env` or anything in `config/` | `php artisan config:clear` |
| A Blade view | `php artisan view:clear` |
| Tailwind classes or `app.css` | `npm run build` (or keep `npm run dev` running) |
| Shop content (products, banners, FAQs) | Wait for the cache, or `php artisan cache:clear` (see §14) |
| Not sure | `php artisan optimize:clear`, then hard refresh the browser (Ctrl+Shift+R) |

---

## 1. App name, currency, timezone

**Where:** `.env`

```env
APP_NAME="AmarEcommerce"
APP_CURRENCY=BDT
APP_CURRENCY_SYMBOL=৳
APP_EMPTY_VALUE=N/A
APP_TIMEZONE=Asia/Dhaka
```

Never type `৳` or `BDT` in code. Show money with `formatMoney($amount)`. `APP_EMPTY_VALUE` is what admin tables, detail views and invoices show when a field has no value: read it with `appEmptyValue()`, or use `<x-ui.empty-value />` for the greyed table placeholder.

**After:** `php artisan config:clear`

---

## 2. Contact details, social links, payment numbers

**Where:** `.env`

```env
SUPPORT_EMAIL=help@example.com
WHATSAPP_NUMBER=1712345678
FACEBOOK_PAGE_URL=https://facebook.com/yourpage
FACEBOOK_MESSENGER_URL=https://m.me/yourpage

PAYMENT_BKASH_NUMBER=01712345678
PAYMENT_NAGAD_NUMBER=01812345678

SHOP_OWNER_EMAIL=owner@example.com   # gets the "new order" email
SHOP_ORDER_PREFIX=AE                 # order IDs look like AE-ABC123
OFFER_GLOBAL_ONLY=true               # whole-shop offers only; false brings back category offers
```

**After:** `php artisan config:clear`

---

## 3. Logo and favicon

**Where:** admin → Settings → System → Branding. Upload a logo, an icon (small square mark) and a favicon (`.ico` or `.png`); they replace the images everywhere, with no deploy. Removing an upload falls back to the default file below.

| What | Default file (used when nothing is uploaded) |
|---|---|
| Logo (navbar, footer, admin, error pages) | `public/assets/logo/logo.png` |
| Icon (collapsed admin sidebar) | `public/assets/icon/icon.png` |
| Browser tab icon | `public/assets/icon/favicon.ico` |

Allowed types and sizes are in `config/system.php`. Emails and the PDF invoice still use the default logo file.

Logo **size** is a Tailwind class on the tag:

```blade
{{-- resources/views/components/frontend/layout/navbar.blade.php (and footer.blade.php) --}}
<x-brand.app-logo-icon class="h-10 w-auto sm:h-11" />
```

---

## 4. Theme colors (brand, surfaces, dark mode) and font

The brand colors live in **one file**: `config/theme.php`. Change them there and the whole app follows: shop and admin (Flux components included), emails and the browser `theme-color`. No `npm run build` is needed, because `<x-brand.theme-colors />` in `partials/head` prints them as CSS variables on every page and `resources/css/app.css` only points at them. Run `php artisan config:clear` if config is cached. Surfaces, grays, the card shadow and the font stay in `resources/css/app.css` (below).

### Brand (buttons, links, shop navbar, active menu items, emails)

```php
'palettes' => [
    'brand' => [
        50 => '#eef6ff',    // lightest tint
        // …
        600 => '#1663e6',   // main brand color
        // …
        950 => '#11234f',   // darkest shade
    ],
    'gold' => [/* 50 to 900 */],   // highlights, badges, offers
],
'brand' => 600,              // shade used for primary buttons and links
'brand_dark' => 500,         // brighter shade used in dark mode
'brand_foreground' => '#ffffff',   // text on brand buttons
```

Replace all eleven brand shades together. A palette generator (search "tailwind color shades generator") gives you 50 to 950 from one hex code. Keep white text readable on the `brand` shade (WCAG AA, 4.5:1). Views keep using `brand-*` and `gold-*` classes, never raw hex or palette names like `blue-600`.

Status colors (paid, cancelled, errors, edit buttons) stay semantic Flux colors (`green`, `red`, `sky`…) in the enums and views, so they keep their meaning whatever the brand is.

### Surfaces (page, cards, top bars, sidebar, borders)

Inside `@theme { … }` (light mode):

```css
--color-canvas: #f6f8fb;                 /* page background, a cool neutral */
--color-surface: var(--color-white);     /* cards, drawers, menus */
--color-line: #e2e8f0;                   /* their borders */
--color-subtle: #eef2f7;                 /* alternate page sections (e.g. "New arrivals") */
--color-media                            /* cover and image placeholders: product covers, category tiles, thumbnails (bg-media) */
```

And inside `.dark { … }` (dark mode):

```css
--color-canvas: var(--color-zinc-950);
--color-surface: var(--color-zinc-900);
--color-line: var(--color-zinc-800);
--color-subtle: var(--color-zinc-900);
```

In light mode the content is neutral and the brand tint lives on the bars: `--color-bar` (storefront header and footer, admin header and sidebar, brand 100) with a white active chip (`--color-bar-active*`). The hero gradient is the `bg-hero` utility. `--color-zinc-500` is a step darker than Tailwind's so muted text passes WCAG AA on every light surface. Dark mode keeps its own values in `.dark` (see Dark mode below).

In views, use `bg-canvas`, `bg-surface`, `bg-subtle` and `border-line`. They switch between light and dark on their own, so **no `dark:` class is needed**.

```blade
<div class="rounded-2xl border border-line bg-surface p-6">…</div>
```

### Glass effect (whole app)

Bars and floating parts in both panels use frosted glass: `glass-bar` on the storefront header and footer and the admin header and sidebar, `glass-solid` on the drawers (storefront menu, cart, filters, admin mobile menu), which sit over a dark backdrop, and `glass` on the search popover, user and notification menus and the scroll-to-top button. Tune it once in `resources/css/app.css`: `--blur-glass` (blur strength), `--color-glass`, `--color-glass-solid` and `--color-glass-line` (panel tint, drawer tint and edge), `--shadow-glass`, each with a dark value in `.dark { … }`. Cards and content sections stay solid on purpose, for readability. Use `glass` only on overlays and floating panels.

### Alerts, banners and storefront cards

- `x-ui.alert` wraps `flux:callout`. Pick the look with `tone` (`brand`, `accent`, `info`, `success`, `warning`, `danger`, `neutral`), or with the Flux `variant` or `color` you already know. `x-ui.banner` is a tinted panel for custom content, with the same tones and `size` (`sm`, `md`, `lg`, `none`). Both get a light tint and a border in their tone from the alert and banner rules in `app.css`, and body text stays neutral gray.
- Every storefront card builds on `x-frontend.cards.card` (border, surface, card shadow, `interactive` for a brand border and shadow on hover, no movement). `product-card` (through `product-tile`), `category-card`, `feature-card` and `contact-card` use it, so a change to the parent restyles them all.
- The home page offer is `x-frontend.content.offer-banner`, a brand-tone banner with amber accents.

### Icon badges (whole app)

Every icon badge in both panels is the `x-ui.icon-badge` component (`resources/views/components/ui/icon-badge.blade.php`): `size` (`xs`, `sm`, `md`, `lg`, `xl`, `2xl`), `tone` (`brand`, `neutral`, `success`, `warning`, `danger`) and `circle`. It sets the size, the radius step, the colors and the soft `icon-badge` edge (the utility in `app.css`), so changing either file restyles every badge.

```blade
<x-ui.icon-badge size="lg" tone="success"><flux:icon.check class="size-5" /></x-ui.icon-badge>
```

### Dark mode (whole app)

Dark mode follows the brand in both panels. In the `.dark { … }` block of `resources/css/app.css`, the surface shades `--color-zinc-500` to `950` are the neutral gray with a small mix of the brand (`--theme-brand-500`), so dark surfaces, Flux components and every `dark:*-zinc-*` class carry the brand with no view changes. Text shades (zinc 50 to 400) stay neutral, so typography reads as natural gray. Use `zinc-*` for text and keep brand colors for links, calls to action, active states and icons. Borders, separators and bar edges in dark mode (`--color-line`, `--color-bar-line`, `--color-glass-line`) are faint translucent white, visible on any dark surface without standing out. Raise or lower the mix percentages (3% to 8%) or the white percentages (8% to 10%) there to make the tint or the borders stronger.

### Buttons and badges (whole app)

Views never use `<flux:button>`, `<flux:badge>` or `<flux:avatar>` directly. They use our wrappers, so colors change in one place:

- `x-ui.button` (`resources/views/components/ui/button.blade.php`) takes every Flux button prop and sets a `data-tone`: `variant="primary"` = brand, `variant="danger"` = red, a Flux `color` = that color, outline and filled = neutral, ghost and subtle = no tone (transparent). The tone rules near the top of `resources/css/app.css` give every toned button the same soft "Save" look: a light tint, matching text and a matching border. Primary (brand) buttons are solid brand and neutral buttons take the surface look, in both panels. Special colors: `color="surface"` (card colored, sign in providers), `color="field"` (looks like an input, for a control beside selects), `color="overlay"` (on an image, and the round corner buttons on the admin sign in pages), `color="transparent"` (see-through with the primary border). A ghost or subtle button with a `color` keeps no fill and only colors its text (`variant="ghost" color="danger"` for a remove icon). Pass `bar` for buttons on the header bars and the admin sidebar. Never restyle a button with color classes, add a color here instead.
- `x-ui.card` (`resources/views/components/ui/card.blade.php`) is the one card box for both panels: border, surface, radius and shadow. `as` sets the tag (`section`, `form`, `ul`, `li`, `details`…) and `interactive` adds the brand border hover (no shadow, so list items stay flat). `x-frontend.cards.card` builds on it with a title, description, actions slot and padding, and its `interactive` cards (product, category, feature, contact) also lift a soft shadow on hover. Flux cards in the admin get the same look from one rule in `app.css`.
- Brand-colored text (links, labels, hover text) uses the `--color-brand-text` token (`text-brand-text`, brand 700 in light and brand 300 in dark), set in `resources/css/app.css`. Change its shade there for the whole app.
- `x-ui.list-item` (`resources/views/components/ui/list-item.blade.php`) is the one list row: `href` makes it a link with the row hover (`--color-row-hover`) and a chevron, and the `leading` and `trailing` slots hold an icon and badges or a price. `x-ui.list-link` (settings rows) and the order rows are built on it.
- `x-ui.badge` (`resources/views/components/ui/badge.blade.php`) wraps `flux:badge` with one size for every badge: a fixed 24px height (the same as the `xs` table action buttons, so table rows stay dense and aligned), a semibold label and a soft border in the badge's own color. It is the one badge and chip for the whole app: change its look with props and put any content in its slot (text, a countdown). `color` takes any Flux color plus `white` (plain chip, such as the Lite edition badge) and `gold` (brand accent). `size="xs"` gives the compact 20px badge (edition badge, count bubbles). Pass `circle` for a round badge such as a rank number. Pass `overlay` when a badge sits on an image (product covers): it keeps the same soft look on a solid surface base so it stays readable on any photo.
- `x-ui.counter` (`resources/views/components/ui/counter.blade.php`) is the small count bubble on an icon (cart items, unread notifications): `count`, and `tone` (`danger` red by default, `accent` gold). It is built on `x-ui.badge` (`size="xs" circle`), hides itself at 0 and shows 99+ above 99.
- `x-brand.edition` (`resources/views/components/brand/edition.blade.php`) shows the edition badge (`APP_EDITION`, default `Lite`) next to the logo in the storefront navbar and the admin. Leave `APP_EDITION` empty to hide it.
- `x-ui.avatar` (`resources/views/components/ui/avatar.blade.php`) wraps `flux:avatar` with brand-tinted initials (light brand background, brand text, with dark-mode values). Photos show unchanged. Pass `bar` for an avatar inside the header bar (the user menu does), which uses the active nav pill colors (`--color-bar-active*`) so it stands out on the bar.
- `x-ui.menu-item` wraps `flux:menu.item` with outline icons, for every dropdown item in both panels. Pass `current` to mark the page you are on with the `bar-active` chip. The storefront and admin user menus (`x-frontend.layout.user-menu`, `x-backoffice.layout.user-menu`) both build on `x-navigation.user-menu` and use it. Menus share one look in both panels (frosted panel, gray outline icons, brand-tinted hover from `--color-menu-hover`).
- `x-ui.list-link` is a navigation row (icon, title, description, chevron) with the same brand-tinted hover, used by the admin Settings list.
- `x-ui.button color="surface"` is a card-colored button with a brand-tinted border and brand hover, for third-party sign in (Google, Facebook) so the provider logo carries the color. Secondary (neutral) buttons inside an `x-ui.banner` automatically take this surface look, so they stand apart from the tinted banner and from the primary button.
- In both panels, primary buttons are solid brand (`brand-600`, `brand-700` on hover) and secondary (neutral) buttons take the surface look, so the main action stands out. Every storefront page uses the `max-w-main` width through `x-frontend.layout.page` and `x-frontend.layout.section`. Change both in `resources/css/app.css` and those components.
- `x-backoffice.management.action` is one table row action (an icon `x-ui.button` with a tooltip), and `x-backoffice.management.actions` lays a row of them out on one line.

### Admin header and sidebar

The backoffice header (`layouts/backoffice.blade.php`) and sidebar use the same `glass-bar` and bar tokens as the storefront navbar (`--color-bar*`): frosted faded brand background, brand pill for the current item, bar hover. Changing the bar tokens restyles both panels.

### Corner radius (whole app)

One radius scale sets every corner in the shop and the backoffice, Flux components included: `--radius-xs` … `--radius-3xl` in `@theme` (`resources/css/app.css`). Change the values there to make the whole app sharper or rounder. Views pick a step by role, and nested elements step down one or two sizes from their container:

| Step | Value | Used for |
|---|---|---|
| `rounded-sm` | 6px | badges (status, Hot, Sale), small product thumbnails |
| `rounded-md` | 8px | menu items, small buttons, inline notices, dropdown options, small icon badges (size-8/9) |
| `rounded-lg` | 10px | buttons, inputs, selects, nav links, upload rows, icon badges (size-10/11), boxes nested in cards, images |
| `rounded-xl` | 14px | cards, panels, list containers, empty states, popovers, modals, chat bubbles, large icon badges |
| `rounded-2xl` | 20px | large page sections: banner slider, offer and call-to-action blocks |
| `rounded-full` | circle | avatars and pills |

### Card shadow

Every card uses the `shadow-card` class. Change its strength once in `resources/css/app.css` (`--shadow-card` in `@theme`), then run `npm run build`.

### Grays

`--color-zinc-50` … `--color-zinc-950` in `@theme` set every gray (text, muted labels, Flux inputs). They are pure neutral grays, so they suit any brand. Use `zinc-*` in views, never `slate-*` or `gray-*`.

### Font

```css
--font-sans: 'Instrument Sans', …;
```

**After any change:** `npm run build`

---

## 5. Browser tab title of a page

Tab shows `Title - App name`. You write only the short title; the layout adds the name.

**Most pages:** the `:title` on the **first line** (`<x-layouts::…>`):

```blade
{{-- resources/views/frontend/faq/index.blade.php --}}
<x-layouts::frontend :title="__('FAQ')">                               ← tab title
    <x-frontend.layout.page :title="__('Frequently asked questions')">   ← page heading (separate)
```

**Admin settings pages** (`resources/views/pages/settings/⚡*.blade.php`):

```php
new #[Title('Appearance')] class extends Component {
```

**Rules:** one or two words; detail pages may use data (`:title="$product->name"`); always use `__()` and the `:` before `title`.

Find a page by its current title: `grep -rn "Old title" resources/views`

---

## 6. Customer navbar and footer links

| What | File |
|---|---|
| Top menu (Home, Shop, FAQ…) | `resources/views/components/frontend/layout/nav-links.blade.php` |
| Footer links (Terms, Privacy…) | `resources/views/components/frontend/layout/footer.blade.php` |
| Account dropdown (Dashboard, My orders…) | `resources/views/components/frontend/layout/user-menu.blade.php` |

Copy an existing line:

```blade
<x-frontend.layout.nav-link :variant="$variant" :href="route('faq')" :current="request()->routeIs('faq')" wire:navigate>{{ __('FAQ') }}</x-frontend.layout.nav-link>
```

The mobile menu uses the same `nav-links` file, so it updates too.

---

## 7. Admin sidebar menu

**Where:** `config/backoffice.php`

```php
['label' => 'Products', 'icon' => 'cube', 'route' => 'manage.products.index', 'active' => 'manage.products.*', 'permission' => PermissionEnum::ManageProducts->value],
```

- `icon`: any [Heroicon](https://heroicons.com) name (works offline once you know the name, e.g. `user-group`, `photo`).
- `permission`: who can see it.
- Reorder or move lines between groups to change the menu.

**After:** `php artisan config:clear`

---

## 8. Admin Settings list (Profile, Security, …)

**Where:** `resources/views/pages/settings/⚡index.blade.php`, method `items()`

```php
['route' => 'profile.edit', 'icon' => 'user-circle', 'title' => __('Profile'), 'description' => __('Update your name, email address and photo')],
```

Gate an item by permission like the existing `Gate::allows(...)` lines.

---

## 9. Roles and their default permissions

**Where:** `app/Enums/User/UserRoleEnum.php`, method `defaultPermissions()`

```php
self::Editor => [PermissionEnum::ManageFaqs, PermissionEnum::ManageBanners],
```

**After:** `php artisan db:seed --class=RoleSeeder` (safe to re-run; it only syncs roles).

Admins can also change a role's permissions in **Settings → Roles**.

---

## 10. Status names and colors (orders, payments, products…)

**Where:** `app/Enums/<Name>Enum.php`, methods `label()` and `color()`

```php
// app/Enums/Order/PaymentStatusEnum.php
self::Pending => 'amber',
self::Paid => 'green',
```

Colors are Flux badge colors: `zinc`, `red`, `amber`, `green`, `blue`, `sky`, `purple`, ….

---

## 11. Upload limits

**Where:** `.env`

```env
PRODUCT_IMAGE_MAX_FILES=5                         # images per product, the first is the thumbnail
PRODUCT_IMAGE_MAX_SIZE_MB=3
BANNER_IMAGE_MAX_SIZE_MB=5
CATEGORY_IMAGE_MAX_SIZE_MB=3
USER_AVATAR_MAX_SIZE_MB=2
LIVEWIRE_TEMPORARY_FILE_UPLOAD_MAX_SIZE_MB=100   # must be ≥ the biggest limit above
```

Image sizes, square crops (capped at the uploaded size, never enlarged) and WebP quality (80) are in `config/product.php`, `config/category.php`, `config/banner.php` and `config/user.php`.

Also check PHP's own limits (`upload_max_filesize`, `post_max_size` in `php.ini`).

**After:** `php artisan config:clear`

---

## 12. Items per page

| What | Where |
|---|---|
| Shop page | `app/Services/Frontend/Shop/PageService.php`: `PRODUCTS_PER_PAGE = 12` |
| Admin list "Per page" choices | `app/Livewire/Concerns/InteractsWithTable.php`: `$perPageOptions = [5, 10, 20, 50, 100]` |

---

## 13. Login, checkout, password length and other limits

**Where:** `.env`

```env
THROTTLE_LOGIN_ATTEMPTS=5
THROTTLE_CHECKOUT_ATTEMPTS=5
THROTTLE_TRACK_ORDER_ATTEMPTS=10
EMAIL_VERIFICATION_EXPIRE_MINUTES=60
PASSWORD_MIN_LENGTH=6
```

**After:** `php artisan config:clear`

---

## 14. Shop content is slow to update

The storefront caches products, banners, categories and FAQs.

```env
FRONTEND_CACHE_TTL_MINUTES=180
```

Admin saves clear the cache automatically. To turn it off (for example while debugging), set `FRONTEND_CACHE_ENABLED=false` and run `php artisan config:clear`; every page then reads the database directly. To clear it by hand: `php artisan cache:clear`. If `FRONTEND_CACHE_STORE` is set (for example `redis`), clear that store: `php artisan cache:clear redis`.

---

## 15. Text on content pages

| Page | File |
|---|---|
| About | `resources/views/frontend/about/index.blade.php` |
| Contact | `resources/views/frontend/contact/index.blade.php` |
| Terms / Privacy / Refund | `resources/views/frontend/legal/*.blade.php` |
| Home page sections | `resources/views/frontend/landing/index.blade.php` |

FAQs and banners are edited in the admin panel, not in code.

Keep text inside `__('…')`.

---

## 16. Emails

| What | Where |
|---|---|
| Subject line | `app/Mail/<Domain>/<Name>Mail.php` (order emails: `app/Mail/Order/`), `subject: __('…')` |
| Email body | `resources/views/mail/<name>.blade.php` |
| Sender name and address | `.env`: `MAIL_FROM_NAME`, `MAIL_FROM_ADDRESS` |
| Mail server down | `MAIL_MAILER=failover` writes the email to `storage/logs/laravel.log` instead of breaking the page |
| Retry when the mail server is back | `.env`: `MAIL_QUEUE_ENABLED=true` (+ `MAIL_QUEUE_BACKOFF_SECONDS`, `MAIL_QUEUE_RETRY_HOURS`) and run a queue worker (SETUP_COMMANDS.md) |

A new email: add `use MailQueueTrait` to the Mailable and send it with `MailService::send($to, $mail)`, so it follows the queue switch automatically.

---

## 17. Toast messages (the pop-up after saving)

**Where:** the Livewire component's action, in `app/Livewire/...`

```php
$this->dispatch('toast-show', slots: ['text' => __('Product saved.')], dataset: ['variant' => 'success']);
```

`variant`: `success`, `warning` or `danger`.

---

## 18. Run the tests (safely)

A plain `php artisan test` **wipes your dev database**. Always use:

```bash
php artisan optimize:clear && env $(grep -oE '^[A-Z_][A-Z0-9_]*' .env | sed 's/^/-u /') php artisan test --compact tests/Feature/Frontend
```

Format PHP before committing: `vendor/bin/pint --dirty`

---

## 19. Maintenance mode

**Where:** admin → Settings → System → Maintenance mode. Switch it on, optionally set a start and end time (empty start = now, empty end = until turned off) and save. Visitors get the "back soon" page; admins keep using the admin panel. The bypass link shown on that page lets you preview the public site in one browser.

`php artisan down` / `php artisan up` change the same setting. The page itself is `resources/views/errors/503.blade.php`.

---

## 20. Search boxes

| What | Where |
|---|---|
| Wait after typing before any search runs (whole app) | `.env`: `APP_SEARCH_DEBOUNCE_MS` (default `300`) |
| Results shown in the navbar search | `app/Livewire/Frontend/Search/Index.php`: `LIMIT` (5) and `MIN_LENGTH` (2) |
| A new live search box | Use `<x-ui.search-input model="…" clearable />`, never your own `wire:model.live.debounce` |

---

## 21. Drawers, menus and overlays

| What | Change it in |
|---|---|
| Every drawer (storefront menu, filters, cart, admin mobile menu): width, backdrop, slide | `resources/views/components/ui/drawer.blade.php` |
| Every action menu (user menu, notifications, avatar menu) | `resources/views/components/ui/dropdown.blade.php`. Props: `position`, `align`, `gap`, `menu-class`, `lock-scroll` (page can't scroll while open). Menus in a sticky bar (navbar, admin header) are pinned automatically and stay put while scrolling; the placement logic is the `dropdown` component in `resources/js/app.js` |
| Floating panels such as the navbar search | `resources/views/components/ui/popover.blade.php` |
| Dimmed backdrop behind drawers and storefront modals | the `scrim` utility in `resources/css/app.css` (used by `resources/views/components/ui/backdrop.blade.php` and by storefront modals through `data-panel="frontend"` on the layout body) |

Open a drawer from any button with `<x-ui.drawer-trigger name="cart">…</x-ui.drawer-trigger>`.

## 21a. Form buttons and galleries

| What | Change it in |
|---|---|
| Where Save and Cancel sit in every page and modal form (right side, full width on phones) | `resources/views/components/form/actions.blade.php`. Wrap a form's buttons in `<x-form.actions>`, never align them by hand |
| Address fields on both profile pages | `resources/views/components/form/address-form.blade.php` (logic in `app/Livewire/Concerns/InteractsWithAddresses.php`) |
| Product image gallery (shop page and admin Images modal) | `resources/views/components/media/gallery.blade.php`. Props: `zoomable` (full-screen button), `first-label` (badge on the first thumbnail), `thumbnail-action` (Livewire method behind the star on each thumbnail, which saves that image as the thumbnail right away). Slots: `empty`, `overlay` |
| Product image order | Staff use the up and down arrows in the Upload Images modal, or the star on a thumbnail in the Images preview (saved right away). The first image is the thumbnail |

## 22. Admin URL

Set `ADMIN_ROUTE_PREFIX` in `.env` (default `backoffice`). The admin login moves to `/{prefix}/login` and every admin page to `/{prefix}/…`. Run `php artisan optimize` afterwards. Links use route names, so nothing else changes.

## 23. SEO and link previews

- **Where:** the `# === SEO ===` group in `.env`: `SEO_DESCRIPTION` (default description), `SEO_IMAGE` (default share image, empty uses the logo), `SEO_INDEXING_ENABLED` (`false` on staging hides every page). Product pages use their own description and first image.
- **Per page:** pass `description` and `image` to `<x-layouts::frontend>`. Tags come from `x-ui.seo`.
- **Hidden from search:** every page outside the storefront layout (admin, auth, errors) gets `noindex`.
- **Sitemap:** `/sitemap.xml` lists the public pages and every in-stock or out-of-stock product (archived products are left out). Submit it once in Google Search Console.

## 24. Category depth

Set `APP_CATEGORY_LEVEL` in `.env` (1–3, default 2), then `php artisan config:clear`. The admin category pickers show one dropdown per level, and only the first is required. The shop sidebar, filter drawer, breadcrumb and AI assistant follow the same depth. Lowering it hides deeper categories everywhere without deleting them.

---

## Finding anything else

```bash
grep -rn "Text you see on screen" resources/views app   # find where a label lives
php artisan route:list --path=settings                  # find a page's route and name
```
