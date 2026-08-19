// Page script for game-knowledge.html — extracted from inline <script>.
// Loaded via <script src="./assets/js/game-knowledge.js"></script>.

/* ═══════════════════════════════════════════════════════════════════
 *  Page script: game-knowledge.html
 * 
 *  TABLE OF CONTENTS
 *  ─────────────────
 *    auto-submit as wrong if nothing picked .............. line   42
 *    stop global handler from painting correct/wrong… .... line   58
 *    Lifelines ........................................... line   94
 *    shuffle then slice 2 ................................ line  103
 *    fake crowd poll — weight toward correct ............. line  115
 * ═══════════════════════════════════════════════════════════════════ */
  (function () {
    const group = document.getElementById('ansGroup');
    const btns = Array.from(group.querySelectorAll('[data-val]'));
    const submitBtn = document.getElementById('submitBtn');
    const timerChip = document.getElementById('timerChip');
    const progressFill = document.getElementById('progressFill');
    const fiftyBtn = document.getElementById('lifeFifty');
    const pollBtn = document.getElementById('lifePoll');
    const hintBtn = document.getElementById('lifeHint');

    let picked = null;
    let answered = false;
    let seconds = 20;
    let mode = 'check'; // 'check' | 'continue'

    function pad(n) { return n < 10 ? '0' + n : String(n); }
    function paintTime() { timerChip.textContent = '⏱️ 00:' + pad(seconds); }

    const tick = setInterval(function () {
      if (answered) { clearInterval(tick); return; }
      seconds = Math.max(0, seconds - 1);
      paintTime();
      if (seconds === 0) {
        clearInterval(tick);
        timerChip.classList.remove('chip-sky');
        timerChip.classList.add('chip-coral');
        // auto-submit as wrong if nothing picked
        if (!picked) {
          answered = true;
          const right = group.querySelector('[data-ans="correct"]');
          if (right) right.classList.add('correct');
          submitBtn.disabled = false;
          submitBtn.classList.remove('opacity-50');
          submitBtn.innerHTML = '<i class="ph-fill ph-arrow-right"></i> Continue';
          mode = 'continue';
        }
      }
    }, 1000);

    btns.forEach(function (b) {
      b.addEventListener('click', function (e) {
        if (answered) { e.stopImmediatePropagation(); return; }
        // stop global handler from painting correct/wrong until user submits
        e.stopImmediatePropagation();
        picked = b;
        btns.forEach(function (o) {
          o.classList.remove('correct', 'wrong');
          o.classList.remove('ring-2', 'ring-[var(--color-k-primary)]');
        });
        b.classList.add('ring-2', 'ring-[var(--color-k-primary)]');
        submitBtn.disabled = false;
        submitBtn.classList.remove('opacity-50');
      });
    });

    submitBtn.addEventListener('click', function () {
      if (mode === 'continue') {
        location.href = 'lesson-completed.html';
        return;
      }
      if (!picked || answered) return;
      answered = true;
      clearInterval(tick);
      const ok = picked.getAttribute('data-ans') === 'correct';
      picked.classList.remove('ring-2', 'ring-[var(--color-k-primary)]');
      if (ok) {
        picked.classList.add('correct');
        progressFill.classList.remove('w-70');
        progressFill.classList.add('w-100');
      } else {
        picked.classList.add('wrong');
        const right = group.querySelector('[data-ans="correct"]');
        if (right) right.classList.add('correct');
      }
      submitBtn.innerHTML = '<i class="ph-fill ph-arrow-right"></i> Continue';
      mode = 'continue';
    });

    // Lifelines
    let fiftyUsed = false;
    fiftyBtn.addEventListener('click', function () {
      if (answered || fiftyUsed) return;
      fiftyUsed = true;
      fiftyBtn.disabled = true;
      fiftyBtn.classList.add('opacity-50');
      // hide 2 wrong answers (keep correct + one wrong)
      const wrongs = btns.filter(function (b) { return b.getAttribute('data-ans') !== 'correct'; });
      // shuffle then slice 2
      wrongs.sort(function () { return Math.random() - 0.5; }).slice(0, 2).forEach(function (b) {
        b.classList.add('opacity-30', 'pointer-events-none');
      });
    });

    let pollUsed = false;
    pollBtn.addEventListener('click', function () {
      if (answered || pollUsed) return;
      pollUsed = true;
      pollBtn.disabled = true;
      pollBtn.classList.add('opacity-50');
      // fake crowd poll — weight toward correct
      const votes = { Earth: 12, Mars: 68, Saturn: 8, Jupiter: 12 };
      btns.forEach(function (b) {
        const v = votes[b.getAttribute('data-val')] || 0;
        const key = b.querySelector('.ans-key');
        if (key) key.textContent = v + '%';
      });
    });

    let hintUsed = false;
    hintBtn.addEventListener('click', function () {
      if (answered || hintUsed) return;
      hintUsed = true;
      hintBtn.disabled = true;
      hintBtn.classList.add('opacity-50');
      toast('Hint: named after the Roman god of war, because of its rusty red color.');
    });

    paintTime();
    submitBtn.classList.add('opacity-50');
  })();
