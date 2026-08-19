// Page script for game-tap-correct.html — extracted from inline <script>.
// Loaded via <script src="./assets/js/game-tap-correct.js"></script>.

/* ═══════════════════════════════════════════════════════════════════
 *  Page script: game-tap-correct.html
 * 
 *  TABLE OF CONTENTS
 *  ─────────────────
 *    reveal the correct one .............................. line   56
 *    clear the wrong highlight after a moment so the… .... line   65
 *    also clear the auto-painted right-reveal from t… .... line   68
 *    initial state ....................................... line   76
 * ═══════════════════════════════════════════════════════════════════ */
  (function () {
    const group = document.getElementById('ansGroup');
    const btns = Array.from(group.querySelectorAll('[data-ans]'));
    const heartsChip = document.getElementById('heartsChip');
    const progressFill = document.getElementById('progressFill');
    const continueBtn = document.getElementById('continueBtn');
    const tip = document.getElementById('tipText');

    let hearts = 3;
    let done = false;

    function paintHearts() {
      heartsChip.textContent = '❤️ ' + hearts;
    }

    function setTip(text, tone) {
      tip.innerHTML = text;
      tip.classList.remove('text-ink', 'text-mint-ink', 'text-coral-ink');
      tip.classList.add(tone || 'text-ink');
    }

    btns.forEach(function (b) {
      b.addEventListener('click', function (e) {
        if (done) { e.stopImmediatePropagation(); return; }
        // Let the global handler paint correct/wrong classes, then react.
        const correct = b.getAttribute('data-ans') === 'correct';
        if (correct) {
          done = true;
          btns.forEach(function (o) { o.disabled = true; });
          progressFill.classList.remove('w-50');
          progressFill.classList.add('w-60');
          setTip('<span class="font-extrabold">Yay!</span> 7 is a number — digits 0–9.', 'text-mint-ink');
          continueBtn.disabled = false;
          continueBtn.classList.remove('opacity-50');
          continueBtn.onclick = function () { location.href = 'game-spell-word.html'; };
        } else {
          hearts = Math.max(0, hearts - 1);
          paintHearts();
          if (hearts === 0) {
            done = true;
            btns.forEach(function (o) { o.disabled = true; });
            // reveal the correct one
            const right = group.querySelector('[data-ans="correct"]');
            if (right) right.classList.add('correct');
            setTip('<span class="font-extrabold">No hearts left.</span> The answer was <b>7</b>. Tap Continue to try next.', 'text-coral-ink');
            continueBtn.disabled = false;
            continueBtn.classList.remove('opacity-50');
            continueBtn.onclick = function () { location.href = 'game-spell-word.html'; };
          } else {
            setTip('<span class="font-extrabold">Try again.</span> Numbers use digits like 0–9.', 'text-coral-ink');
            // clear the wrong highlight after a moment so they can retry
            setTimeout(function () {
              b.classList.remove('wrong');
              // also clear the auto-painted right-reveal from the global handler
              btns.forEach(function (o) { o.classList.remove('correct'); });
            }, 900);
          }
        }
      });
    });

    // initial state
    paintHearts();
    continueBtn.classList.add('opacity-50');
  })();
