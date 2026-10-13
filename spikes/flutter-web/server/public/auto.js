// Auto-run (WP-0.5): walks every variant with N cold loads (fresh r-<run> URLs, caches cleared
// before each variant) and M warm loads (same URL, service worker active). Each app load posts its
// timings and comes back here via window.suraSpike.next(). State lives in localStorage.
(() => {
  const main = document.getElementById('auto');
  const plan = JSON.parse(main.dataset.plan);
  const texts = JSON.parse(main.dataset.texts);
  const out = document.getElementById('auto-state');
  const params = new URLSearchParams(location.search);
  const carry = new URLSearchParams();
  for (const k of ['k', 'd', 'n']) if (params.get(k)) carry.set(k, params.get(k));
  const KEY = 'sura-auto-plan';
  const fill = (t, vars) => t.replace(/:(\w+)/g, (_, k) => vars[k]);

  const build = () => {
    const steps = [];
    for (const variant of plan.variants) {
      let url = '';
      steps.push({ clean: true });
      for (let i = 0; i < plan.cold; i++) {
        const run = 'r-' + crypto.getRandomValues(new Uint32Array(2)).reduce((s, n) => s + n.toString(16).padStart(8, '0'), '');
        url = `/v/${variant}/${run}/`;
        steps.push({ url, phase: 'cold' });
      }
      for (let i = 0; i < plan.warm; i++) steps.push({ url, phase: 'warm' });
    }
    return { steps, i: 0 };
  };

  const load = () => { try { return JSON.parse(localStorage.getItem(KEY)); } catch (_) { return null; } };
  const save = (s) => { try { localStorage.setItem(KEY, JSON.stringify(s)); } catch (_) { /* private mode */ } };

  const clean = async () => {
    out.textContent = texts.cleaning;
    for (const r of (await navigator.serviceWorker?.getRegistrations?.()) ?? []) await r.unregister();
    for (const k of (await window.caches?.keys?.()) ?? []) await caches.delete(k);
  };

  const run = async () => {
    let state = params.get('start') === '1' ? build() : load();
    if (!state) state = build();
    else if (params.get('start') !== '1') state.i += 1; // came back from an app load
    const loads = state.steps.filter((s) => s.url).length;
    while (state.i < state.steps.length && state.steps[state.i].clean) {
      await clean();
      state.i += 1;
    }
    save(state);
    if (state.i >= state.steps.length) {
      out.textContent = texts.done;
      document.getElementById('auto-back').hidden = false;
      try { localStorage.removeItem(KEY); } catch (_) { /* ignore */ }
      return;
    }
    const step = state.steps[state.i];
    const done = state.steps.slice(0, state.i).filter((s) => s.url).length;
    out.textContent = fill(texts.step, { i: done + 1, n: loads });
    const q = new URLSearchParams(carry);
    q.set('auto', '1');
    q.set('phase', step.phase);
    setTimeout(() => location.replace(`${step.url}?${q}`), 400);
  };
  run();
})();
