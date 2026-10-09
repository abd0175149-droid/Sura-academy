---
name: scheduling-time
description: Time-zone and scheduling rules for Sura Academy — class patterns in local time plus IANA zone, session generation, DST handling and alerts, conflicts, holidays, maintenance window, join windows and the 13-zone test matrix. Use for any code or test that creates, moves, displays or compares session times, availability, deadlines or reminders.
paths:
  - "apps/api/app/Modules/Scheduling/**"
  - "apps/api/app/Modules/Live/**"
  - "packages/sura_core/lib/src/time/**"
---

# Scheduling & time

Sources: `spec/08-scheduling-and-timezones.md`, ADR-008, D-12, D-24, BR-013.

## Model
- Instants: `timestamptz` UTC in DB and API. Display converts to the viewer's zone.
- Class pattern: weekdays + local `HH:MM` + `ref_timezone` (IANA). The class's local time is fixed; the teacher's Amman time moves when the reference zone changes DST.
- `Asia/Amman` = UTC+3 all year (read from tzdata, never hardcoded).
- Durations by age group from settings (`session.duration_by_age`: 5–7 → 60, 8–10 → 75, 11–14 → 90).

## Generation (SCH-05)
Daily job, `scheduling.generate_weeks_ahead` (8) weeks: for each class rule and local date → combine local time in `ref_timezone` → UTC. Idempotent by `(class_id, local_date, local_start)`.
- Non-existent local time (spring forward) or ambiguous (fall back) → create the session with `needs_review = true`; never pick silently; alert ops.

## DST alert (SCH-06)
Daily: find sessions within `scheduling.dst_alert_days` (21) whose time changes for Amman or for any enrolled family's zone; notify teacher and families ≥ 14 days before (spec notification table).

## Guards
- Capacity `class.capacity_min/max` (2/4); exceeding needs supervisor permission + reason (SCH-01).
- Conflicts (SCH-08): teacher double-booked, student double-booked, more than one family member on one device at the same time.
- Maintenance window: Saturday 10:00–12:00 UTC — reject overlap unless supervisor overrides with reason (D-24).
- Family zone ≠ class `ref_timezone` → explicit confirmation required (SCH-07).
- Holidays (D-12): `tentative` → notice only; `confirmed` → bulk cancel + compensation + notifications; alert ops if still tentative 48h before the first affected session.
- Join window: opens `session.join_window_before_min` (10) before start, closes `session.room_close_after_end_min` (10) after end.

## Test matrix (mandatory)
Zones: America/New_York, America/Chicago, America/Los_Angeles, America/Toronto, America/Vancouver, Europe/London, Europe/Dublin, Europe/Berlin, Europe/Paris, Australia/Sydney, Australia/Melbourne, Australia/Brisbane, Asia/Amman.

DST transition dates to cover:
| Region | 2027 start | 2027 end | 2028 start | 2028 end |
| --- | --- | --- | --- | --- |
| US & Canada | 2027-03-14 | 2027-11-07 | 2028-03-12 | 2028-11-05 |
| EU, UK, Ireland | 2027-03-28 | 2027-10-31 | 2028-03-26 | 2028-10-29 |
| Sydney/Melbourne (southern: ends in April, starts in October) | 2027-10-03 | 2027-04-04 | 2028-10-01 | 2028-04-02 |
| Brisbane, Amman | no DST | — | — | — |

For each: an evening class (e.g., 17:30 local) and a morning class (e.g., 09:00 local); assert UTC instants, Amman wall time for the teacher, the alert job output, and `needs_review` for impossible/ambiguous times (02:30 on a spring-forward day).

## Deadlines in UTC
Absence cutoff, teacher confirmation, report deadlines and renewals compare UTC instants (ATT-02). Never compare local wall-clock strings.

## Dart side
Use the `timezone` package with bundled tzdata for display. Never compute business windows in Flutter; the API returns `join_opens_at`, `join_closes_at`, `can_join`.
