---
paths:
  - resources/views/**
---

# Views

## Markup only
No `@php`, no queries and no business logic. Data comes from props, `compact()` or computed properties.

## Reuse components
Components live in purpose folders under `resources/views/components`. Add new ones to the matching folder; never at a folder root.
- Shared: `brand/` (logo), `ui/` (`x-ui.price`, `badge`, `tabs`, `error-page`, `theme-toggle`, `backdrop` for every dimmed overlay (drawers), `dropdown` for every action menu (`trigger` slot + menu items; wraps `flux:dropdown`/`flux:menu`; never use `flux:dropdown` directly. Props: `position` (bottom, top, left, right), `align` (start, center, end), `gap`, `menu-class`, `lock-scroll`. The page stays scrollable unless `lock-scroll`. A menu whose button is inside a sticky or fixed bar is pinned automatically (the `dropdown` Alpine component in `app.js`), so it stays put while the page scrolls; other menus follow their button. Placement logic lives only in `app.js` (`dropdown`) and the `.dropdown-fixed` class in `app.css`), `popover` for every free-form floating panel (`trigger` slot + content; full width under the closest `[data-popover-anchor]` on phones, under the trigger from `sm`, re-placed on resize; the navbar search uses it), `search-input`: every live search box uses it, so typing waits `APP_SEARCH_DEBOUNCE_MS` and its `clearable` button clears at once with one request; never write `wire:model.live.debounce` or a custom clear button for a search by hand), `navigation/` (`user-menu`, `breadcrumb`), `notifications/`, `media/`, `form/`, `auth/`.
- Admin: `backoffice/layout/` (sidebar, nav, `main-content-container` with `title`, `subtitle`, `breadcrumbs` props and `badge` (right-aligned on the subtitle row) and `actions` slots), `backoffice/management/` (`table`, `form-modal`, `search`, `filter`, `per-page`, `pagination`, `details-modal`), `backoffice/form/` (`toggle-field`, `permission-checkboxes`).
- Storefront: `frontend/layout/` (`page`, `section`, navbar, footer), `frontend/cards/` (`card`, `product-card`/`product-tile` for the grid card, `product-list-item` for the row view of a product), `frontend/content/` (`notice`, `countdown` for a compact time-left label (`31d : 05h : 51m : 42s`, days hidden when zero, `separator` prop) with `to`, `color` (red, brand, muted), `icon`, `live` (ticks every second with the `countdown` Alpine component in `app.js`; default true) props, a label slot and an `expired` slot, FAQ, legal), `frontend/orders/` (`items`, `row`, `status`), `frontend/drawers/` (`drawer` base with `name`, `label`, `side`, `static`, `mobile` props and `header`/body/`footer` slots; `navigation-drawer`, `filter-drawer` and the cart are built on it. Open one with `<x-frontend.drawers.trigger name="…">` or `$this->dispatch('drawer-open', name: '…')`; inside the slots `open = false` closes it).

New components must be generic and prop-driven.

## UI stack
Flux UI with Heroicons in the backoffice, and Material Symbols on the storefront. Use Tailwind 4, with Alpine only for client-only behaviour. Link with named routes and `wire:navigate`. Wrap every string in `__()`.

## Colors
Use the central tokens from `resources/css/app.css`: `bg-canvas` (page), `bg-surface` and `border-line` (cards, bars, sidebar), `bg-subtle` (alternate sections), `brand-*` for brand color and `zinc-*` for grays. They already switch for dark mode, so don't pair them with `dark:` classes. Every card (a bordered surface box) also gets `shadow-card` (the `--shadow-card` token); never hard-code a card shadow. Never hard-code hex colors or use `slate-*`/`gray-*`.

## No semicolons or em dashes in text
User-facing text never contains a semicolon (;) or an em dash (—): `__()` strings, Blade copy, validation and toast messages, mail, PDFs and seeded content alike. Split the sentence or use a comma instead. Show an empty value as `-`, not `—`.
