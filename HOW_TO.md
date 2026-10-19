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
| Shop content (books, banners, FAQs) | Wait for the cache, or `php artisan cache:clear` (see §14) |
| Not sure | `php artisan optimize:clear`, then hard refresh the browser (Ctrl+Shift+R) |

---

## 1. App name, currency, timezone

**Where:** `.env`

```env
APP_NAME="নতুন কৃষক"
APP_CURRENCY=BDT
APP_CURRENCY_SYMBOL=৳
APP_TIMEZONE=Asia/Dhaka
```

Never type `৳` or `BDT` in code. Show money with `formatMoney($amount)`.

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
SHOP_ORDER_PREFIX=NK                 # order IDs look like NK-ABC123
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

Every color lives in **one file**: `resources/css/app.css`. Change a value there, run `npm run build`, and the whole app (shop and admin, Flux components included) follows.

### Brand (buttons, links, shop navbar, active menu items)

Inside `@theme { … }`:

```css
--color-brand-50: #f0fbf2;    /* lightest tint */
…
--color-brand-600: #22893a;   /* main brand color */
…
--color-brand-950: #072812;   /* darkest shade */
--color-gold-400: #facc15;    /* highlights, badges */
```

Replace all eleven `--color-brand-*` shades together. A palette generator (search "tailwind color shades generator") gives you 50–950 from one hex code. Flux's primary buttons use the brand through `--color-accent`, so they change too.

Dark mode uses a slightly brighter brand for buttons. Change it in the `.dark { … }` block:

```css
--color-brand-dark: #2b9c4f;
```

### Surfaces (page, cards, top bars, sidebar, borders)

Inside `@theme { … }` (light mode):

```css
--color-canvas: var(--color-zinc-50);    /* page background */
--color-surface: #fafafa;                /* cards, navbars, sidebar, drawers */
--color-line: var(--color-zinc-200);     /* their borders */
--color-subtle: var(--color-zinc-100);   /* alternate page sections (e.g. "New arrivals") */
```

And inside `.dark { … }` (dark mode):

```css
--color-canvas: var(--color-zinc-950);
--color-surface: var(--color-zinc-900);
--color-line: var(--color-zinc-800);
--color-subtle: var(--color-zinc-900);
```

In views, use `bg-canvas`, `bg-surface`, `bg-subtle` and `border-line`. They switch between light and dark on their own, so **no `dark:` class is needed**.

```blade
<div class="rounded-2xl border border-line bg-surface p-6">…</div>
```

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
['label' => 'Books', 'icon' => 'book-open', 'route' => 'manage.books.index', 'active' => 'manage.books.*', 'permission' => PermissionEnum::ManageProducts->value],
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

## 10. Status names and colors (orders, payments, books…)

**Where:** `app/Enums/<Name>Enum.php`, methods `label()` and `color()`

```php
// app/Enums/Order/PaymentStatusEnum.php
self::Pending => 'amber',
self::Paid => 'green',
```

Colors are Flux badge colors: `zinc`, `red`, `amber`, `green`, `blue`, `sky`, `purple`, ….

---

## 11. Upload limits and book preview

**Where:** `.env`

```env
BOOK_STOCK_TYPE=both                              # both | digital | physical
BOOK_FILE_MAX_SIZE_MB=100
BOOK_COVER_MAX_SIZE_MB=3
BANNER_IMAGE_MAX_SIZE_MB=5
CATEGORY_IMAGE_MAX_SIZE_MB=3
USER_AVATAR_MAX_SIZE_MB=2
LIVEWIRE_TEMPORARY_FILE_UPLOAD_MAX_SIZE_MB=100   # must be ≥ the biggest limit above
BOOK_PREVIEW_PAGES=5                              # free preview length
```

`BOOK_STOCK_TYPE=physical` sells printed books only: the book form hides the stock type and free options, and the upload dialog takes a preview PDF (a few sample pages) that customers read as it is, with no trimming. `digital` hides the stock type choice. With a single type the books list and details hide the stock type badge, and the demo seed makes only that type. `both` works as before.

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

The storefront caches books, banners, categories and FAQs.

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
$this->dispatch('toast-show', slots: ['text' => __('Book saved.')], dataset: ['variant' => 'success']);
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
| Every customer drawer (menu, filters, cart): width, backdrop, slide | `resources/views/components/frontend/drawers/drawer.blade.php` |
| Every action menu (user menu, notifications, avatar menu) | `resources/views/components/ui/dropdown.blade.php`. Props: `position`, `align`, `gap`, `menu-class`, `lock-scroll` (page can't scroll while open). Menus in a sticky bar (navbar, admin header) are pinned automatically and stay put while scrolling; the placement logic is the `dropdown` component in `resources/js/app.js` |
| Floating panels such as the navbar search | `resources/views/components/ui/popover.blade.php` |
| Dimmed backdrop behind drawers | `resources/views/components/ui/backdrop.blade.php` |

Open a drawer from any button with `<x-frontend.drawers.trigger name="cart">…</x-frontend.drawers.trigger>`.

## 22. Admin URL

Set `ADMIN_ROUTE_PREFIX` in `.env` (default `backoffice`). The admin login moves to `/{prefix}/login` and every admin page to `/{prefix}/…`. Run `php artisan optimize` afterwards. Links use route names, so nothing else changes.

## 23. SEO and link previews

- **Where:** the `# === SEO ===` group in `.env`: `SEO_DESCRIPTION` (default description), `SEO_IMAGE` (default share image, empty uses the logo), `SEO_INDEXING_ENABLED` (`false` on staging hides every page). Book pages use their own description and cover.
- **Per page:** pass `description` and `image` to `<x-layouts::frontend>`. Tags come from `x-ui.seo`.
- **Hidden from search:** every page outside the storefront layout (admin, auth, errors) gets `noindex`.
- **Sitemap:** `/sitemap.xml` lists the public pages and every in-stock or out-of-stock book (archived books are left out). Submit it once in Google Search Console.

## 24. Category depth

Set `APP_CATEGORY_LEVEL` in `.env` (1–3, default 2), then `php artisan config:clear`. The admin category pickers show one dropdown per level, and only the first is required. The shop sidebar, filter drawer, breadcrumb and AI assistant follow the same depth. Lowering it hides deeper categories everywhere without deleting them.

---

## Finding anything else

```bash
grep -rn "Text you see on screen" resources/views app   # find where a label lives
php artisan route:list --path=settings                  # find a page's route and name
```
