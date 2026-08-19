// Page script for bedtime-lock.html — extracted from inline <script>.
// Loaded via <script src="./assets/js/bedtime-lock.js"></script>.

  (function () {
    const days = document.querySelectorAll('[data-day]');
    days.forEach(function (d) {
      d.addEventListener('click', function () { d.classList.toggle('is-selected'); });
    });
  })();
