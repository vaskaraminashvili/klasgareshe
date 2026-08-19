// Page script for game-counting.html — extracted from inline <script>.
// Loaded via <script src="./assets/js/game-counting.js"></script>.

/* ═══════════════════════════════════════════════════════════════════
 *  Page script: game-counting.html
 * 
 *  TABLE OF CONTENTS
 *  ─────────────────
 *    sync the quick-answer grid buttons ............... line   39
 *    reset feedback hint .............................. line   52
 *    Stepper buttons .................................. line   61
 *    Check button ..................................... line   81
 *    correct .......................................... line   86
 *    wrong — paint chosen as wrong, reveal correct .... line  101
 *    allow retry ...................................... line  110
 * ═══════════════════════════════════════════════════════════════════ */
  (function () {
    const stepper = document.getElementById('stepperVal');
    const minus = document.getElementById('minusBtn');
    const plus = document.getElementById('plusBtn');
    const group = document.getElementById('ansGroup');
    const btns = Array.from(group.querySelectorAll('[data-val]'));
    const checkBtn = document.getElementById('checkBtn');
    const feedback = document.getElementById('feedback');
    const scoreChip = document.getElementById('scoreChip');
    const progressFill = document.getElementById('progressFill');
    const fruitGrid = document.getElementById('fruitGrid');

    const correctAnswer = parseInt(group.querySelector('[data-ans="correct"]').getAttribute('data-val'), 10);
    const TRUE_COUNT = fruitGrid.querySelectorAll('span').length; // reality check: 8

    let pick = null;          // user's current chosen value
    let answered = false;     // locked after correct Check
    let score = 90;

    function paintPick() {
      stepper.textContent = pick === null ? '0' : String(pick);
      // sync the quick-answer grid buttons
      btns.forEach(function (b) {
        b.classList.remove('correct', 'wrong');
        b.classList.toggle('is-pick', String(pick) === b.getAttribute('data-val'));
        b.setAttribute('aria-pressed', String(pick) === b.getAttribute('data-val'));
      });
      checkBtn.disabled = pick === null || answered;
      checkBtn.classList.toggle('opacity-50', checkBtn.disabled);
    }

    function setPick(v) {
      if (answered) return;
      pick = Math.max(0, Math.min(99, v));
      // reset feedback hint
      feedback.textContent = 'Press Check to confirm your answer.';
      feedback.classList.remove('text-mint-ink', 'text-coral-ink');
      feedback.classList.add('text-muted');
      // clear any previous correct/wrong paint (they could be stale after re-pick)
      btns.forEach(function (b) { b.classList.remove('correct', 'wrong'); });
      paintPick();
    }

    // Stepper buttons
    minus.addEventListener('click', function () {
      if (answered) return;
      setPick((pick === null ? 0 : pick) - 1);
    });
    plus.addEventListener('click', function () {
      if (answered) return;
      setPick((pick === null ? 0 : pick) + 1);
    });

    // Quick-answer grid → pick (but don't paint correct/wrong yet)
    btns.forEach(function (b) {
      b.addEventListener('click', function (e) {
        if (answered) return;
        // stop the global handler in app.js from painting correct/wrong prematurely
        e.stopImmediatePropagation();
        setPick(parseInt(b.getAttribute('data-val'), 10));
      });
    });

    // Check button
    checkBtn.addEventListener('click', function () {
      if (pick === null) return;
      const chosenBtn = btns.find(function (b) { return parseInt(b.getAttribute('data-val'), 10) === pick; });
      if (pick === correctAnswer && pick === TRUE_COUNT) {
        // correct
        if (chosenBtn) chosenBtn.classList.add('correct');
        answered = true;
        score += 20;
        scoreChip.textContent = '⭐ ' + score;
        feedback.innerHTML = '<i class="ph-fill ph-check-circle text-mint-ink"></i> Perfect! ' + TRUE_COUNT + ' apples. +20 XP';
        feedback.classList.remove('text-muted', 'text-coral-ink');
        feedback.classList.add('text-mint-ink');
        progressFill.classList.remove('w-45');
        progressFill.classList.add('w-60');
        checkBtn.innerHTML = '<i class="ph-fill ph-arrow-right"></i> Continue';
        checkBtn.disabled = false;
        checkBtn.classList.remove('opacity-50');
        checkBtn.onclick = function () { location.href = 'lesson-completed.html'; };
      } else {
        // wrong — paint chosen as wrong, reveal correct
        if (chosenBtn) chosenBtn.classList.add('wrong');
        const rightBtn = group.querySelector('[data-ans="correct"]');
        if (rightBtn) rightBtn.classList.add('correct');
        score = Math.max(0, score - 5);
        scoreChip.textContent = '⭐ ' + score;
        feedback.innerHTML = '<i class="ph-fill ph-warning-circle text-coral-ink"></i> Not quite — there are <b>' + correctAnswer + '</b> apples. Try again (-5 pts).';
        feedback.classList.remove('text-muted', 'text-mint-ink');
        feedback.classList.add('text-coral-ink');
        // allow retry
        setTimeout(function () {
          btns.forEach(function (b) { b.classList.remove('correct', 'wrong'); });
          pick = null;
          paintPick();
        }, 1400);
      }
    });

    paintPick();
  })();
