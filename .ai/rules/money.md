---
paths:
  - app/Services/**
  - app/Models/**
  - database/migrations/**
---

# Money and currency

## Whole units only
Prices, totals, discounts, salaries and payments are integers (e.g. 550, never 550.50).
- **Columns:** `unsignedInteger`, cast `'integer'`.
- **Rounding:** once, at the point of calculation, with `(int) round(...)`. Discounted prices, offer discounts and percentage payouts round half up. Sum the already-rounded line totals.
- **Input:** validate money as `integer`; inputs use `step="1"`.

## Currency from config
The code is `appCurrency()` (`APP_CURRENCY`, e.g. BDT) and the symbol is `appCurrencySymbol()` (`APP_CURRENCY_SYMBOL`, e.g. ৳). Never hard-code either.
- **Display:** use `formatMoney($amount)` or `<x-ui.price>`.
- **Translated strings:** pass the formatted amount as a placeholder: `__('Total: :amount', ['amount' => formatMoney($x)])`.
- **PDFs:** pass `appCurrency().' '` as the symbol, because dompdf fonts may not have the ৳ glyph.
