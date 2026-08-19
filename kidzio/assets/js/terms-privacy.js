// Page script for terms-privacy.html — extracted from inline <script>.
// Loaded via <script src="./assets/js/terms-privacy.js"></script>.

  (function () {
    const tabs = document.querySelectorAll('[data-tab]');
    const panels = document.querySelectorAll('[data-panel]');
    tabs.forEach(function (t) {
      t.addEventListener('click', function () {
        const key = t.getAttribute('data-tab');
        tabs.forEach(function (o) {
          const active = o === t;
          o.classList.toggle('chip-primary', active);
          o.setAttribute('aria-selected', active ? 'true' : 'false');
        });
        panels.forEach(function (p) {
          p.classList.toggle('hidden', p.getAttribute('data-panel') !== key);
        });
      });
    });
  })();
