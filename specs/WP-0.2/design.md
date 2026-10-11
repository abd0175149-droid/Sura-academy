# Design — WP-0.2 Claude Code environment and guardrails

## 1. Scope
- In scope (IDs): none (environment WP). Deliverables 1–5 and the two acceptance criteria of `phase-0-foundation.md#WP-0.2`.
- Explicitly out of scope: any product code; editing `.claude/settings.json` or `.claude/hooks/*` (human-only, hook-enforced). Fixes to those files are written as exact proposals for the owner.
- Decisions applied: D-33 (development in WSL; the dev-server controls in `server-environment.md` §4 apply only on `mafia-prod`). No other D-xx in the WP row.
- Session note: the owner is remote and authorized running WP-0.2 in the same session as WP-0.1 without `/clear` (owner message 2026-10-11). Recorded in notes.md.

## 2. Traceability
No requirement IDs. Each deliverable maps to evidence:

| Deliverable | How it is proven (planned) | Evidence location |
| --- | --- | --- |
| 1. Plugins installed | `claude plugin list` | notes.md §Inventory |
| 1. MCP servers context7, laravel-boost, playwright, dart | `claude mcp list` + a headless session's `system/init` event (server status as a fresh session sees it) + laravel-boost JSON-RPC handshake | notes.md §Inventory |
| 2. `CLAUDE.md` loaded, project skills, six agents | headless `claude -p --output-format stream-json --verbose` init event (agents, skills, slash commands, MCP status) + file inventory of `.claude/skills` (12) and `.claude/agents` (6). Owner may add `/context`, `/skills`, `/agents` output from the IDE (optional) | notes.md §Inventory |
| 3. Guard attempts (5 required) | Deliberate tool calls in this session, each with the exact tool result (see §5) | notes.md §Guard attempts |
| 3. Repeatability | `scripts/test-guards.php`: feeds recorded tool-call JSON to the hooks and asserts exit code and message (no hook changes). Gate `guards` in `verify.sh` | `php scripts/test-guards.php` output |
| 4. `/doctor` audit | `claude doctor` (CLI) + manual audit of settings/hooks against the constitution; owner runs `/doctor` in the IDE (optional) | `specs/WP-0.2/proposals.md` |
| 5. Probe files deleted | `git status` clean of probe files; none committed | notes.md |
| AC: `/sura-status` shows WP-0.3 | Focus block updated at close; owner types `/sura-status` (works remotely, like `/sura-wp`) | notes.md |

## 3. Data
None.

## 4. API contract changes
None.

## 5. Guard attempts (deliberate, safe by construction)
| # | Attempt | Tool | Expected | Safety |
| --- | --- | --- | --- | --- |
| G1 | Write `docs/01-product/spec/zz-guard-probe.md` | Write | PreToolUse blocks (spec read-only) | Blocked before write; if written, deleted at once and reported as BLOCKER |
| G2 | `apps/family/lib/guard_probe.dart` containing `EdgeInsets.only(left: 8)` | Write | PostToolUse reports the RTL rule (file is written) | Probe deleted in T3 |
| G3 | `git push --force --dry-run origin HEAD:refs/heads/wp-guard-probe` | Bash | Blocked (settings `deny` + hook) | `--dry-run` to a throwaway ref: harmless even if both guards failed |
| G4 | Write `specs/WP-0.2/guard-probe.txt` with AWS's public documentation example access key (`AKIA` + 16 chars) | Write | PreToolUse blocks (secret pattern) | Public example key, not a real secret |
| G5 | Write `specs/WP-0.2/guard-probe.sql` with an `UPDATE` on `credit_ledger_entries` | Write | PreToolUse blocks (append-only table) | No database involved |
| G6 (extra) | Same target as G1 through Bash (shell redirect) | Bash | START_HERE §6 says blocked. Static reading of `pre_tool_guard.php`: **not** blocked (Bash branch has no path rules) | Creates only a new empty probe file; deleted immediately; reported as finding with a proposed hook fix |

Already observed and reused as evidence: WP-0.1 `artisan migrate --force` blocked; a heredoc that merely contained `cp .env.example .env` blocked as "reading .env" (false positive, §8); WP-0.2 design draft blocked for quoting the AWS example key (secret pattern works on any Write).

## 6. UI
None.

## 7. Security & privacy
No secrets used: G4 uses AWS's public documentation key. `CONTEXT7_API_KEY` stays the owner's task (secrets never in chat or repo). No change to permissions or hooks by Claude.

## 8. Risks & open questions
1. **Interactive-only checks.** `/mcp`, `/context`, `/skills`, `/agents`, `/doctor`, `/sura-status` are slash commands Claude cannot run. Plan: equivalent evidence from the CLI and a headless session; the owner types `/sura-status` at the end (works remotely, as `/sura-wp` did). The others are optional.
2. **Project MCP servers "pending approval" outside this session.** `claude mcp list` reports context7, laravel-boost and playwright as pending, so a new session may start without them. Proposal for the owner (not applied by Claude): `"enabledMcpjsonServers": ["context7", "laravel-boost", "playwright"]` in `.claude/settings.json`, or approve once in a fresh session.
3. **Guard gaps to confirm, with proposed fixes** (proposals.md, owner applies):
   - Bash can write protected paths (G6): redirects, `sed -i`, `cp`, `tee` into `docs/01-product/spec|source`, `constitution.md`, `.claude/settings.json|hooks`, generated code.
   - The `.env` read rule is narrow (`cat|less|head|tail…`): `grep`, `sed -n`, `awk`, `python` are not covered; and it false-positives on heredocs that only mention `cp .env.example .env`.
   - START_HERE.md §3–§4 still describe the server workflow (D-33 notes this). Not human-only, so fixed in T5.
4. `scripts/test-guards.php` (T4) is new tooling not named in the WP text; it makes the guard proof repeatable after future hook edits. Drop it if you prefer evidence in notes only.
