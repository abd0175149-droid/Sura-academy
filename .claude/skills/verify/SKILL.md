---
name: verify
description: Run the Sura Academy quality gates — formatting, static analysis, tests with coverage, OpenAPI contract lint and traceability — for changed files or the whole repository. Use before every commit, before ticking any task, and before closing a work package; never claim work is done without a green run.
allowed-tools: Bash(bash scripts/verify.sh *)
---

# Verify

- During a task: `bash scripts/verify.sh --changed`
- Before closing a WP or opening a PR: `bash scripts/verify.sh --all`

Read the summary block at the end and report it as a short list: ✅ gate or ❌ gate with the first relevant error lines.

Rules:
- Fix failures caused by your change before continuing.
- Never delete, skip or weaken tests, add skip annotations, lower coverage or analysis levels, or edit baselines to get green.
- A failure unrelated to the current WP: stop and report it to the user; do not fix it silently inside this WP.
- Golden diffs: accept (`flutter test --update-goldens`) only for intended visual changes, and list each one in the WP notes.
- If a gate cannot run (tool missing), report it as ❌ with the reason; it is not a pass.
