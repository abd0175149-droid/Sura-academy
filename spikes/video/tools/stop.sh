#!/usr/bin/env bash
# Stops everything the spike tools started: each one leaves token-server/var/<name>.pid
# (server, tunnel, minio-tunnel, bot, bot3, …). By pid file only, never by name pattern.
set -uo pipefail

var="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)/token-server/var"
for file in "$var"/*.pid; do
  [[ -f "$file" ]] || continue
  pid="$(cat "$file")"
  if kill -0 "$pid" 2>/dev/null; then
    kill "$pid" && echo "stopped $(basename "$file" .pid) ($pid)"
  fi
  rm -f "$file"
done
rm -f "$var/tunnel.url"
docker rm -f sura-spike-throttled >/dev/null 2>&1 || true
