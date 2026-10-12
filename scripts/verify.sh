#!/usr/bin/env bash
# Sura Academy quality gates.
# Usage: bash scripts/verify.sh [--changed|--all] [--only api|contract|dart|guards|traceability]
#   --changed (default): gates for areas touched since HEAD (staged, unstaged, untracked)
#   --all: every gate, with coverage thresholds where a coverage driver exists
#   --only <area>: run just that area's gates (CI runs one area per job)
set -uo pipefail
MODE="--changed"
ONLY=""
while [ $# -gt 0 ]; do
  case "$1" in
    --changed|--all) MODE="$1" ;;
    --only) ONLY="${2:-}"; shift ;;
    *) echo "unknown argument: $1" >&2; exit 2 ;;
  esac
  shift
done
case "$ONLY" in ""|api|contract|dart|guards|traceability) ;; *) echo "unknown area: $ONLY" >&2; exit 2 ;; esac
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

RESULTS=()
FAILED=0

gate() { # gate "<name>" <command...>
  local name="$1"; shift
  echo ""
  echo "▶ [$name] $*"
  if "$@"; then RESULTS+=("✅ $name"); else RESULTS+=("❌ $name"); FAILED=1; fi
}
in_dir() { local d="$1"; shift; ( cd "$d" && "$@" ); }
changed_files() {
  { git diff --name-only HEAD 2>/dev/null
    git diff --name-only --cached 2>/dev/null
    git ls-files --others --exclude-standard 2>/dev/null; } | sort -u
}
touches() { [ "$MODE" = "--all" ] && return 0; changed_files | grep -qE "$1"; }
area() { [ -z "$ONLY" ] || [ "$ONLY" = "$1" ]; }
have() { command -v "$1" >/dev/null 2>&1; }

# ---------- Laravel API ----------
if area api && [ -f apps/api/artisan ] && touches '^(apps/api/|contracts/)'; then
  gate "api:pint" in_dir apps/api vendor/bin/pint --test
  gate "api:phpstan" in_dir apps/api vendor/bin/phpstan analyse --no-progress --memory-limit=1G
  if [ "$MODE" = "--all" ] && php -m 2>/dev/null | grep -qiE '^(pcov|xdebug)$'; then
    gate "api:tests+coverage(80)" in_dir apps/api php artisan test --parallel --coverage --min=80
  else
    gate "api:tests" in_dir apps/api php artisan test --parallel
  fi
fi

# ---------- OpenAPI contract ----------
if area contract && [ -f contracts/openapi/sura-api.v1.yaml ] && touches '^contracts/'; then
  if have npx; then
    gate "contract:lint" npx --yes @redocly/cli@latest lint contracts/openapi/sura-api.v1.yaml
  else
    RESULTS+=("❌ contract:lint (npx not found)"); FAILED=1
  fi
fi

# ---------- Dart / Flutter ----------
if area dart && [ -f pubspec.yaml ] && touches '^(apps/(family|teacher|kids)/|packages/|pubspec)'; then
  gate "dart:format" dart format --output=none --set-exit-if-changed apps packages
  gate "dart:analyze" dart analyze --fatal-infos .
  for d in packages/* apps/family apps/teacher apps/kids; do
    [ -d "$d/test" ] || continue
    if [ "$MODE" = "--all" ] || changed_files | grep -q "^$d/"; then
      gate "flutter:test:$d" in_dir "$d" flutter test
    fi
  done
  if [ "$MODE" = "--all" ]; then
    # Fresh release build (ADR-010), then a runtime check in headless Chromium on the wasm and
    # JS paths: no request may leave our origin (D-34). Built here so the check never skips or
    # runs on a stale build. One build at a time (server-environment.md §3).
    [ -d scripts/web/node_modules ] || npm ci --prefix scripts/web --no-audit --no-fund >/dev/null
    for app in apps/family apps/teacher; do
      [ -d "$app" ] || continue
      gate "web:build:$app" in_dir "$app" flutter build web --wasm --no-web-resources-cdn --release
      gate "web:no-third-party-requests:$app" node scripts/web/check-no-third-party.mjs "$app/build/web"
    done
  fi
fi

# ---------- Claude Code guard hooks (WP-0.2) ----------
if area guards && touches '^(\.claude/|scripts/test-guards\.php)'; then
  gate "guards" php scripts/test-guards.php
fi

# ---------- Traceability (always, unless another area is selected) ----------
if ! area traceability; then
  :
elif have php; then
  # --all (and CI) runs the full check: WPs in Review/Done need a test for every assigned ID (WP-0.3).
  if [ "$MODE" = "--all" ]; then
    gate "traceability" php scripts/check-traceability.php
  else
    gate "traceability" php scripts/check-traceability.php --quick
  fi
  if touches '^scripts/(check|test)-traceability\.php'; then
    gate "traceability:selftest" php scripts/test-traceability.php
  fi
  # The owner's roadmap page is built from these files; keep them buildable.
  if touches '^(docs/03-plan/|scripts/build-roadmap\.php)'; then
    gate "roadmap" php scripts/build-roadmap.php --check
  fi
else
  RESULTS+=("❌ traceability (php not found)"); FAILED=1
fi

echo ""
echo "──────── verify summary ($MODE) ────────"
printf '%s\n' "${RESULTS[@]}"
exit $FAILED
