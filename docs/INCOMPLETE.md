# Incomplete features and future improvements

The core shop works end to end. Customers can browse, check out as a guest or with an account, pay, track orders and download invoices. Staff can run the catalogue, orders, offers, banners, staff, roles and reports. This file lists what is **not finished**, what is **left over**, and what would be **worth improving later**.

Status legend: 🔴 fix before launch · 🟠 should fix soon · 🟢 nice to have

## 1. Before launch

| | Item | Why it matters | What to do |
|---|---|---|---|
| 🔴 | **Review the legal pages** (`resources/views/frontend/legal/*`) | The terms, privacy and refund texts describe how the app works but were not written by a lawyer | The owner approves the wording and updates "Last updated" |
| 🔴 | **Working email on the live server** | Customers cannot log in until they verify their email | Configure `MAIL_*` and send a test registration |
| 🔴 | **Production `.env`** | Demo data must never be seeded live | Set `APP_ENV=production`, `APP_DEBUG=false`, `DB_SEEDING_ENABLED=false`, `APP_CURRENCY`, `APP_CURRENCY_SYMBOL`, `DB_SEED_ADMIN_EMAIL`, `DB_SEED_ADMIN_PASSWORD` (see SETUP_ADVANCED.md) |

## 2. Small design gaps

| | Item | Details |
|---|---|---|
| 🟠 | **Resend cooldowns live in the session** | The 5-minute wait for verification and password reset emails is stored in the session, so another browser can resend sooner. The per-minute throttle still applies. A per-user cache key would enforce the wait everywhere |
| 🟢 | **Hitting the verification resend limit shows the "Too many requests" page** | An inline message would be friendlier |
| 🟢 | **Emails and PDF invoices use the default logo** | The uploaded brand logo shows on every page, but the mail header and the invoice still use the logo file in `public/assets` |
| 🟢 | **Percentage compensation is whole percent only** | Staff percentages are integers (e.g. 7%, not 7.5%), in line with the whole-unit money rule |

## 3. Not built yet

| | Item | Details |
|---|---|---|
| 🟠 | **Product image size** | The `large` product image is cropped to 1000×1000 (`config/product.php`). Raising it to about 2000px and fitting instead of cropping would keep detail and shape |
| 🟢 | **Saved addresses** | Each user has one shipping and one billing address as free text. Several saved addresses or split fields (city, area, postcode) are not built |
| 🟢 | **SEO extras** | Meta description, OpenGraph/Twitter tags, canonical URLs, `noindex` on private pages, `/sitemap.xml`, Settings → SEO and per product and category SEO are in place. Still open: clean URLs (`/product-slug`, `/category-slug`) with redirects, category SEO on a category page, structured data (JSON-LD) for products and breadcrumbs, and submitting the sitemap in Google Search Console |
| 🟠 | **AI assistant v3** | The beta assistant chats and suggests products (RAG). Adding or removing cart items by chat is planned in OUR_RAG.md (section 14). Replies are not streamed, the chat resets on reload, and vector search runs in PHP over cached vectors (fine for a few thousand products) |
| 🟢 | **Automatic payment verification** | Stripe is verified automatically. bKash and Nagad are still checked by hand, and the gateway interface is ready for their APIs |
| 🟠 | **Online payment v2** | Unpaid Stripe orders keep their stock until staff cancel them (no auto cancel), refunds are not sent to Stripe (staff refund in the Stripe dashboard), and no email goes out when an online payment succeeds |
| 🟢 | **Automatic reminders and cancellations** | Staff act by hand. Automating needs a scheduled command and cron |
| 🟢 | **Self-service account deletion** | The privacy policy asks customers to contact the team |
| 🟢 | **Activity log** | No record of who changed what in the backoffice |
| 🟢 | **SMS or WhatsApp notifications** | Only email today |

## 4. Code health

| | Item | Details |
|---|---|---|
| 🟢 | **PHPStan warnings** | Mostly in older Livewire components: missing paginator generics, `list` vs `array` shapes, unnecessary `?->`. None break anything. Fix gradually when touching those files |
| 🟢 | **`jobs` / `job_batches` tables** | Used only when `MAIL_QUEUE_ENABLED=true` (order emails). Otherwise empty |
| 🟢 | **Order detail lists its lines inline** | `frontend/orders/detail.blade.php` repeats the order-line markup instead of using `x-frontend.orders.items` like tracking and confirmation |
| 🟢 | **Seeded accounts share one password** | `123456`, local only. Keep `DB_SEEDING_ENABLED=false` live |

## Suggested order

1. Everything in section 1.
2. SEO extras: clean URLs and JSON-LD.
3. The server-side resend cooldown.
4. Section 3 as the business grows.
