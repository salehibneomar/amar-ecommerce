---
paths:
  - app/Livewire/**
---

# Livewire

## No models in components
Never import or query `App\Models`. Inject services in `boot()`, expose data through `#[Computed]`, pass IDs or scalars to services, and `unset($this->computed)` after a write.

## Layout
- Classes: `app/Livewire/{Backoffice,Frontend}/<Area>/Index.php`. An area with several pages names each class after its page (`Backoffice/Settings/Profile.php`, `Frontend/Cart/Checkout.php`).
- Views: `resources/views/components/{backoffice,frontend}/<area>/<page>.blade.php`, returned from `render()`.
- Page shells: `resources/views/{backoffice,frontend}/<area>/<page>.blade.php`, returned by a controller (`.ai/rules/http-routes.md`). The shell sets the page title, so components carry no `#[Title]`.
- Only class-based components. No single-file (`⚡`) components or `Route::livewire` pages.

## Admin screens
Use `InteractsWithTable` + `WithPagination`, with `showForm`/`showDeleteConfirmation` modals, `editing<Model>Id`, `saveX()`, `confirmDeleteX()`, `deleteX()` and `resetForm()`. Reference: `Backoffice/Faqs/Index.php`.

## Validation and feedback
Validate inside the action with `$this->validate([...])`. Report success with `$this->dispatch('toast-show', slots: ['text' => $message], dataset: ['variant' => 'success'])`.
