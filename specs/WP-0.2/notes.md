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
