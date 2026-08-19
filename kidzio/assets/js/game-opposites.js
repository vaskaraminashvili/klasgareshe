// Page script for game-opposites.html — extracted from inline <script>.
// Loaded via <script src="./assets/js/game-opposites.js"></script>.

  (function () {
    const group = document.getElementById('ansGroup');
    const btns = Array.from(group.querySelectorAll('[data-val]'));
    const heartsChip = document.getElementById('heartsChip');
    const progressFill = document.getElementById('progressFill');
    const nextBtn = document.getElementById('nextBtn');
    const promptWord = document.getElementById('promptWord');
    const promptEmoji = document.getElementById('promptEmoji');
    const peekBtns = document.querySelectorAll('[data-peek]');

    let hearts = 3;
    let done = false;

    function paintHearts() { heartsChip.textContent = '❤️ ' + hearts; }

    btns.forEach(function (b) {
      b.addEventListener('click', function (e) {
        if (done) { e.stopImmediatePropagation(); return; }
        const ok = b.getAttribute('data-ans') === 'correct';
        if (ok) {
          done = true;
          btns.forEach(function (o) { o.disabled = true; });
          progressFill.classList.remove('w-30');
          progressFill.classList.add('w-50');
          promptWord.textContent = 'SMALL';
          promptEmoji.textContent = '🐭';
          nextBtn.disabled = false;
          nextBtn.classList.remove('opacity-50');
          nextBtn.onclick = function () { location.href = 'game-body-parts.html'; };
        } else {
          hearts = Math.max(0, hearts - 1);
          paintHearts();
          if (hearts === 0) {
            done = true;
            btns.forEach(function (o) { o.disabled = true; });
            const right = group.querySelector('[data-ans="correct"]');
            if (right) right.classList.add('correct');
            nextBtn.disabled = false;
            nextBtn.classList.remove('opacity-50');
            nextBtn.onclick = function () { location.href = 'game-body-parts.html'; };
          } else {
            setTimeout(function () {
              b.classList.remove('wrong');
              btns.forEach(function (o) { o.classList.remove('correct'); });
            }, 900);
          }
        }
      });
    });

    // Tap a "more pairs" chip → briefly replaces the prompt with that pair preview
    const previews = {
      'hot ↔ cold': { word: 'HOT', emoji: '🔥' },
      'up ↔ down': { word: 'UP', emoji: '⬆️' },
      'fast ↔ slow': { word: 'FAST', emoji: '⚡' }
    };
    peekBtns.forEach(function (p) {
      p.addEventListener('click', function () {
        if (done) return;
        const label = p.querySelector('p').textContent.trim();
        const data = previews[label];
        if (!data) return;
        const prevWord = promptWord.textContent;
        const prevEmoji = promptEmoji.textContent;
        promptWord.textContent = data.word;
        promptEmoji.textContent = data.emoji;
        setTimeout(function () {
          if (done) return;
          promptWord.textContent = prevWord;
          promptEmoji.textContent = prevEmoji;
        }, 1200);
      });
    });

    nextBtn.classList.add('opacity-50');
    paintHearts();
  })();
