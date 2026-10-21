---
paths:
  - resources/views/**
---

# Views

## Markup only
No `@php`, queries or business logic. Data comes from props, `compact()` or computed properties.

## UI stack
Flux UI + Heroicons in the backoffice, Material Symbols on the storefront, Tailwind 4, Alpine only for client-only behaviour. Named routes with `wire:navigate`. Every string in `__()`.

## Reuse components
Components live in purpose folders under `resources/views/components`, never at a folder root. New ones are generic and prop-driven.

**Shared**
- `brand/` (logo), `navigation/` (`user-menu`, `breadcrumb`), `notifications/`, `media/`, `form/`, `auth/`.
- `ui/`: `price`, `badge`, `tabs`, `error-page`, `theme-toggle`, `backdrop` (every dimmed overlay). Its look is the `scrim` utility in `app.css`, which also styles the backdrop of every storefront modal. Change the backdrop only there.
- `ui/dropdown`: every action menu (`trigger` slot + `<x-ui.menu-item>`s). Never use `flux:dropdown` directly. Props: `position` (bottom, top, left, right), `align` (start, center, end), `gap`, `menu-class`, `lock-scroll` (page scrolls unless set). Menus in sticky or fixed bars pin automatically. Placement logic lives only in the `dropdown` Alpine component in `app.js` and `.dropdown-fixed` in `app.css`.
- `ui/popover`: every free-form floating panel (`trigger` slot + content). Full width under the closest `[data-popover-anchor]` on phones, under the trigger from `sm`.
- `ui/search-input`: every live search box (debounce from `APP_SEARCH_DEBOUNCE_MS`, `clearable`). Never hand-write `wire:model.live.debounce` or a clear button for search.

**Admin**
- `backoffice/layout/`: `navigation` (the nav list, shared by the desktop `sidebar` and the mobile `navigation-drawer`), `user-menu`, `main-content-container` (`title`, `subtitle`, `breadcrumbs` props, `badge` and `actions` slots).
- `backoffice/management/`: `table`, `form-modal`, `search`, `filter`, `per-page`, `pagination`, `details-modal`, `actions` wrapper, `action` for every row action (icon button with tooltip and `aria-label`, never a text button).
- `backoffice/form/`: `toggle-field`, `permission-checkboxes`.
- `form/actions`: wraps the submit row of every page and modal form. Buttons sit at the end (right), full width on phones. Never hand-align a Save button. Only auth cards (full-width buttons), the checkout summary and inline search bars like order tracking are exempt.
- Records with media (products, categories, banners): save without files first, then upload from the row's Upload action (`x-media.upload`, or `x-media.gallery-upload` for several images) and view from Media (`x-media.preview` or `x-media.gallery` with `:zoomable="false"`). Never put a file upload in the create or edit form.
- Storefront image galleries use `x-media.gallery` (main image with thumbnails under it, not a slider).

**Storefront**
- `frontend/layout/`: `page`, `section`, navbar, footer.
- `frontend/cards/`: `card` is the parent of every storefront card (`framed`, `padded`, `interactive` hover with no lift, `as`). `product-card`/`product-tile` (grid), `product-list-item` (row), `category-card` (same shape as `product-card`: square image frame with an icon fallback, name below), `feature-card`, `contact-card`.
- `frontend/content/`: `offer-banner`, `notice`, `empty-state` (every "nothing here" box: `title`, `icon`, actions in the slot), FAQ, legal, `countdown` (`31d : 05h : 51m : 42s`, zero days hidden, props `to`, `color` red/brand/muted, `icon`, `live` default true, `separator`, `variant` inline or `boxes` (label left of compact unit cards, used by the offer banner), plus label and `expired` slots).
- `frontend/orders/`: `items`, `row`, `status`.
- `frontend/drawers/`: `navigation-drawer`, `filter-drawer`. Every drawer in both panels (these, the cart, the admin mobile menu) is built on `x-ui.drawer` (`name`, `label`, `side`, `static`, `mobile`, `header`/body/`footer` slots, one soft brand glass tone like the header, with the backdrop). Open with `<x-ui.drawer-trigger name="…">` or `$this->dispatch('drawer-open', name: '…')`. `open = false` closes it.

## Use the ui component, never raw markup
- `<x-ui.button>` not `flux:button`. Bar buttons take `bar`. Special looks use `color` (`surface`, `field`, `overlay`, `transparent`), never color classes.
- `<x-ui.badge>` not `flux:badge`: the one badge and chip (`color` incl. `white`, `gold`, `size="xs"`, `circle`). Never override its colors or size. On images use `overlay`, never `solid`.
- `<x-ui.counter>` for count bubbles, `<x-ui.avatar>` not `flux:avatar`, `<x-ui.icon-badge>` (size, tone, circle) for icon badges.
- `<x-ui.list-item>` / `<x-ui.list-link>` for every list row, `<x-ui.card>` (`as`) for every card box.
- `<x-ui.alert>` for alerts, `<x-ui.banner>` (`tone`, `size`) for tinted panels. Never hand-write a tinted callout.

## Colors
- Brand and gold values live only in `config/theme.php`, printed as CSS variables by `<x-brand.theme-colors />`.
- Use tokens from `app.css`: `bg-canvas` (neutral page), `bg-surface` + `border-line` (white cards and panels), `bg-subtle` (alternate sections), `bg-media` (image placeholders), `bg-bar` (header, footer, admin sidebar: the only tinted chrome), `bg-hero` (the hero gradient), `brand-*`, `zinc-*`. They switch for dark mode, so no `dark:` pairs.
- Text is `zinc-*`. Brand color only for links, calls to action, active states and icons. Brand text uses `text-brand-text`. Active nav and menu items (header, drawers, menus, sidebar) use the `bar-active` white chip.
- Overlays, floating panels and bars in both panels (popovers, drawers, menus, storefront header and footer, admin header and sidebar) use `glass` / `glass-solid` / `glass-bar`, never hand-written `backdrop-blur`. Cards stay solid.
- Never hex colors, `slate-*`/`gray-*`, or raw palettes (`blue-*`, `sky-*`) for brand elements. Raw Flux colors only for semantic status (success, danger, info).

## Radius and shadow
By role (HOW_TO.md §4): badges `rounded-sm`, menu items and small controls `rounded-md`, buttons, inputs and boxes inside cards `rounded-lg`, cards and panels `rounded-xl`, page sections `rounded-2xl`, avatars `rounded-full`. Nested elements step down. No arbitrary radii. Every card gets `shadow-card`, never a hard-coded shadow.

## Text
User-facing text never contains a semicolon or an em dash. Split the sentence or use a comma. Empty values use `appEmptyValue()` or `<x-ui.empty-value />`, never a hard-coded `-`.

## Always use outlined icons
Every icon in both panels uses the outline style. `x-ui.button` and `x-ui.menu-item` default to outline, so never pass `variant="mini"`, `"micro"` or `"solid"` (or the `icon:variant` / `icon-variant` versions). For a standalone `<flux:icon>`, set the size with a class instead (`size-4` for small, `size-5` for normal).
