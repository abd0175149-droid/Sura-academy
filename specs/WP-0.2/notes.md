# Notes — WP-0.2

## Decisions applied
- D-33: development in WSL; `server-environment.md` §4 controls apply only to sessions on `mafia-prod`.
- Owner authorization (2026-10-11, remote): run WP-0.2 in the same session as WP-0.1 without `/clear`; Claude runs all commands on the machine.
- Design approved 2026-10-11 via the Arabic approval page (https://claude.ai/artifact/QwRMUDbuPKC4gEu1pc9iE8): (1) full plan incl. `scripts/test-guards.php`; (2) MCP approval setting stays a proposal for the owner, not applied by Claude.

## Deviations / surprises
- Design (2026-10-11): the first draft of `design.md` quoted AWS's public example access key verbatim; the Write was blocked by `pre_tool_guard.php` ("the content looks like an AWS access key"). Unplanned live evidence for G4; the design now describes the key without spelling it out.

## Inventory (T1, 2026-10-11, Claude Code 2.1.295, WSL)
Sources: `claude plugin list`, `claude mcp list`, and two headless sessions started from the repo root (`claude -p … --output-format stream-json --verbose`; trimmed init event in `specs/WP-0.2/evidence/headless-init.json`).

| Check | Result |
| --- | --- |
| Plugin `dart-flutter@dart-flutter` | ✅ 1.0.6, user scope, enabled |
| Plugin `php-lsp@claude-plugins-official` | ✅ 1.0.0, user scope, enabled |
| MCP `dart` (`plugin:dart-flutter:dart-mcp-server`) | ✅ connected (CLI list and headless init) |
| MCP `laravel-boost` | ✅ connected in headless init (JSON-RPC `initialize` answered in WP-0.1). In this IDE session it failed at startup only because `apps/api` did not exist yet |
| MCP `context7` | ⚠ connected in headless init, but `claude mcp list` warns `Missing environment variables: CONTEXT7_API_KEY` → owner sets the key (runs keyless/rate-limited until then) |
| MCP `playwright` | ⚠ `pending` approval outside this IDE session (works here: used in WP-0.1) → proposal P-1 |
| `CLAUDE.md` loaded | ✅ headless session quoted line 1 verbatim: `# Sura Academy (أكاديمية سُرى) — Project Constitution for Claude` |
| Project skills | ✅ 12 in `.claude/skills`: workflow `sura-wp`, `sura-status`, `verify` listed as slash commands; the 9 domain skills are path-scoped (`paths:` frontmatter), so they appear only when matching files are touched (seen live: `arabic-rtl-ui` appeared when Dart files were edited in WP-0.1) |
| Project agents | ✅ 6: ledger-auditor, privacy-reviewer, rtl-reviewer, security-reviewer, spec-reader, traceability-checker (headless init + headless answer) |
| SessionStart hook | ✅ ran in the headless session and printed the focus block |
| Workspace trust (CLI) | ⚠ CLI prints `Ignoring 33 permissions.allow entries from .claude/settings.json: this workspace has not been trusted`; `~/.claude.json` has no `hasTrustDialogAccepted` for this path. Hooks and deny rules still apply; only the allow list is skipped (more prompts). → owner action O-1 |

## Guard attempts (T2, 2026-10-11, this IDE session)
Each attempt was a real tool call by Claude; the result column quotes the tool output (SQL keyword and table name are split by `·` here so these notes do not trip the content guard themselves).

| # | Attempt | Expected | Actual result | Verdict |
| --- | --- | --- | --- | --- |
| G1 | Write new `docs/01-product/spec/zz-guard-probe.md` | blocked | `BLOCKED by .claude/hooks/pre_tool_guard.php: docs/01-product/spec/zz-guard-probe.md — spec files are read-only. Propose a change request (docs/00-governance/change-control.md).` | ✅ |
| G1b | Edit line 1 of existing `docs/01-product/spec/00-priorities-and-baseline-decisions.md` | blocked | same message for that path | ✅ |
| G2 | Write `apps/family/lib/guard_probe.dart` with `EdgeInsets.only(left: 8)` | correction message | file written, then `PostToolUse … Project checks failed: apps/family/lib/guard_probe.dart:4: … -> use directional APIs (EdgeInsetsDirectional, AlignmentDirectional, PositionedDirectional, TextAlign.start/end, BorderRadiusDirectional). Suppress only with // rtl-ok: <reason>`; `.claude/rules/flutter.md` auto-loaded | ✅ |
| G3 | force-push with `--force --dry-run` to throwaway ref `wp-guard-probe` | blocked | `BLOCKED … — force-push is forbidden.` | ✅ |
| G3b | same with `-f` | blocked | `force-push is forbidden.` | ✅ |
| G3c | same with a `+HEAD:` refspec | blocked | `force-push is forbidden.` | ✅ |
| G3d | `--dry-run` push of `HEAD:main` | blocked | `BLOCKED … — never push to main; push the WP branch and open a PR.` | ✅ |
| G4 | Write `specs/WP-0.2/guard-probe.txt` with AWS's public example access key | blocked | `BLOCKED … the content looks like an AWS access key. Secrets live in the secrets manager or a local .env, never in tracked files.` (it also blocked the first design draft) | ✅ |
| G5 | Write `specs/WP-0.2/guard-probe.sql` with UPDATE·credit_ledger_entries `SET …` | blocked | `BLOCKED … 'UPDATE·credit_ledger_entries' targets an append-only table (constitution art. 5). Write a REVERSAL entry or a new row instead.` | ✅ |
| G5b (extra) | Write `apps/api/app/Modules/Ledger/Actions/GuardProbe.php` with `DB::table('credit_ledger_entries')->where(…)->update(…)` | finding expected | **written, no warning**: the pre-hook matches SQL text only, the post-hook matches Eloquent model calls only. The `credit-ledger` skill auto-loaded on that path | ⚠ gap → P-3 |
| G6 (extra) | Bash `printf '' > docs/01-product/spec/zz-guard-probe.md` | finding expected | **created, not blocked**; removed in the same command; `git status docs/01-product` clean | ⚠ gap → P-2 |

False positives seen live (→ P-4): WP-0.1, a heredoc writing `ci.yml` that merely contained the text of the env-template copy step was blocked as `reading .env is forbidden`; WP-0.2, a heredoc writing these notes was blocked as `force-push is forbidden` because the notes quoted the G3 commands. The Bash guard scans the whole command text, including heredoc bodies. Also reused: WP-0.1 `artisan migrate --force` blocked (`migrate --force is for CI/CD only`).

Probe cleanup (T3): `GuardProbe.php` and `guard_probe.dart` deleted; the G1/G4/G5 files never existed; `git status --short` empty; `git diff main -- docs/01-product apps` empty.

## Guard regression suite (T4)
- `php scripts/test-guards.php`: 83 cases (64 pre-hook, 19 post-hook) replaying Claude Code's hook JSON, offline (local bare git remotes, temp project root for post-hook files), ~3 s. Result: `guard tests: 83/83 as expected`, with 10 known-gap cases listed (P-2, P-3, P-4) that fail on purpose once a proposal changes the behaviour.
- Proof it catches regressions: a copy of `pre_tool_guard.php` with the spec rule broken (`GUARD_HOOKS_DIR=<copy>`) → `79/83`, failing exactly G1, G1b, source file and relative path. Real hooks untouched (`git status .claude` empty).
- Found while writing it: the post hook runs `dart format` before checking, so a `// parental-gate-ok` (or `rtl-ok`) marker must be on the offending line of the *formatted* code; the suite has a case for both shapes. `grep … apps/api/.env` is blocked only because the env-dump rule happens to match `.env` at the end of the command; with output redirected, or via python, reading `.env` is allowed (P-4).
- Wired as gate `guards` in `verify.sh` (runs under `--all`, or when `.claude/` or the suite changes) and as a CI step in the `traceability` job.

## Audit (T5)
- `claude doctor` (CLI, 2.1.295): "No installation issues found"; one invalid setting: `.mcp.json › mcpServers.context7: Missing environment variables: CONTEXT7_API_KEY` (→ O-2). The interactive `/doctor` is optional for the owner.
- Settings and hooks audited against the constitution → `specs/WP-0.2/proposals.md`: P-1…P-5 as ready-to-apply patches (each `git apply --check` clean) plus owner actions O-1 (trust the workspace in the CLI) and O-2 (Context7 key). The hook patch was validated on a patched copy: exactly the 8 known-gap cases flip, nothing else regresses; with the companion suite patch the result is 88/88.
- `START_HERE.md` §3–§4 rewritten for WSL (D-33); §6 now states that shell writes to protected paths are not blocked yet (P-2) and points to the guard suite.

## Verification evidence
`bash scripts/verify.sh --all` on 2026-10-11 (WSL):
```
──────── verify summary (--all) ────────
✅ api:pint
✅ api:phpstan
✅ api:tests+coverage(80)
✅ dart:format
✅ dart:analyze
✅ flutter:test:packages/sura_core
✅ flutter:test:apps/family
✅ flutter:test:apps/teacher
✅ web:build:apps/family
✅ web:no-third-party-requests:apps/family
✅ web:build:apps/teacher
✅ web:no-third-party-requests:apps/teacher
✅ guards
✅ traceability
```
| Acceptance criterion | Evidence |
| --- | --- |
| Every attempt in deliverable 3 documented with its expected result | §Guard attempts: G1–G5 all ✅ as expected (plus G1b, G3b–d), extras G5b and G6 documented as gaps with proposals P-3 and P-2; repeatable via `php scripts/test-guards.php` (88/88) |
| `/sura-status` works and shows next WP-0.3 | Focus block in `status.md` names WP-0.3. `/sura-status` is user-invocable only (`disable-model-invocation: true`), so the owner runs it; result recorded below when received |

Deliverables 1, 2, 4, 5: §Inventory, §Audit, `proposals.md`, §Guard attempts (cleanup line).

## Review findings
| Agent | Severity | Finding | Resolution |
| --- | --- | --- | --- |

## خطوات العرض اليدوي
كل الأوامر من جذر المستودع `~/sura-academy` داخل WSL، على الفرع `wp/WP-0.2-guardrails-proof`.

1. اكتب في محادثة Claude: `/sura-status`. يجب أن يعرض الحزمة التالية WP-0.3، وWP-0.2 بحالة Review. (يعمل من جوالك أيضاً.)
2. شغّل اختبار الضوابط: `php scripts/test-guards.php`. النتيجة `guard tests: 88/88 as expected` مع قائمة الثغرات المعروفة (P-2 وP-3 وP-4).
3. اطلب من Claude في جلسة أن يكتب ملفاً داخل `docs/01-product/spec/`. يجب أن يُرفض بالرسالة «spec files are read-only».
4. اطلب منه أن يكتب في ملف Dart `EdgeInsets.only(left: 8)`. يجب أن تظهر رسالة «use directional APIs». احذف الملف بعدها.
5. (اختياري) شغّل في جلسة Claude: `/mcp` و`/agents` و`/context` و`/doctor`، وقارنها بجدول «Inventory» أعلاه.
6. عند عودتك إلى الجهاز: نفّذ O-1 (شغّل `claude` مرة من جذر المستودع ووافق على الثقة) وO-2 (ضع `CONTEXT7_API_KEY` في ملف الطرفية)، ثم طبّق المقترحات بالخطوات في `specs/WP-0.2/proposals.md` §How to apply، وتحقق أن `php scripts/test-guards.php` يعطي 88/88.
