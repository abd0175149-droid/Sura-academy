{{flutter_js}}
{{flutter_build_config}}

// WP-0.5 spike bootstrap (ADR-010). Marks every start-up stage for D-22, loads Flutter with our own
// font-fallback origin, removes the HTML splash on the first Flutter frame, and — after the app is
// interactive — registers the service worker and fills its cache with what this browser loaded.
(() => {
  const perf = window.performance;
  perf.mark('sura-boot');
  const meta = (name) => document.querySelector(`meta[name="sura-${name}"]`)?.content ?? '';
  const params = new URLSearchParams(location.search);
  const spike = (window.suraSpike = { lcp: null });

  try {
    new PerformanceObserver((list) => {
      for (const e of list.getEntries()) spike.lcp = e.startTime;
    }).observe({ type: 'largest-contentful-paint', buffered: true });
  } catch (_) {
    // Safari before 26 has no LCP entries; FCP (paint timing) is used instead.
  }

  window.addEventListener('flutter-first-frame', () => {
    perf.mark('sura-first-frame');
    document.getElementById('splash')?.remove();
  }, { once: true });

  const nonce = meta('nonce') || undefined;
  const config = {
    // Never Google's default (https://fonts.gstatic.com/s/): missing glyphs resolve on our origin.
    fontFallbackBaseUrl: new URL('fonts/fallback/', document.baseURI).href,
    nonce,
  };
  if (meta('force-wasm') === '1') {
    // Experiment only: let Safari/Firefox try the WebAssembly build (flutter.js default: Chromium only).
    config.wasmAllowList = { blink: true, webkit: true, gecko: true };
  }

  _flutter.loader.load({
    config,
    nonce,
    onEntrypointLoaded: async (engineInitializer) => {
      perf.mark('sura-entrypoint');
      // A custom onEntrypointLoaded must pass the config on itself (Flutter docs, initialization).
      const appRunner = await engineInitializer.initializeEngine(config);
      perf.mark('sura-engine');
      await appRunner.runApp();
      perf.mark('sura-app-running');
    },
  });

  // --- after "interactive" (marked by Dart): service worker + cache, then the auto-run hand-off ---
  const cacheName = (scope) => `sura-spike-${meta('build-id')}-${new URL(scope).pathname}`;
  const swReady = (async () => {
    await new Promise((resolve) => {
      const seen = () => perf.getEntriesByName('sura-interactive').length > 0;
      if (seen()) return resolve();
      try {
        new PerformanceObserver((list, obs) => {
          if (list.getEntriesByName('sura-interactive').length) { obs.disconnect(); resolve(); }
        }).observe({ type: 'mark' });
      } catch (_) {
        setTimeout(resolve, 3000);
      }
    });
    if (!('serviceWorker' in navigator) || !('caches' in window)) return 'unsupported';
    const reg = await navigator.serviceWorker.register('sw.js', { scope: './' });
    await navigator.serviceWorker.ready;
    // Same-origin files this browser actually needed (engine variant, fonts, …), so a warm start
    // never touches the network. API calls and other origins are left alone.
    const scope = reg.scope;
    const urls = perf.getEntriesByType('resource')
      .filter((e) => !['fetch', 'xmlhttprequest', 'beacon'].includes(e.initiatorType))
      .map((e) => e.name.split('#')[0])
      .filter((u) => u.startsWith(scope));
    const cache = await caches.open(cacheName(scope));
    await cache.addAll([...new Set(['./', ...urls])]);
    return 'ready';
  })().catch((e) => `failed: ${e}`);

  spike.next = async () => {
    const sw = await Promise.race([swReady, new Promise((r) => setTimeout(() => r('timeout'), 20000))]);
    const back = new URLSearchParams({ k: params.get('k') ?? '', d: params.get('d') ?? '', n: params.get('n') ?? '', sw });
    location.replace(`/auto?${back}`);
  };
})();
