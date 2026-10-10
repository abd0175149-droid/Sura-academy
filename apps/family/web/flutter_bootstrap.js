{{flutter_js}}
{{flutter_build_config}}

// ADR-010: never fetch fonts or engine resources from third parties.
// Fallback fonts resolve against our own origin; bundled fonts arrive in WP-1.6.
_flutter.loader.load({
  config: {
    fontFallbackBaseUrl: '/fonts/',
  },
});
