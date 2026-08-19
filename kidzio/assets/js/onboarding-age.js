// Page script for onboarding-age.html — extracted from inline <script>.
// Loaded via <script src="./assets/js/onboarding-age.js"></script>.

  (function () {
    const cards = document.querySelectorAll('[data-age]');
    const badge = document.getElementById('pickBadge');
    const cta = document.getElementById('continueAge');

    cards.forEach(function (c) {
      c.addEventListener('click', function () {
        cards.forEach(function (o) { o.classList.remove('is-selected'); });
        c.classList.add('is-selected');
        const name = c.getAttribute('data-name');
        const range = c.getAttribute('data-range');
        badge.textContent = name + ' · ' + range;
        cta.textContent = name + ' ' + range;
      });
    });
  })();
