#!/usr/bin/env bash
# WP-0.4 video spike (CLS-14, NFR «الشبكة»): one participant on a 1.5 Mbit/s link.
# Runs the bot in a Playwright container whose network is shaped with `tc` (NET_ADMIN inside the
# container only; no sudo on the host): 1.5 Mbit/s up and 1.5 Mbit/s down, both queued (tbf, ifb).
# Joins through the public tunnel, so start tools/serve.sh first. Stats go to
# token-server/var/stats-bot-<seat>.jsonl every 2 s while the owner watches it from the devices.
#
#   bash spikes/video/tools/throttled.sh [minutes=20] [seat=2] [provider=livekit] [rate=1500kbit]
set -euo pipefail

here="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
repo="$(cd "$here/../.." && pwd)"
var="$here/token-server/var"
minutes="${1:-20}"
seat="${2:-2}"
provider="${3:-livekit}"
rate="${4:-1500kbit}"
image="mcr.microsoft.com/playwright:v1.64.0-noble"
url="$(cat "$var/tunnel.url" 2>/dev/null || true)"
[[ -n "$url" ]] || { echo "no tunnel: run tools/serve.sh first" >&2; exit 1; }

docker rm -f sura-spike-throttled >/dev/null 2>&1 || true
docker run --rm --name sura-spike-throttled --cap-add NET_ADMIN \
  -v "$repo/scripts/web/node_modules:/repo/scripts/web/node_modules:ro" \
  -v "$here/tools:/repo/spikes/video/tools:ro" \
  -v "$var:/repo/spikes/video/token-server/var" \
  -e RATE="$rate" -e URL="$url" -e SEAT="$seat" -e PROVIDER="$provider" -e MINUTES="$minutes" \
  "$image" bash -c '
    set -e
    apt-get update -qq >/dev/null && apt-get install -y -qq iproute2 >/dev/null
    # Upload: token bucket on eth0. Download: incoming traffic redirected through ifb0 and queued
    # by a second token bucket (a policer would drop bursts and understate a real 1.5 Mbit/s line;
    # measured 2026-10-12: ~1.2 Mbit/s TCP goodput with ifb vs ~0.5 with a policer).
    ip link add ifb0 type ifb && ip link set ifb0 up
    tc qdisc add dev eth0 handle ffff: ingress
    tc filter add dev eth0 parent ffff: protocol all u32 match u32 0 0 action mirred egress redirect dev ifb0
    tc qdisc add dev ifb0 root tbf rate "$RATE" burst 32kbit latency 300ms
    tc qdisc add dev eth0 root tbf rate "$RATE" burst 32kbit latency 300ms
    tc qdisc show dev eth0; tc qdisc show dev ifb0
    node /repo/spikes/video/tools/bot.mjs --server "$URL" --seat "$SEAT" --provider "$PROVIDER" \
      --network limited-1.5mbit --stats --minutes "$MINUTES"
  ' 2>&1 | tee "$var/throttled.log"
