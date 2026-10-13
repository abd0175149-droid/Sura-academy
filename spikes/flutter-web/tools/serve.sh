#!/usr/bin/env bash
# WP-0.5: start the spike servers — app origin on 127.0.0.1:27200, fake payment origin on
# 127.0.0.1:27201 — and print the start links.
#
#   bash spikes/flutter-web/tools/serve.sh            local only (Chrome on this machine)
#   bash spikes/flutter-web/tools/serve.sh --tunnel   plus two Cloudflare quick tunnels (iPad, Chromebook)
#   bash spikes/flutter-web/tools/serve.sh --livekit  plus a local LiveKit dev server in Docker
#   bash spikes/flutter-web/tools/stop.sh             stop everything this script started
#
# The LiveKit Cloud key for the iPad video check is read by the server at runtime from WP-0.4's
# key file (owner's decision 2026-10-13); this script never reads it.
set -euo pipefail

here="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
var="$here/server/var"
cloudflared="${CLOUDFLARED:-$HOME/tools/cloudflared}"
mkdir -p "$var"
chmod 700 "$var"
bash "$here/tools/stop.sh" >/dev/null 2>&1 || true

tunnel=0
livekit=0
for arg in "$@"; do
  case "$arg" in
    --tunnel) tunnel=1 ;;
    --livekit) livekit=1 ;;
    *) echo "unknown option $arg" >&2; exit 2 ;;
  esac
done

[[ -f "$here/app/build/web-wasm/build-id.txt" ]] || echo "note: no builds yet: bash $here/tools/build.sh" >&2

start_tunnel() { # name port
  nohup "$cloudflared" tunnel --no-autoupdate --url "http://127.0.0.1:$2" >"$var/$1.log" 2>&1 &
  echo $! >"$var/$1.pid"
}
wait_tunnel() { # name → prints url
  local url=""
  for _ in $(seq 1 60); do
    url="$(grep -oE 'https://[a-z0-9-]+\.trycloudflare\.com' "$var/$1.log" | head -n1 || true)"
    [[ -n "$url" ]] && break
    sleep 1
  done
  [[ -n "$url" ]] || { echo "tunnel $1 did not start; see $var/$1.log" >&2; exit 1; }
  echo "$url"
}

app_origin="http://127.0.0.1:27200"
pay_origin="http://127.0.0.1:27201"
if [[ $tunnel == 1 ]]; then
  [[ -x "$cloudflared" ]] || { echo "missing $cloudflared" >&2; exit 1; }
  start_tunnel app-tunnel 27200
  start_tunnel pay-tunnel 27201
  app_origin="$(wait_tunnel app-tunnel)"
  pay_origin="$(wait_tunnel pay-tunnel)"
fi
echo "$app_origin" >"$var/app.url"

if [[ $livekit == 1 ]]; then
  docker rm -f sura-spike-livekit >/dev/null 2>&1 || true
  docker run -d --name sura-spike-livekit -p 7880:7880 -p 7881:7881 -p 7882:7882/udp \
    livekit/livekit-server:v1.13.7 --dev --bind 0.0.0.0 >/dev/null
fi

export SPIKE_APP_ORIGIN="$app_origin" SPIKE_PAY_ORIGIN="$pay_origin"
PHP_CLI_SERVER_WORKERS=6 nohup php -S 127.0.0.1:27200 "$here/server/public/index.php" >"$var/app-server.log" 2>&1 &
echo $! >"$var/app-server.pid"
PHP_CLI_SERVER_WORKERS=2 nohup php -S 127.0.0.1:27201 "$here/server/public/pay.php" >"$var/pay-server.log" 2>&1 &
echo $! >"$var/pay-server.pid"

for _ in $(seq 1 20); do curl -fsS http://127.0.0.1:27200/health >/dev/null 2>&1 && break; sleep 0.3; done
if [[ $tunnel == 1 ]]; then
  for _ in $(seq 1 30); do
    # WSL caches the "not found" answer from before the tunnel existed; ask Cloudflare DNS directly.
    curl -fsS --doh-url https://1.1.1.1/dns-query "$app_origin/health" >/dev/null 2>&1 && break
    sleep 1
  done
fi

key="$(php -r '$d = json_decode((string) @file_get_contents($argv[1]), true); echo $d["run_key"] ?? "?";' "$var/spike.json")"
cat <<EOT
app origin:      $app_origin
payment origin:  $pay_origin
start (this PC): http://127.0.0.1:27200/start?k=$key&d=pc-chrome&n=ethernet
EOT
if [[ $tunnel == 1 ]]; then
  echo "start (iPad):    $app_origin/start?k=$key&d=ipad-safari&n=wifi"
  echo "start (Chromebook): $app_origin/start?k=$key&d=chromebook-chrome&n=wifi"
fi
echo "logs:            $var/*.log"
