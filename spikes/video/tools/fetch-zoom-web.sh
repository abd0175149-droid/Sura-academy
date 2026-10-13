#!/usr/bin/env bash
# WP-0.4 video spike: fetch the Zoom Video SDK for Web (pinned, integrity-checked) into
# spikes/video/zoom-web/vendor/ (git-ignored: Zoom's licence, ~64 MB). The spike server then
# serves it from our own origin, so the probe page loads nothing from third-party hosts except
# Zoom's media servers.
set -euo pipefail

version=2.5.0
integrity="sha512-BL3vNdrjwdV/hYwhvRECQlKlwv4Pl8IX2+yNxUZwVmKL1fsLT8n4Zu20Szw4u/C612LAHpgCMaFBoLlUr3/ePw=="
here="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
vendor="$here/zoom-web/vendor"
tmp="$(mktemp -d)"
trap 'rm -rf "$tmp"' EXIT

curl -sSfL -o "$tmp/sdk.tgz" "https://registry.npmjs.org/@zoom/videosdk/-/videosdk-$version.tgz"
actual="sha512-$(openssl dgst -sha512 -binary "$tmp/sdk.tgz" | base64 -w0)"
[[ "$actual" == "$integrity" ]] || { echo "integrity mismatch for @zoom/videosdk $version" >&2; exit 1; }

mkdir -p "$tmp/x"
tar -xzf "$tmp/sdk.tgz" -C "$tmp/x"
rm -rf "$vendor"
mkdir -p "$vendor"
cp "$tmp/x/package/dist/index.umd.js" "$vendor/zoom-video-sdk.umd.js"
cp -r "$tmp/x/package/dist/lib" "$vendor/lib"
cp "$tmp/x/package/LICENSE.md" "$vendor/LICENSE.md"
echo "@zoom/videosdk $version → $vendor ($(du -sh "$vendor" | cut -f1))"
