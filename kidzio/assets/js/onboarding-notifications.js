// Page script for onboarding-notifications.html — extracted from inline <script>.
// Loaded via <script src="./assets/js/onboarding-notifications.js"></script>.

  (function () {
    const picks = document.querySelectorAll('[data-time]');
    picks.forEach(function (p) {
      p.addEventListener('click', function () {
        picks.forEach(function (o) { o.classList.remove('is-selected'); });
        p.classList.add('is-selected');
      });
    });
  })();
