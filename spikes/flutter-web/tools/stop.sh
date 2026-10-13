#!/usr/bin/env bash
# Stops everything tools/serve.sh started (by pid file only, never by name pattern).
set -uo pipefail

var="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)/server/var"
for file in "$var"/*.pid; do
  [[ -f "$file" ]] || continue
  pid="$(cat "$file")"
  if kill -0 "$pid" 2>/dev/null; then
    kill "$pid" && echo "stopped $(basename "$file" .pid) ($pid)"
  fi
  rm -f "$file"
done
docker rm -f sura-spike-livekit >/dev/null 2>&1 || true
