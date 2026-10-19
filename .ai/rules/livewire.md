---
paths:
  - app/Livewire/**
---

# Livewire

## No models in components
Never import or query `App\Models`. Inject services in `boot()`, expose data through `#[Computed]`, pass IDs or scalars to services, and `unset($this->computed)` after a write.

## Layout
- Classes: `app/Livewire/{Backoffice,Frontend}/<Area>/Index.php`.
- Views: `resources/views/components/{backoffice,frontend}/<area>/index.blade.php`.
- Page shells: `resources/views/{backoffice,frontend}/<area>/index.blade.php`.

## Admin screens
Use `InteractsWithTable` + `WithPagination`, with `showForm`/`showDeleteConfirmation` modals, `editing<Model>Id`, `saveX()`, `confirmDeleteX()`, `deleteX()` and `resetForm()`. Reference: `Backoffice/Faqs/Index.php`.

## Validation and feedback
Validate inside the action with `$this->validate([...])`. Report success with `$this->dispatch('toast-show', slots: ['text' => $message], dataset: ['variant' => 'success'])`.
