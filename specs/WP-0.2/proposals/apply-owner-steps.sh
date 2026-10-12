#!/usr/bin/env bash
# Owner steps after WP-0.2 — run by the project owner, never by Claude (.claude/ is human-only).
#
# From Windows PowerShell:
#   wsl -d Ubuntu-24.04 -- bash -l ~/sura-academy/specs/WP-0.2/proposals/apply-owner-steps.sh
#
# What it does, in order (it asks before the optional steps):
#   1. Applies the guard fixes P-1..P-5 on a new branch and checks them (must print 94/94).
#   2. Marks this folder as trusted for Claude Code (O-1), with a backup of ~/.claude.json.
#   3. Saves your Context7 API key in ~/.bashrc if you paste one (O-2). The key is never shown.
#   4. Optionally approves decisions D-32, D-33, D-34 in docs/00-governance/decisions.md.
#   5. Commits, pushes the branch and opens a pull request. Claude then watches CI and asks you before merging.
set -euo pipefail

REPO="$HOME/sura-academy"
BRANCH="chore/claude-guards-p1-p5"
PROPOSALS="specs/WP-0.2/proposals"
export PATH="$HOME/development/flutter/bin:$HOME/.local/bin:$PATH"

say() { printf '\n\033[1;36m== %s\033[0m\n' "$1"; }
ok() { printf '\033[32m✔ %s\033[0m\n' "$1"; }
stop() { printf '\n\033[1;31m✘ %s\033[0m\n' "$1"; exit 1; }
ask() { local a; read -r -p "$1 [y/N] " a; [[ "$a" =~ ^[yY] ]]; }

cd "$REPO"

say "0/5 Checks"
for tool in git php dart gh python3; do
  command -v "$tool" >/dev/null || stop "missing tool: $tool"
done
[ -z "$(git status --porcelain --untracked-files=no)" ] || stop "there are uncommitted changes in $REPO; tell Claude before running this"
git rev-parse --verify --quiet "$BRANCH" >/dev/null && stop "branch $BRANCH already exists; this script was already run. Tell Claude."
git switch -q main
git pull -q --ff-only
ok "tools present, main up to date"

say "1/5 Guard fixes P-1..P-5"
git switch -q -c "$BRANCH"
for patch in settings-P1-P5 hooks-P2-P3-P4 suite-after-P2-P3-P4; do
  git apply "$PROPOSALS/$patch.diff"
  ok "applied $patch"
done
RESULT="$(php scripts/test-guards.php)"
echo "$RESULT" | head -1
echo "$RESULT" | grep -q "94/94 as expected" || stop "guard tests are not 94/94. Nothing was committed; send this output to Claude."
ok "guards verified"

say "2/5 Trust this folder for Claude Code (O-1)"
python3 - "$REPO" <<'PY'
import json, os, shutil, sys, tempfile
repo = sys.argv[1]
path = os.path.expanduser("~/.claude.json")
if not os.path.exists(path):
    print("~/.claude.json not found; skipped (accept the trust prompt next time you run claude)")
    sys.exit(0)
data = json.load(open(path))
project = data.setdefault("projects", {}).setdefault(repo, {})
if project.get("hasTrustDialogAccepted") is True:
    print("already trusted")
    sys.exit(0)
shutil.copy2(path, path + ".bak-sura")
project["hasTrustDialogAccepted"] = True
fd, tmp = tempfile.mkstemp(dir=os.path.dirname(path))
with os.fdopen(fd, "w") as f:
    json.dump(data, f, indent=2)
os.chmod(tmp, os.stat(path + ".bak-sura").st_mode)
os.replace(tmp, path)
print("trusted (backup saved as ~/.claude.json.bak-sura)")
PY

say "3/5 Context7 API key (O-2, optional)"
if grep -q '^export CONTEXT7_API_KEY=' "$HOME/.bashrc"; then
  ok "already set in ~/.bashrc"
else
  read -r -s -p "Paste your Context7 key and press Enter (or just press Enter to skip): " KEY
  echo
  if [ -n "$KEY" ]; then
    printf '\n# Context7 MCP (Sura Academy)\nexport CONTEXT7_API_KEY=%q\n' "$KEY" >> "$HOME/.bashrc"
    unset KEY
    ok "saved in ~/.bashrc (reload the VS Code window afterwards)"
  else
    echo "skipped"
  fi
fi

say "4/5 Decisions D-32, D-33, D-34 (optional)"
echo "  D-32: the mafia-prod server is for development and preview only, no real data."
echo "  D-33: development happens in WSL on Windows; code reaches the server through GitHub."
echo "  D-34: Flutter web builds are checked by a real browser run, not by a text search."
APPROVED_DECISIONS=0
if ask "Approve all three?"; then
  python3 - <<'PY'
path = "docs/00-governance/decisions.md"
s = open(path, encoding="utf-8").read()
today = "2026-10-12"
edits = [
    ("| Open (رد صاحب المشروع 2026-10-09: تطوير فقط) |", f"| Approved ({today}) |"),
    ("| Open (رد صاحب المشروع 2026-10-09: Windows ثم GitHub ثم الخادم) |", f"| Approved ({today}) |"),
    ("| Open (رد صاحب المشروع 2026-10-11: فحص تشغيل فعلي) |", f"| Approved ({today}) |"),
    ("### D-32 دور خادم التطوير الحالي — Open", "### D-32 دور خادم التطوير الحالي — Approved"),
    ("### D-33 مكان التطوير ونقل الكود إلى الخادم — Open", "### D-33 مكان التطوير ونقل الكود إلى الخادم — Approved"),
    ("### D-34 فحص «لا موارد من طرف ثالث» لبناء Flutter على الويب — Open", "### D-34 فحص «لا موارد من طرف ثالث» لبناء Flutter على الويب — Approved"),
]
for old, new in edits:
    if s.count(old) != 1:
        raise SystemExit(f"decisions.md changed since the script was written; not editing it. Missing: {old}")
    s = s.replace(old, new)
open(path, "w", encoding="utf-8").write(s)
print("D-32, D-33, D-34 marked Approved")
PY
  APPROVED_DECISIONS=1
else
  echo "skipped (they stay Open)"
fi

say "5/5 Commit, push and open a pull request"
git add .claude/settings.json .claude/hooks/pre_tool_guard.php .claude/hooks/post_tool_check.php \
  scripts/test-guards.php "$PROPOSALS/apply-owner-steps.sh"
TITLE="chore(claude): apply guard fixes P-1..P-5 [WP-0.2]"
BODY="Owner-applied fixes from specs/WP-0.2/proposals.md: P-1 MCP servers approved for the project, P-2 shell writes to human-only paths blocked, P-3 query-builder writes to append-only tables reported, P-4 .env reads blocked and cat/tee heredoc false positives removed, P-5 guard suite allowed. Guard suite: 94/94."
if [ "$APPROVED_DECISIONS" = 1 ]; then
  git add docs/00-governance/decisions.md
  TITLE="chore(claude): apply guard fixes P-1..P-5; approve D-32..D-34 [WP-0.2]"
  BODY="$BODY Decisions D-32, D-33, D-34 approved by the owner."
fi
git commit -q -m "$TITLE" -m "$BODY" -m "Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>"
ok "committed"
git push -q -u origin "$BRANCH"
ok "pushed"
gh pr create --base main --head "$BRANCH" --title "$TITLE" \
  --body "$BODY"$'\n\n'"🤖 Generated with [Claude Code](https://claude.com/claude-code)"

say "Done"
echo "Go back to Claude and write: تم"
