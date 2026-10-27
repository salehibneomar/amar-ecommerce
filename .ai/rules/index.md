# Project Rules Index

Read a rule file only when its trigger matches the change. Most small changes need one file or none.

| Read when the change… | Rule file |
| --- | --- |
| adds or reads a config key, `.env` key or helper (`config/**`, `app/Helpers/**`, `.env.example`) | .ai/rules/config-helpers.md |
| adds or changes a route, controller, middleware or policy (`routes/**`, `app/Http/**`, `app/Policies/**`) | .ai/rules/http-routes.md |
| adds or changes a Livewire component (`app/Livewire/**`) | .ai/rules/livewire.md |
| changes a model, migration, factory or seeder (`app/Models/**`, `database/**`) | .ai/rules/models-database.md |
| touches prices, totals, discounts, payments or currency display | .ai/rules/money.md |
| adds or changes a service, cache, stock, payment or mail flow (`app/Services/**`) | .ai/rules/services.md |
| creates, renames or moves a class, trait or provider | .ai/rules/structure.md |
| adds or changes a test (`tests/**`) | .ai/rules/tests.md |
| adds or changes Blade markup (`resources/views/**`) | .ai/rules/views.md |
