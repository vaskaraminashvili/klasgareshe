// Page script for screen-time.html — extracted from inline <script>.
// Loaded via <script src="./assets/js/screen-time.js"></script>.

  (function () {
    const picks = document.querySelectorAll('[data-limit]');
    const limitVal = document.getElementById('limitVal');
    const usedText = document.getElementById('usedText');
    const limitBar = document.getElementById('limitBar');
    picks.forEach(function (p) {
      p.addEventListener('click', function () {
        picks.forEach(function (o) { o.classList.remove('is-selected'); });
        p.classList.add('is-selected');
        const l = p.getAttribute('data-limit');
        limitVal.textContent = l;
        usedText.textContent = '18/' + l;
      });
    });
  })();
