# Proposals for human-only files — WP-0.2

`.claude/settings.json` and `.claude/hooks/*` are human-only (hook-enforced), so Claude prepared these as patches for the owner. Nothing here has been applied. Each patch was checked with `git apply --check` against this branch; the hook patch was validated by running the guard suite against a patched **copy** of the hooks (`GUARD_HOOKS_DIR`).

## Summary

| ID | File | Problem (evidence) | Change | Severity |
| --- | --- | --- | --- | --- |
| P-1 | `.claude/settings.json` | `claude mcp list` shows context7, laravel-boost, playwright as "pending approval"; a headless session started with playwright `pending` (notes §Inventory) | `"enabledMcpjsonServers": ["context7", "laravel-boost", "playwright"]` | Medium: a new session can start without the MCP servers |
| P-2 | `.claude/hooks/pre_tool_guard.php` | Shell commands can write human-only paths: G6 created a file in `docs/01-product/spec/` with a redirect; `sed -i` on the constitution and `cp` into `.claude/hooks/` are also allowed (suite cases) | Block a Bash command that names a human-only path **and** contains a write operation (`>`/`>>` except to `/dev/null` and not as part of `->`/`=>`, `tee`, `sed -i`, `perl -i`, `cp`/`mv`/`rm`/`ln`/`touch`/`truncate`/`dd`/`install`/`chmod`/`unlink`/`rsync`, `open(path, 'w'/'a'/'x')`). Reads stay allowed (`grep`, `sed -n`, `cat … | head`, `git status`, `open(path)` for reading, commit messages with `->`) | High: the constitution's human-only rule was enforceable only for Write/Edit |
| P-3 | `.claude/hooks/post_tool_check.php` | G5b: `DB::table('credit_ledger_entries')->…->update(…)` in the Ledger module raised no warning; the existing check only knows Eloquent model calls and works line by line, while Pint splits chains over lines | After every write under `apps/api/app` or `apps/api/database`, scan the whole file for `DB::table('<append-only table>')…->update/delete/truncate/upsert/updateOrInsert/increment/decrement(` across lines and report the line | Medium: defence in depth; the real guarantee is the DB-level append-only enforcement planned in WP-1.1 (Art. 5) |
| P-4 | `.claude/hooks/pre_tool_guard.php` | (a) `.env` reading is only caught for `cat/less/more/head/tail/type/bat`; `grep … .env > x` and `python … open('.env')` read it freely, and plain `grep … .env` is blocked only by accident (the env-dump rule matches `.env` at the end of a command). (b) False positives: text inside heredoc bodies is scanned as commands — WP-0.1 `cat > ci.yml <<EOF … cp .env.example .env …` and WP-0.2 notes quoting push commands were blocked | (a) Block any Bash command naming a real `.env` file (read or write), except the setup idiom `cp [-n] .env.example .env`; tighten the env-dump rule to a standalone `env` command. (b) Skip a heredoc body for the push/deny/.env rules only when it is pure data: written by `cat`/`tee`, the opening line ends with at most a `> file` redirect (no pipe, `;` or `&&`), and nothing follows the heredoc in the command. So `bash <<EOF`, `cat <<EOF \| bash`, `tee f <<EOF \| sh` and "write a script, then run it" all stay fully scanned (pinned by suite cases after review M1). SQL rules keep scanning everything | (a) Medium: secrets in `.env` (Art. 8 / CLAUDE.md "Never read .env"). (b) Low: friction only, with a Write-tool workaround |
| P-5 | `.claude/settings.json` | The new suite asks for permission each run | allow `Bash(php scripts/test-guards.php)` | Low |

Accepted as is (no change proposed): the SQL append-only check on Bash scans the whole command, so `echo "<verb> <append-only table> …"` is blocked even as plain text (seen live in WP-0.2). Safer to keep; the suite records it as a known false positive.

## Owner actions that are not file changes

| ID | Action | Why |
| --- | --- | --- |
| O-1 | In WSL, run `claude` once from `~/sura-academy` and accept the trust dialog (VS Code may already have done this for its own sessions) | The CLI warns `Ignoring 33 permissions.allow entries … this workspace has not been trusted`; `~/.claude.json` has no trust flag for this path. Without it, headless/CLI sessions ignore the allow list (guards still apply) |
| O-2 | Put `CONTEXT7_API_KEY` in your shell profile in WSL (never in the repo) | `claude doctor` and `claude mcp list` report it missing |

## How to apply (when you are at the machine)

From the repo root, on a branch (main is protected):

```bash
git switch -c chore/claude-guards-p1-p5
git apply specs/WP-0.2/proposals/settings-P1-P5.diff
git apply specs/WP-0.2/proposals/hooks-P2-P3-P4.diff
git apply specs/WP-0.2/proposals/suite-after-P2-P3-P4.diff   # flips the 8 known-gap cases in the same commit
php scripts/test-guards.php                                   # expect: guard tests: 94/94 as expected
git commit -am "chore(claude): close guard gaps P-1..P-5 [WP-0.2]"
git push -u origin chore/claude-guards-p1-p5 && gh pr create --fill
```

The suite patch must go in the same commit as the hook patch: with the new hooks and the old suite, 8 cases fail on purpose (that is how the suite flags changed behaviour).

## Validation evidence

| Hooks | Suite | Result |
| --- | --- | --- |
| current | current (committed) | `94/94 as expected`, 10 known-gap notices |
| patched copy | current | `86/94`: exactly the 8 gap cases change (3× P-2, 4× P-4, 1× P-3); no other case regresses, including the new allow cases for reading the spec and creating `.env` from the template, the `->` commit message and the four heredoc-executes-its-body cases (still blocked) |
| patched copy | after `suite-after-P2-P3-P4.diff` | `94/94 as expected`, 2 known false positives left (accepted above) |
| current | after `suite-after-P2-P3-P4.diff` | `86/94`: the 8 flipped cases fail, so the suite really depends on the patch |

Patch files: `specs/WP-0.2/proposals/settings-P1-P5.diff`, `hooks-P2-P3-P4.diff`, `suite-after-P2-P3-P4.diff`.
