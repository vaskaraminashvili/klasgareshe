// Page script for onboarding-goals.html — extracted from inline <script>.
// Loaded via <script src="./assets/js/onboarding-goals.js"></script>.

  (function () {
    const goals = document.querySelectorAll('[data-goal]');
    const badge = document.getElementById('goalBadge');
    const cta = document.getElementById('continueGoal');

    goals.forEach(function (g) {
      g.addEventListener('click', function () {
        goals.forEach(function (o) { o.classList.remove('is-selected'); });
        g.classList.add('is-selected');
        const name = g.getAttribute('data-name');
        const time = g.getAttribute('data-time');
        badge.textContent = name + ' · ' + time;
        cta.textContent = name + ' ' + time;
      });
    });
  })();
