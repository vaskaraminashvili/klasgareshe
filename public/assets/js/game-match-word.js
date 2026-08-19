// Page script for game-match-word.html — extracted from inline <script>.
// Loaded via <script src="./assets/js/game-match-word.js"></script>.

/* ═══════════════════════════════════════════════════════════════════
 *  Page script: game-match-word.html
 * 
 *  TABLE OF CONTENTS
 *  ─────────────────
 *    word buttons ........................................ line   39
 *    pic buttons ......................................... line   57
 *    find the word that paired to this pic ............... line   62
 *    Only control Check state while in 'check' mode … .... line   76
 *    mode: 'check' | 'continue' | 'retry' ................ line  126
 *    keep correct pairs, wipe wrong ones ................. line  135
 *    mode === 'check' .................................... line  148
 * ═══════════════════════════════════════════════════════════════════ */
  (function () {
    const wordBtns = Array.from(document.querySelectorAll('#words [data-word]'));
    const picBtns = Array.from(document.querySelectorAll('#pics [data-pic]'));
    const checkBtn = document.getElementById('checkBtn');
    const scoreChip = document.getElementById('scoreChip');
    const progressFill = document.getElementById('progressFill');
    const tip = document.getElementById('tip');
    const TOTAL = 4;

    const tips = {
      sun: '<span class="font-extrabold">Hint:</span> the sun is bright and warm.',
      dog: '<span class="font-extrabold">Hint:</span> a dog goes "woof"!',
      fish: '<span class="font-extrabold">Hint:</span> a fish swims in water.',
      car: '<span class="font-extrabold">Hint:</span> a car has wheels and goes vroom.'
    };

    const pairs = new Map(); // wordKey -> picKey
    let activeWord = null;
    let answered = false;

    function paint() {
      // word buttons
      wordBtns.forEach(function (b) {
        b.classList.remove('ring-2', 'ring-[var(--color-k-primary)]', 'ring-[#0E7A5A]', 'ring-[#D2304A]');
        const w = b.getAttribute('data-word');
        if (answered) {
          const p = pairs.get(w);
          if (p !== undefined) {
            b.classList.add('ring-2');
            b.classList.add(p === w ? 'ring-[#0E7A5A]' : 'ring-[#D2304A]');
          }
          return;
        }
        if (b === activeWord) {
          b.classList.add('ring-2', 'ring-[var(--color-k-primary)]');
        } else if (pairs.has(w)) {
          b.classList.add('ring-2', 'ring-[var(--color-k-primary)]');
        }
      });
      // pic buttons
      picBtns.forEach(function (b) {
        b.classList.remove('ring-2', 'ring-[var(--color-k-primary)]', 'ring-[#0E7A5A]', 'ring-[#D2304A]');
        const p = b.getAttribute('data-pic');
        if (answered) {
          // find the word that paired to this pic
          let linkedWord = null;
          pairs.forEach(function (picKey, wordKey) { if (picKey === p) linkedWord = wordKey; });
          if (linkedWord !== null) {
            b.classList.add('ring-2');
            b.classList.add(linkedWord === p ? 'ring-[#0E7A5A]' : 'ring-[#D2304A]');
          }
          return;
        }
        let isPaired = false;
        pairs.forEach(function (picKey) { if (picKey === p) isPaired = true; });
        if (isPaired) b.classList.add('ring-2', 'ring-[var(--color-k-primary)]');
      });

      // Only control Check state while in 'check' mode — leave Continue/Retry untouched
      if (mode === 'check') {
        const ready = pairs.size === TOTAL;
        checkBtn.disabled = !ready;
        checkBtn.classList.toggle('opacity-50', !ready);
      }
    }

    function clearPairTouching(key, side) {
      if (side === 'W') {
        pairs.delete(key);
      } else {
        pairs.forEach(function (picKey, wordKey) {
          if (picKey === key) pairs.delete(wordKey);
        });
      }
    }

    wordBtns.forEach(function (b) {
      b.addEventListener('click', function () {
        if (answered) return;
        activeWord = (activeWord === b) ? null : b;
        if (activeWord) {
          tip.innerHTML = tips[b.getAttribute('data-word')] || tip.innerHTML;
          tip.classList.remove('text-coral-ink', 'text-mint-ink');
          tip.classList.add('text-ink');
        }
        paint();
      });
    });

    picBtns.forEach(function (b) {
      b.addEventListener('click', function () {
        if (answered) return;
        if (!activeWord) {
          tip.innerHTML = '<i class="ph-fill ph-arrow-left text-sun-ink"></i> Tap a word on the left first.';
          tip.classList.remove('text-ink', 'text-mint-ink');
          tip.classList.add('text-coral-ink');
          return;
        }
        const wKey = activeWord.getAttribute('data-word');
        const pKey = b.getAttribute('data-pic');
        clearPairTouching(wKey, 'W');
        clearPairTouching(pKey, 'P');
        pairs.set(wKey, pKey);
        activeWord = null;
        paint();
      });
    });

    // mode: 'check' | 'continue' | 'retry'
    let mode = 'check';

    checkBtn.addEventListener('click', function () {
      if (mode === 'continue') {
        location.href = 'game-match-animal.html';
        return;
      }
      if (mode === 'retry') {
        // keep correct pairs, wipe wrong ones
        Array.from(pairs.keys()).forEach(function (w) {
          if (pairs.get(w) !== w) pairs.delete(w);
        });
        answered = false;
        mode = 'check';
        checkBtn.innerHTML = '<i class="ph-fill ph-check"></i> Check answers';
        tip.innerHTML = '<span class="font-extrabold">Hint:</span> fix the unmatched pairs.';
        tip.classList.remove('text-coral-ink', 'text-mint-ink');
        tip.classList.add('text-ink');
        paint();
        return;
      }
      // mode === 'check'
      if (pairs.size !== TOTAL) return;
      let correct = 0;
      pairs.forEach(function (pic, word) { if (pic === word) correct++; });
      answered = true;
      if (correct === TOTAL) {
        scoreChip.textContent = '+40 XP';
        progressFill.classList.remove('w-85');
        progressFill.classList.add('w-100');
        tip.innerHTML = '<i class="ph-fill ph-confetti text-mint-ink"></i> Perfect! All 4 matched. +40 XP';
        tip.classList.remove('text-ink', 'text-coral-ink');
        tip.classList.add('text-mint-ink');
        checkBtn.innerHTML = '<i class="ph-fill ph-arrow-right"></i> Continue';
        checkBtn.disabled = false;
        checkBtn.classList.remove('opacity-50');
        mode = 'continue';
      } else {
        scoreChip.textContent = '+' + (correct * 5) + ' XP';
        tip.innerHTML = '<i class="ph-fill ph-warning-circle text-coral-ink"></i> ' + correct + ' / 4 right. Tap Retry to fix the rest.';
        tip.classList.remove('text-ink', 'text-mint-ink');
        tip.classList.add('text-coral-ink');
        checkBtn.innerHTML = '<i class="ph ph-arrow-counter-clockwise"></i> Try again';
        checkBtn.disabled = false;
        checkBtn.classList.remove('opacity-50');
        mode = 'retry';
      }
      paint();
    });

    paint();
  })();
