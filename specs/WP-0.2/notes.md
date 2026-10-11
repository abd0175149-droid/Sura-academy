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

## Verification evidence
```
(paste verify --all summary)
```
| Acceptance criterion | Evidence |
| --- | --- |

## Review findings
| Agent | Severity | Finding | Resolution |
| --- | --- | --- | --- |

## خطوات العرض اليدوي
1. …
