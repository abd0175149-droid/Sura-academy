#!/usr/bin/env bash
# WP-0.4 video spike: a throwaway MinIO that LiveKit egress can upload recordings to (CLS-10, D-16
# stand-in for S3 eu-central-1). Separate from the dev MinIO in infra/docker so its default
# credentials never sit behind a public tunnel.
#
#   bash spikes/video/tools/minio-egress.sh          start container + tunnel, write var/egress.json
#   bash spikes/video/tools/minio-egress.sh purge    delete the container, its volume and all recordings
#
# Random root and egress credentials are generated on every start; the egress user may only write
# objects into the bucket. Root credentials stay in var/minio-root.json (mode 600) for listing and
# downloading. The spike server reads var/egress.json at each request.
set -euo pipefail

here="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
var="$here/token-server/var"
name=sura-spike-minio
volume=sura-spike-minio-data
port=29100
bucket=spike-recordings
image="cgr.dev/chainguard/minio@sha256:7df55d002b698e4494a131b848dd553361bbe58b7e19f62d9623c29af1ba9ccf"
cloudflared="${CLOUDFLARED:-$HOME/tools/cloudflared}"
mkdir -p "$var"

stop_tunnel() {
  if [[ -f "$var/minio-tunnel.pid" ]]; then
    kill "$(cat "$var/minio-tunnel.pid")" 2>/dev/null || true
    rm -f "$var/minio-tunnel.pid"
  fi
}

if [[ "${1:-}" == "purge" ]]; then
  stop_tunnel
  docker rm -f "$name" >/dev/null 2>&1 || true
  docker volume rm "$volume" >/dev/null 2>&1 || true
  rm -f "$var/egress.json" "$var/minio-root.json"
  echo "purged $name, volume $volume and local egress credentials"
  exit 0
fi

root_user="spike-root"
root_pass="$(openssl rand -hex 24)"
egress_user="spike-egress"
egress_pass="$(openssl rand -hex 24)"

stop_tunnel
docker rm -f "$name" >/dev/null 2>&1 || true
docker run -d --name "$name" -p "127.0.0.1:$port:9000" -v "$volume:/data" \
  -e MINIO_ROOT_USER="$root_user" -e MINIO_ROOT_PASSWORD="$root_pass" \
  "$image" server /data >/dev/null

for _ in $(seq 1 30); do
  curl -fsS "http://127.0.0.1:$port/minio/health/live" >/dev/null 2>&1 && break
  sleep 1
done

mc() { docker exec "$name" mc "$@"; }
mc alias set local http://127.0.0.1:9000 "$root_user" "$root_pass" >/dev/null
mc mb --ignore-existing "local/$bucket" >/dev/null
docker exec -i "$name" sh -c 'cat > /tmp/egress-write.json' <<JSON
{"Version":"2012-10-17","Statement":[{"Effect":"Allow","Action":["s3:PutObject","s3:AbortMultipartUpload","s3:ListMultipartUploadParts"],"Resource":["arn:aws:s3:::$bucket/*"]}]}
JSON
mc admin policy create local egress-write /tmp/egress-write.json >/dev/null
mc admin user add local "$egress_user" "$egress_pass" >/dev/null
mc admin policy attach local egress-write --user "$egress_user" >/dev/null

nohup "$cloudflared" tunnel --no-autoupdate --url "http://127.0.0.1:$port" >"$var/minio-tunnel.log" 2>&1 &
echo $! >"$var/minio-tunnel.pid"
url=""
for _ in $(seq 1 60); do
  url="$(grep -oE 'https://[a-z0-9-]+\.trycloudflare\.com' "$var/minio-tunnel.log" | head -n1 || true)"
  [[ -n "$url" ]] && break
  sleep 1
done
[[ -n "$url" ]] || { echo "minio tunnel did not start; see $var/minio-tunnel.log" >&2; exit 1; }

umask 077
php -r 'file_put_contents($argv[1], json_encode(["endpoint" => $argv[2], "bucket" => $argv[3], "access_key" => $argv[4], "secret" => $argv[5], "region" => "eu-central-1"], JSON_PRETTY_PRINT));' \
  "$var/egress.json" "$url" "$bucket" "$egress_user" "$egress_pass"
php -r 'file_put_contents($argv[1], json_encode(["user" => $argv[2], "password" => $argv[3]], JSON_PRETTY_PRINT));' \
  "$var/minio-root.json" "$root_user" "$root_pass"

echo "egress bucket: $bucket via $url (write-only user $egress_user); recordings: docker exec $name mc ls -r local/$bucket"
