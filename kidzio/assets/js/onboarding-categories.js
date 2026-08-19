// Page script for onboarding-categories.html — extracted from inline <script>.
// Loaded via <script src="./assets/js/onboarding-categories.js"></script>.

  (function () {
    const cards = document.querySelectorAll('[data-pick]');
    const countLabel = document.getElementById('pickCount');
    const countBtn = document.getElementById('countBtn');
    const MIN = 3;

    function update() {
      const selected = document.querySelectorAll('.pick-card.is-selected').length;
      countBtn.textContent = selected;
      countLabel.textContent = selected + ' / ' + MIN + (selected >= MIN ? ' · ready!' : ' selected');
      countLabel.classList.toggle('ok', selected >= MIN);
    }

    cards.forEach(function (c) {
      c.addEventListener('click', function () {
        c.classList.toggle('is-selected');
        update();
      });
    });

    update();
  })();
