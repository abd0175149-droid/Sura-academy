// Fake gateway, popup and iframe modes: send {status, mode} to the app origin (validated by the
// server before it rendered data-origin), then close the popup.
(() => {
  const main = document.getElementById('pay');
  const state = document.getElementById('pay-state');
  const origin = main.dataset.origin;
  const mode = main.dataset.mode;
  for (const button of main.querySelectorAll('button[data-status]')) {
    button.addEventListener('click', () => {
      const message = JSON.stringify({ status: button.dataset.status, mode });
      const target = mode === 'popup' ? window.opener : window.parent !== window ? window.parent : null;
      if (!target) {
        // COOP same-origin on the app severs window.opener: this is what the probe looks for.
        state.textContent = state.dataset.noOpener;
        return;
      }
      target.postMessage(message, origin);
      state.textContent = state.dataset.sent;
      if (mode === 'popup') setTimeout(() => window.close(), 300);
    });
  }
})();
