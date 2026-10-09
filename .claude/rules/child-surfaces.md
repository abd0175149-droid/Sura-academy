---
paths:
  - "packages/sura_kids/**"
  - "apps/kids/**"
  - "apps/family/lib/features/child/**"
---

# Child surfaces — hard rules

- No analytics, ads, attribution or tracking SDKs. No network calls to any host except our API and the video provider during a live session.
- Every external link, permission request, purchase or settings change goes through `ParentalGate`.
- No free-text messaging to adults, no private chat, no contact data shown (teacher or other children).
- Show children only first names (other students) and nothing else personal.
- Large targets (≥ 56), audio instruction buttons for under-8 screens, minimal text, no timers that pressure, no streaks, no leaderboards, no loot-box mechanics.
- Child token scope is `child:<student_id>`; never call parent, billing or messaging endpoints.
- Any change here requires the `privacy-reviewer` agent before closing the WP.
