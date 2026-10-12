#!/usr/bin/env bash
# WP-0.4 video spike: start the spike server on 127.0.0.1:27100 and a Cloudflare quick tunnel in
# front of it, then print the HTTPS link (works on phones and iPad) and the spike access code.
#
#   bash spikes/video/tools/serve.sh          start (idempotent: restarts what is already running)
#   bash spikes/video/tools/stop.sh           stop everything this script started
#
# Needs ~/tools/cloudflared (quick tunnels need no Cloudflare account). The link changes on every
# start, so webhook URLs in the provider dashboards must be updated after a restart.
set -euo pipefail

here="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
var="$here/token-server/var"
port=27100
cloudflared="${CLOUDFLARED:-$HOME/tools/cloudflared}"
mkdir -p "$var"
chmod 700 "$var"

bash "$here/tools/stop.sh" >/dev/null 2>&1 || true

[[ -x "$cloudflared" ]] || { echo "missing $cloudflared (see spikes/video/README.md)" >&2; exit 1; }
[[ -f "$here/app/build/web/index.html" ]] || echo "note: no web build yet: (cd $here/app && flutter build web --release --no-web-resources-cdn)" >&2

PHP_CLI_SERVER_WORKERS=6 nohup php -S "127.0.0.1:$port" -t "$here/token-server/public" "$here/token-server/public/index.php" \
  >"$var/server.log" 2>&1 &
echo $! >"$var/server.pid"

nohup "$cloudflared" tunnel --no-autoupdate --url "http://127.0.0.1:$port" >"$var/tunnel.log" 2>&1 &
echo $! >"$var/tunnel.pid"

url=""
for _ in $(seq 1 60); do
  url="$(grep -oE 'https://[a-z0-9-]+\.trycloudflare\.com' "$var/tunnel.log" | head -n1 || true)"
  [[ -n "$url" ]] && break
  sleep 1
done
[[ -n "$url" ]] || { echo "tunnel did not start; see $var/tunnel.log" >&2; exit 1; }
echo "$url" >"$var/tunnel.url"

for _ in $(seq 1 30); do
  # WSL caches the "not found" answer from before the tunnel existed; ask Cloudflare DNS directly.
  curl -fsS --doh-url https://1.1.1.1/dns-query "$url/health" >/dev/null 2>&1 && break
  sleep 1
done

code="$(php -r 'echo json_decode(file_get_contents($argv[1]), true)["access_code"] ?? "?";' "$var/spike.json" 2>/dev/null || echo '?')"
cat <<EOF
link:         $url
access code:  $code
webhooks:     LiveKit → $url/webhooks/livekit   ·   Zoom → $url/webhooks/zoom
logs:         $var/server.log · $var/tunnel.log
EOF
