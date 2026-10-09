---
name: billing-payments
description: Pricing, family discount, coupons, subscription lifecycle, renewals, dunning, refunds, wallet, invoices and PayTabs/webhook rules for Sura Academy. Use for any work on prices, checkout, subscriptions, payments, refunds, invoices, wallet, or payment webhooks — including UI that displays money or subscription state.
paths:
  - "apps/api/app/Modules/Billing/**"
  - "apps/family/lib/features/billing/**"
  - "apps/family/lib/features/checkout/**"
---

# Billing & payments

Sources: `spec/10-billing-and-payments.md`, `state-machines.md` (Subscription), ADR-006, D-01, D-07, D-17, D-27, D-28, D-30, settings `billing.*`, `subscription.*`, `refund.*`.

## Pricing
- Prices come from the price book (validity dates); a price change affects new subscriptions only (BIL-02).
- Family discount (D-01): first child (oldest active subscription; tie → higher price) at full price; each additional child −`billing.family_discount_pct` (12.5% = 1250 bps) on the family basket, even across classes (BIL-03).
- Coupons (BIL-04): percent or fixed, usage limit, expiry, first-month-only flag, referral codes. Order: list price → family discount → coupon → wallet. Never below zero.
- Market gating (D-17): purchase allowed only for enabled countries (flag). EU blocked until BIL-18.

## Subscription states
draft → pending_payment → active ↔ paused; active → completed | grace → suspended; active/paused/suspended → cancelled → refunded. Transitions, guards and effects: `state-machines.md`. Every transition writes `subscription_events` (append-only) and an outbox event.

## Renewal & dunning
- Auto-renew charge when paid balance reaches `subscription.renew_trigger_balance` (2); reminder per D-07 (≥ 72h before charging).
- Failed renewal → grace (`billing.grace_period_days` = 5, sessions continue) → retries on days in `billing.dunning_retry_days` (1,3,5) → suspended (no new session entry, BR-011) → late payment reactivates.

## Payments
- Truth = verified webhook, not browser return. Return page polls our API.
- Webhook handler: verify signature first → store raw → respond fast → process in queue → dedupe by provider event id and `provider_ref` → tolerate out-of-order events with state checks → amount/currency mismatch → `needs_review`.
- Every money-moving POST requires `Idempotency-Key`.
- Never store PAN/CVV; store token, brand, last4, expiry only. Use PayTabs sandbox and its published test cards only in sandbox; never real cards in tests.
- Bank transfer (BIL-09): proof upload → finance approval (reason, audit) → payment marked paid → activation.

## Refunds (spec refund rules)
| Case | Rule |
| --- | --- |
| New subscription, no session used | full refund |
| Partially used | no cash refund; remaining paid credits → wallet for 3 months (D-28, CR-004) |
| EU/UK customer within `refund.cooling_off_days` (14) | refund per explicit consent rules at checkout |
| Academy fault | refund or credit, family's choice |
Refund approval requires reason and permission (`payments.refund`); execution via gateway or wallet; invoice credit note.

## Wallet (BIL-13)
USD balance, append-only entries with source and expiry (`billing.wallet_freeze_months` = 3); used at checkout before card.

## Invoices (BIL-11)
Sequential numbering (gap-free per series, generated under lock), legal entity data, lines, discounts, tax fields when required, bilingual PDF via Gotenberg.

## Currency
Billing currency USD (`billing.currency`). Teacher compensation is JOD (fils). Never convert implicitly.
