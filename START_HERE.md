# Start here: reading order for new developers

New to this project? Read the docs in this order. Each step builds on the one before. You don't need everything on day one: steps 1–4 get the app running and make the code readable, and the rest you can read when you need it.

## Day one: run it and understand it

| # | File | Why read it | Time |
|---|---|---|---|
| 1 | [README.md](README.md) | What the app is, how customers and staff use it, the pages, the stack, and sample accounts | 5 min |
| 2 | [SETUP.md](SETUP.md) | Install it locally and understand every `.env` setting. Read **Local installation** now; the production and cPanel parts can wait | 15 min |
| 3 | [SETUP_COMMANDS.md](SETUP_COMMANDS.md) | Every command in one place. Note the **safe test command**: a plain `php artisan test` wipes your dev database | 5 min |
| 4 | [TECHNICAL.md](TECHNICAL.md) | Architecture, folder map, database tables, core flows, caching, security and conventions. The most important read before touching code | 30 min |

After step 4, open the app (`composer dev`), log in with the sample accounts, and click through the shop and the backoffice.

## Week one: go deeper

| # | File | Why read it | Time |
|---|---|---|---|
| 5 | [DIAGRAM.md](DIAGRAM.md) | The ERD, class diagrams and sequence diagrams that show TECHNICAL.md visually. Read alongside it, or whenever a flow is unclear | 15 min |
| 6 | [AGENTS.md](AGENTS.md) | The project's **golden rules** (services own logic, no models in Livewire, money as integers…), the working loop and the test rules. Written for AI agents, but the rules apply to everyone | 10 min |
| 7 | [.ai/rules/index.md](.ai/rules/index.md) and the rule files it lists | Short, specific rules per folder: services, Livewire, views, models, routes, config, money, tests. Read the one for the folder you're about to change | 2 min each |
| 8 | [HOW_TO.md](HOW_TO.md) | Recipes for everyday changes: titles, links, colours, limits, `.env` | 10 min |
| 9 | [INCOMPLETE.md](INCOMPLETE.md) | Known gaps, leftovers and ideas, so you don't rediscover them or build on something unfinished | 5 min |

## When you need it

| File | Read it when |
|---|---|
| [.ai/playbooks/new-feature.md](.ai/playbooks/new-feature.md) | You build a new feature, screen, model or report. It is the step-by-step recipe, with the sibling file to copy for each layer |
| [OUR_RAG.md](OUR_RAG.md) | You work on the **Ask AI** assistant, or want to learn RAG. It explains every AI file and includes a build-your-own guide |
| README → [Reusing the AI assistant in another app](README.md#reusing-the-ai-assistant-in-another-app) | You use this project as a boilerplate and need to adapt the AI prompts in `config/ai-prompts.php` |
| [SETUP.md](SETUP.md) → Production installation / cPanel | You deploy to a VPS or shared hosting |
| [CLAUDE.md](CLAUDE.md) | You use Claude Code. It imports AGENTS.md and adds two Claude-specific notes |

## A suggested first task

1. Run the safe test command from SETUP_COMMANDS.md and see everything pass.
2. Pick a small item from HOW_TO.md (for example, change a page title) and make the change.
3. Read the matching rule in `.ai/rules/`, run `vendor/bin/pint --dirty`, and run the related test file.

You will then have touched the docs, the code, the rules and the tests once each, which is the whole workflow.
