# Roadmap — Feature & Improvement Ideas

A prioritized backlog tailored to this codebase (Laravel 12 + React 19 + Inertia, multi-tenant German invoicing with GoBD / E-Rechnung compliance). Grouped by category with quick wins and longer plays.

## Compliance & billing (German market)
- **ZUGFeRD PDF/A-3 upgrade**: today `generateZugferd()` embeds the XML in a plain PDF; make it a valid PDF/A-3b so it’s legally archive-compliant. Add an automated XRechnung schema validation step (KoSIT validator or `horstoeko/zugferd-validator`).
- **GoBD audit completeness**: extend `InvoiceAuditLog` usage to cover `sent`, `paid`, `payment recorded`, `reminder sent`, `cancelled`, `PDF generated`, `XRechnung downloaded`, and protect logs from deletion (append-only table + DB triggers or file-signed hash chain).
- **Dunning (Mahnverfahren) automation polish**:
  - Per-customer reminder policy overrides (grace days, fees, max level).
  - Auto-pause reminders while a `Zahlungsvereinbarung` (payment plan) is active.
  - Inkasso handoff export (CSV/PDF bundle) for an `Inkasso-Partner`.
- **Abschlags-/Schlussrechnung linkage**: enforce that `schlussrechnung` totals = sum(`abschlag`) + remainder and render the `Abschlagsübersicht` block automatically.
- **OSS / § 3a UStG** support for EU B2C: per-country VAT rates + OSS quarterly export.
- **Kleinunternehmer UI safeguards**: block tax fields globally when `is_small_business`, not just in totals.
- **Customer invoicing defaults**: remember last `vat_regime` per customer, plus B2G flag derived from Leitweg-ID.

## Payments
- **Online payment**: Stripe / Mollie / PayPal links embedded in the invoice and in the email (SEPA, credit card, Klarna). Auto-reconcile to `Payment`.
- **Bank reconciliation (FinTS / HBCI or CSV import)**: match `Umsatz` to invoice by amount + `Verwendungszweck` + customer; propose matches for the user.
- **Partial payments & overpayments**: proper handling with `credit_notes` and `kundenguthaben` balances.
- **Skonto auto-tracking**: mark invoice skonto as used/expired automatically, show warning banner.

## Products & inventory
- **Simple inventory**: stock per product + warehouse, decrement on invoice, restock on Storno.
- **Bundles / packages** (kit products) that expand into line items.
- **Price lists** per customer / customer group.
- **Time tracking → invoice**: convert tracked hours to Abschlag/Schlussrechnung line items (useful for service companies).

## Offers / sales
- **Digital offer signing**: public link + e-signature (typed or drawn), timestamp, audit trail.
- **Offer versioning**: keep every revision; show diff to customer.
- **Convert offer → order (Auftragsbestätigung)** before the invoice.
- **Accept/reject notifications** back to company owner.

## Documents / storage
- **Folders + tagging hierarchy** and server-side search (already have tags; add full-text on name/description and OCR via Tesseract for scanned Rechnungen).
- **Versioning** for documents + retention policies (GoBD 10-year rule).
- **DATEV export** for expenses/invoices.

## Employee portal
- **Payslip upload & signed acknowledgment** (Lohnabrechnung).
- **Leave / Urlaubsanträge**: request, approval flow, Urlaubskonto.
- **Time tracking** with Stempeluhr and monthly overview.
- **Expense reimbursement**: employee uploads receipt, admin approves, pushes to expenses.

## Multi-tenancy & admin
- **Subscription / plan gating** per company (feature flags for `e-rechnung`, `employees`, `inventory`).
- **Usage metering** (invoices/month) + soft limits.
- **Per-company backups** + tenant export to a ZIP (GoBD data portability).
- **Owner vs admin vs accountant** role separation (read-only access for Steuerberater with scoped permissions).

## UX / UI
- **Global command palette** (`⌘K`) for “new invoice”, “search customer”, jump to settings.
- **Kanban / status board** for invoices (Draft → Sent → Paid → Overdue) and offers.
- **Inline customer create** when composing an invoice (no context switch).
- **Email template editor** per company (currently likely hardcoded Blade).
- **Mobile-optimized pages** for `invoices/index`, `show`, and payment recording.
- **Dashboard widgets**: cash flow (30/60/90), DSO, top customers, overdue chart, offer win rate.
- **Toast system + undo** for destructive actions (invoice delete, convert, cancel).
- **Number format + template previews** live in settings (you have the layout editor — extend the same UX to number formats).

## Notifications
- **In-app notifications center** (bell icon) for: overdue invoices, offer accepted, payment received, reminder queued.
- **Webhook outbox** (Stripe-style) so customers can integrate ERP/CRM.
- **Daily/Weekly digest emails** summarising activity.

## Reporting / analytics
- **UStVA export** (Umsatzsteuer-Voranmeldung) prepared data.
- **Einnahmen-Überschuss-Rechnung (EÜR)** export.
- **Customer profitability** report (revenue − tracked costs).
- **Revenue forecast** based on recurring invoices + pipeline of offers.

## Recurring / automation
- **Recurring invoices / subscriptions** (monthly, quarterly, yearly) with auto-send + optional SEPA pull via Stripe.
- **Contract repository** with renewal reminders.
- **“Rules” engine** (if invoice overdue X days → create task → notify admin).

## Performance & scaling
- **Queued PDF & email**: currently `sendReminder`/`send` run synchronously. Move DomPDF + mail to a job so HTTP is fast and failures retry.
- **PDF caching**: cache final PDF bytes keyed on `invoice_id + updated_at` so repeated downloads don’t re-render.
- **Database indexes** review: `invoices(company_id, status, due_date)`, `invoice_items(invoice_id)`, `documents(company_id, category)`.
- **N+1 audit** on `invoices/index`, `customers/show`, `dashboard` — add eager loading + `withCount`.
- **Move DomPDF → Browsershot (headless Chrome)** for better CSS/flex/fonts, then run it in a dedicated pool.

## Security & hardening
- **2FA / TOTP** for admin + optional WebAuthn.
- **Session device list + revoke**.
- **Rate limiting** on login, password-reset, and `send` endpoints (throttling already applied to expensive routes — extend).
- **Content Security Policy** nonce-based; `SecurityHeaders` middleware already in place.
- **Audit all `config()` reads inside controllers**; push to the Settings service where possible.
- **Full server-side idempotency keys** for `store`/`send` to prevent accidental duplicates (e.g. double-click).

## Internationalisation
- **Full DE/EN i18n** of UI + invoice templates (customer-level locale).
- **Currency per invoice** with ECB daily rate snapshot stored at creation.

## Code quality / DevEx
- **Consolidate tenancy resolution**: `Controller`, `ContextService`, `HandleInertiaRequests` all duplicate the logic — move to a single `TenantResolver`.
- **Replace `abort(403, ...)` “wrong company” patterns** with a single `EnsureBelongsToCompany` service or model scope guard; keeps controllers smaller.
- **Fix the 200+ TS errors** (types drift — many pages miss `[key: string]: unknown` index signatures; add helpers like `withPage<T extends PageProps>()`).
- **Feature tests for PDF regimes** (`standard`, `small_business`, both `13b` flavours, `intra_community`, `export`) to lock in tax UI behaviour.
- **GitHub Actions CI**: pint + pest + tsc + eslint on PR; preview deploys per branch.

---

## Top 5 “biggest ROI” picks
1. **Queued PDF + online payment link (Stripe/Mollie)** — immediately improves cash flow & perceived speed.
2. **Recurring invoices / subscriptions** — unlocks SaaS-like revenue for your users.
3. **XRechnung KoSIT validation + PDF/A-3 ZUGFeRD** — turns the feature from “exists” to “production-ready B2G”.
4. **Bank reconciliation (CSV first, FinTS later)** — closes the loop between “Rechnung gestellt” and “Zahlung erhalten”.
5. **UStVA / EÜR exports** — massive value for German small businesses and accountants (Steuerberater love it).

---

## Active implementation queue

Filtered from the Top 5 above. Online payment and bank reconciliation are parked (no PSP account / no bank API access yet).

1. **Recurring invoices / subscriptions** — internal scheduler, recurring template → auto-generated invoice, optional auto-send. No external dependency.
2. **XRechnung validation + PDF/A-3 ZUGFeRD** — add `horstoeko/zugferd-validator` (or KoSIT CLI) + switch DomPDF output to PDF/A-3b so the embedded XML is archive-legal. Block `sent` if validation fails.
3. **UStVA / EÜR exports** — compute per-period tax buckets from invoices + payments, export as PDF / CSV / ELSTER-ready XML for the Steuerberater.

### Parked (revisit later)
- **Online payment (Mollie / Stripe)** — waiting on PSP account + business decision.
- **Bank reconciliation (CSV + FinTS/PSD2)** — not needed right now.
