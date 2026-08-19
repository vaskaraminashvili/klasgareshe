// Page script for export-progress.html — extracted from inline <script>.
// Loaded via <script src="./assets/js/export-progress.js"></script>.

  (function () {
    const pairs = [['data-range'], ['data-fmt']];
    pairs.forEach(function (attr) {
      const els = document.querySelectorAll('[' + attr + ']');
      els.forEach(function (e) {
        e.addEventListener('click', function () {
          els.forEach(function (o) { o.classList.remove('is-selected'); });
          e.classList.add('is-selected');
        });
      });
    });
  })();
