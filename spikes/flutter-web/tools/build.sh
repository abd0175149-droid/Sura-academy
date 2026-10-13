#!/usr/bin/env bash
# WP-0.5: build both web variants (ADR-010 §1–2), stamp a build id, precompress (brotli + gzip)
# and write results/sizes.csv. `--apk` also builds the Android APK for the screenshot comparison.
#
#   bash spikes/flutter-web/tools/build.sh [--apk]
set -euo pipefail

here="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
app="$here/app"
cd "$app"
flutter pub get >/dev/null

stamp="$(date -u +%Y%m%dT%H%M%SZ)"
flutter build web --wasm --no-web-resources-cdn --release -o build/web-wasm
flutter build web --no-web-resources-cdn --release -o build/web-js
for build in web-wasm web-js; do
  echo "$stamp-$build" >"build/$build/build-id.txt"
done
node "$here/tools/compress.mjs" "$app/build/web-wasm" "$app/build/web-js" >"$here/results/sizes.csv"
echo "sizes: $here/results/sizes.csv"

if [[ "${1:-}" == "--apk" ]]; then
  export JAVA_HOME="${JAVA_HOME:-$HOME/tools/jdk-21}"
  export ANDROID_HOME="${ANDROID_HOME:-$HOME/tools/android-sdk}"
  export PATH="$JAVA_HOME/bin:$PATH"
  # The run key lets the phone and the emulator post screenshots; it lives in server/var (git-ignored).
  key="$(php -r '$d = json_decode((string) @file_get_contents($argv[1]), true); echo $d["run_key"] ?? "";' "$here/server/var/spike.json")"
  [[ -n "$key" ]] || { echo "start the server once first (tools/serve.sh) so it creates the run key" >&2; exit 1; }
  # Server URL: the app tunnel when one is running (real phone and emulator both reach it),
  # otherwise the emulator's view of this machine. Rebuild after every new tunnel.
  server="$(cat "$here/server/var/app.url" 2>/dev/null || true)"
  [[ "$server" == https://* ]] || server="http://10.0.2.2:27200"
  flutter build apk --release --split-per-abi --target-platform android-arm64,android-x64 \
    --dart-define=SPIKE_KEY="$key" --dart-define=SPIKE_SERVER="$server/"
  echo "apk for: $server"
fi
