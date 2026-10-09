#!/usr/bin/env bash
# Regenerate the Dart client (packages/sura_api) from the OpenAPI contract. Deterministic: running twice yields no diff.
set -euo pipefail
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"
SPEC=contracts/openapi/sura-api.v1.yaml
[ -f "$SPEC" ] || { echo "missing $SPEC"; exit 1; }
npx --yes @redocly/cli@latest lint "$SPEC"
npx --yes @redocly/cli@latest bundle "$SPEC" -o packages/sura_api/openapi.bundled.yaml
cd packages/sura_api
dart run swagger_parser          # reads swagger_parser.yaml (schema_path: openapi.bundled.yaml, output: lib/src/generated)
dart run build_runner build --delete-conflicting-outputs
dart format lib/src/generated
echo "✅ client regenerated from $SPEC"
