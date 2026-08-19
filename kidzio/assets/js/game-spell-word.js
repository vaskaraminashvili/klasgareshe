// Page script for game-spell-word.html — extracted from inline <script>.
// Loaded via <script src="./assets/js/game-spell-word.js"></script>.

/* ═══════════════════════════════════════════════════════════════════
 *  Page script: game-spell-word.html
 * 
 *  TABLE OF CONTENTS
 *  ─────────────────
 *    fill slots .................................. line   30
 *    enable check only when full ................. line   39
 *    enable erase only when something's typed .... line   43
 *    clear any red/green slot states ............. line   70
 *    mark each slot correct/wrong by position .... line   97
 * ═══════════════════════════════════════════════════════════════════ */
  (function () {
    const TARGET = 'APPLE';
    const slots = Array.from(document.querySelectorAll('[data-slot]'));
    const keys = Array.from(document.querySelectorAll('#kbd .kbd'));
    const erase = document.getElementById('eraseBtn');
    const check = document.getElementById('checkBtn');
    const speaker = document.getElementById('speakerBtn');
    const feedback = document.getElementById('feedback');
    const progressFill = document.getElementById('progressFill');

    let current = []; // letters typed so far
    let solved = false;

    function paint() {
      // fill slots
      slots.forEach(function (s, i) {
        const letter = current[i] || '';
        s.textContent = letter;
        s.classList.remove('ring-2', 'ring-[var(--color-k-primary)]', 'ring-[#0E7A5A]', 'ring-[#D2304A]');
        if (!solved && i === current.length) {
          s.classList.add('ring-2', 'ring-[var(--color-k-primary)]');
        }
      });
      // enable check only when full
      const full = current.length === TARGET.length;
      check.disabled = solved || !full;
      check.classList.toggle('opacity-50', check.disabled);
      // enable erase only when something's typed
      erase.disabled = solved || current.length === 0;
      erase.classList.toggle('opacity-50', erase.disabled);
      // active keyboard highlight: none; keep visual clean
      keys.forEach(function (k) { k.classList.remove('active'); });
    }

    function resetFeedback() {
      feedback.innerHTML = 'Tap letters to spell the word.';
      feedback.classList.remove('text-mint-ink', 'text-coral-ink');
      feedback.classList.add('text-muted');
    }

    keys.forEach(function (k) {
      k.addEventListener('click', function () {
        if (solved) return;
        if (current.length >= TARGET.length) return;
        current.push(k.getAttribute('data-k'));
        resetFeedback();
        paint();
      });
    });

    erase.addEventListener('click', function () {
      if (solved || !current.length) return;
      current.pop();
      resetFeedback();
      // clear any red/green slot states
      slots.forEach(function (s) {
        s.classList.remove('ring-2', 'ring-[#0E7A5A]', 'ring-[#D2304A]');
      });
      paint();
    });

    check.addEventListener('click', function () {
      if (solved || current.length !== TARGET.length) return;
      const guess = current.join('');
      if (guess === TARGET) {
        solved = true;
        slots.forEach(function (s) {
          s.classList.remove('ring-[var(--color-k-primary)]', 'ring-[#D2304A]');
          s.classList.add('ring-2', 'ring-[#0E7A5A]');
        });
        feedback.innerHTML = '<i class="ph-fill ph-check-circle text-mint-ink"></i> Perfect! A-P-P-L-E spells APPLE.';
        feedback.classList.remove('text-muted', 'text-coral-ink');
        feedback.classList.add('text-mint-ink');
        progressFill.classList.remove('w-60');
        progressFill.classList.add('w-80');
        check.disabled = false;
        check.classList.remove('opacity-50');
        check.innerHTML = 'Continue <i class="ph ph-arrow-right"></i>';
        check.onclick = function () { location.href = 'game-fill-letter.html'; };
        speakWord('Apple');
      } else {
        // mark each slot correct/wrong by position
        slots.forEach(function (s, i) {
          s.classList.remove('ring-[var(--color-k-primary)]');
          s.classList.add('ring-2');
          s.classList.add(current[i] === TARGET[i] ? 'ring-[#0E7A5A]' : 'ring-[#D2304A]');
        });
        feedback.innerHTML = '<i class="ph-fill ph-warning-circle text-coral-ink"></i> Not quite. Fix the red letters.';
        feedback.classList.remove('text-muted', 'text-mint-ink');
        feedback.classList.add('text-coral-ink');
      }
    });

    function speakWord(w) {
      if ('speechSynthesis' in window) {
        const u = new SpeechSynthesisUtterance(w);
        u.rate = 0.85;
        window.speechSynthesis.cancel();
        window.speechSynthesis.speak(u);
      } else {
        toast('Hear: ' + w);
      }
    }
    speaker.addEventListener('click', function () { speakWord('Apple'); });

    paint();
  })();
