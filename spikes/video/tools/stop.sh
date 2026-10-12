#!/usr/bin/env bash
# Stops what serve.sh and bot.sh started (by pid file, never by name pattern).
set -uo pipefail

var="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)/token-server/var"
for name in tunnel server bot minio-tunnel; do
  file="$var/$name.pid"
  [[ -f "$file" ]] || continue
  pid="$(cat "$file")"
  if kill -0 "$pid" 2>/dev/null; then
    kill "$pid" && echo "stopped $name ($pid)"
  fi
  rm -f "$file"
done
rm -f "$var/tunnel.url"
