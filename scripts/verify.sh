#!/usr/bin/env bash
# Sura Academy quality gates.
# Usage: bash scripts/verify.sh [--changed|--all]
#   --changed (default): gates for areas touched since HEAD (staged, unstaged, untracked)
#   --all: every gate, with coverage thresholds where a coverage driver exists
set -uo pipefail
MODE="${1:---changed}"
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
have() { command -v "$1" >/dev/null 2>&1; }

# ---------- Laravel API ----------
if [ -f apps/api/artisan ] && touches '^(apps/api/|contracts/)'; then
  gate "api:pint" in_dir apps/api vendor/bin/pint --test
  gate "api:phpstan" in_dir apps/api vendor/bin/phpstan analyse --no-progress --memory-limit=1G
  if [ "$MODE" = "--all" ] && php -m 2>/dev/null | grep -qiE '^(pcov|xdebug)$'; then
    gate "api:tests+coverage(80)" in_dir apps/api php artisan test --parallel --coverage --min=80
  else
    gate "api:tests" in_dir apps/api php artisan test --parallel
  fi
fi

# ---------- OpenAPI contract ----------
if [ -f contracts/openapi/sura-api.v1.yaml ] && touches '^contracts/'; then
  if have npx; then
    gate "contract:lint" npx --yes @redocly/cli@latest lint contracts/openapi/sura-api.v1.yaml
  else
    RESULTS+=("❌ contract:lint (npx not found)"); FAILED=1
  fi
fi

# ---------- Dart / Flutter ----------
if [ -f pubspec.yaml ] && touches '^(apps/(family|teacher|kids)/|packages/|pubspec)'; then
  gate "dart:format" dart format --output=none --set-exit-if-changed apps packages
  gate "dart:analyze" dart analyze --fatal-infos .
  for d in packages/* apps/family apps/teacher apps/kids; do
    [ -d "$d/test" ] || continue
    if [ "$MODE" = "--all" ] || changed_files | grep -q "^$d/"; then
      gate "flutter:test:$d" in_dir "$d" flutter test
    fi
  done
  if [ "$MODE" = "--all" ]; then
    for app in apps/family apps/teacher; do
      [ -d "$app/build/web" ] || continue
      gate "web:no-third-party-resources:$app" sh -c "! grep -rIlE 'gstatic\.com|fonts\.googleapis\.com' '$app/build/web'"
    done
  fi
fi

# ---------- Traceability (always) ----------
if have php; then
  gate "traceability" php scripts/check-traceability.php --quick
else
  RESULTS+=("❌ traceability (php not found)"); FAILED=1
fi

echo ""
echo "──────── verify summary ($MODE) ────────"
printf '%s\n' "${RESULTS[@]}"
exit $FAILED
