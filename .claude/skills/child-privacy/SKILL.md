---
name: child-privacy
description: Child privacy, consent, safeguarding and data-minimization rules for Sura Academy (COPPA as amended, UK Children's Code, spec PRV/SAFE requirements). Use for any work involving children's data, child-facing screens, recordings, live sessions, chat, consents, analytics, logging, notifications to families, deletion/export, or third-party services.
paths:
  - "apps/api/app/Modules/Families/**"
  - "apps/api/app/Modules/Live/**"
  - "packages/sura_kids/**"
  - "apps/family/lib/features/child/**"
---

# Child privacy & safeguarding

Sources: `spec/17-security-privacy-safeguarding.md`, ADR-009, ADR-012, D-11, D-13, D-14, D-15, D-16, D-23.

## Data minimization (PRV-04)
Child: first name, birth month+year, level info, learning notes (non-medical). No photo, email, phone, address, school, or free-form identity fields. Other children see first names only. Teachers see first names, levels, learning notes (TCH-03).

## Consent
- Verifiable parental consent before creating a child profile (`VpcMethod`, D-13). Separate per-child recording consent (D-11). Marketing separate. AI features (future) separate.
- Consent rows are immutable with document version; withdrawal = new row; effects are events.

## Child surfaces
- Child token scope `child:<student_id>`; PIN on an authorized device (SEC-04).
- No third-party SDKs, trackers, ads, analytics, fonts or CDNs. External links, permissions and purchases behind `ParentalGate`.
- No private channels with adults; class chat is public and logged (CLS-07, SAFE-03).
- Design: no streaks, no leaderboards, no pressure timers, no loot boxes.

## Recordings
Start only when every present student has recording consent (D-11). Copy to EU S3, delete from provider (D-16). Family access only to own child's class, signed link ≤ 10 min (SEC-10), access logged. Auto-delete 30 days (families) / 90 days (admin).

## Safeguarding
- "Report something wrong" (SAFE-01) creates an incident routed immediately to the safeguarding lead with priority notification; access restricted (SAFE-02).
- Supervisor joins are visible to participants and logged (SAFE-05).
- Teacher activation requires clearance, safeguarding training and code acceptance (SAFE-04); immediate suspension revokes access (SAFE-06).

## Logs, analytics, AI
- Log redaction for names, emails, phones, tokens, child data; Sentry `send_default_pii=false`.
- Analytics: allowlisted events only, opaque ids, never from child context (D-23).
- No child data in any AI prompt (ADR-012).

## Retention & deletion
Spec retention table + D-15 additions; each data type registered in `config/privacy.php`. Deletion (D-14): erase/pseudonymize PII, keep financial/ledger rows with opaque ids, hard-delete files and recordings. Data requests answered within 30 days (PRV-07).

## Review checklist (run privacy-reviewer)
New field on a child? New third party? New log/analytics/notification content? New child screen? Each needs a spec ID, a retention rule and a consent basis.
