// Page script for game-connect-pair.html — extracted from inline <script>.
// Loaded via <script src="./assets/js/game-connect-pair.js"></script>.

/* ═══════════════════════════════════════════════════════════════════
 *  Page script: game-connect-pair.html
 * 
 *  TABLE OF CONTENTS
 *  ─────────────────
 *    connection state: map leftKey -> rightKey ........... line   31
 *    Fixed line anchor points based on SVG viewBox 3… .... line   35
 *    clear existing lines ................................ line   44
 *    highlight active left selection ..................... line   67
 *    disable Check until all 3 pairs set ................. line   73
 *    Helpers ............................................. line   85
 *    drop any existing pairing on either side ............ line  127
 *    paint cards ......................................... line  147
 *    keep correct pairs, wipe wrong ones ................. line  179
 * ═══════════════════════════════════════════════════════════════════ */
  (function () {
    const lineLayer = document.getElementById('lineLayer');
    const leftBtns = Array.from(document.querySelectorAll('#colLeft [data-side="L"]'));
    const rightBtns = Array.from(document.querySelectorAll('#colRight [data-side="R"]'));
    const resetBtn = document.getElementById('resetBtn');
    const checkBtn = document.getElementById('checkBtn');
    const scoreChip = document.getElementById('scoreChip');
    const tip = document.getElementById('tip');
    const TOTAL = 3;
    const COLORS = ['#4ED6A8', '#FF8FC5', '#7C5CFF'];

    // connection state: map leftKey -> rightKey
    const pairs = new Map();
    let activeLeft = null;

    // Fixed line anchor points based on SVG viewBox 340x380
    const leftX = 60;
    const rightX = 280;
    const yPositions = [60, 180, 300];

    function leftPos(btn) { return yPositions[parseInt(btn.getAttribute('data-pos'), 10)]; }
    function rightPos(btn) { return yPositions[parseInt(btn.getAttribute('data-pos'), 10)]; }

    function render() {
      // clear existing lines
      while (lineLayer.firstChild) lineLayer.removeChild(lineLayer.firstChild);
      let idx = 0;
      pairs.forEach(function (rightKey, leftKey) {
        const l = leftBtns.find(function (b) { return b.getAttribute('data-key') === leftKey; });
        const r = rightBtns.find(function (b) { return b.getAttribute('data-key') === rightKey; });
        if (!l || !r) return;
        const checked = checkBtn.dataset.checked === '1';
        const correct = leftKey === rightKey;
        const color = !checked ? COLORS[idx % COLORS.length] : (correct ? '#0E7A5A' : '#D2304A');
        const line = document.createElementNS('http://www.w3.org/2000/svg', 'line');
        line.setAttribute('x1', leftX);
        line.setAttribute('y1', leftPos(l));
        line.setAttribute('x2', rightX);
        line.setAttribute('y2', rightPos(r));
        line.setAttribute('stroke', color);
        line.setAttribute('stroke-width', '5');
        line.setAttribute('stroke-linecap', 'round');
        if (!checked) line.setAttribute('stroke-dasharray', '8 8');
        lineLayer.appendChild(line);
        idx++;
      });

      // highlight active left selection
      leftBtns.forEach(function (b) {
        b.classList.toggle('ring-primary', b === activeLeft);
        b.classList.toggle('is-selected', b === activeLeft);
      });

      // disable Check until all 3 pairs set
      checkBtn.disabled = pairs.size !== TOTAL || checkBtn.dataset.checked === '1';
      checkBtn.classList.toggle('opacity-50', checkBtn.disabled);
      scoreChip.textContent = '⭐ ' + countCorrect() + ' / ' + TOTAL;
    }

    function countCorrect() {
      let n = 0;
      pairs.forEach(function (r, l) { if (r === l) n++; });
      return n;
    }

    // Helpers
    function removePairsTouching(key, side) {
      if (side === 'L') {
        pairs.delete(key);
      } else {
        pairs.forEach(function (rightKey, leftKey) {
          if (rightKey === key) pairs.delete(leftKey);
        });
      }
    }

    function resetCheckedState() {
      checkBtn.dataset.checked = '';
      checkBtn.innerHTML = '<i class="ph-fill ph-check"></i> Check';
      leftBtns.concat(rightBtns).forEach(function (b) {
        b.classList.remove('ring-2', 'ring-[#0E7A5A]', 'ring-[#D2304A]');
      });
      tip.innerHTML = '<i class="ph-fill ph-info text-primary-ink"></i> Tap a card on the left, then its match on the right.';
      tip.classList.remove('text-mint-ink', 'text-coral-ink');
      tip.classList.add('text-muted');
    }

    leftBtns.forEach(function (b) {
      b.addEventListener('click', function () {
        if (checkBtn.dataset.checked === '1') resetCheckedState();
        activeLeft = (activeLeft === b) ? null : b;
        render();
      });
    });

    rightBtns.forEach(function (b) {
      b.addEventListener('click', function () {
        if (checkBtn.dataset.checked === '1') resetCheckedState();
        if (!activeLeft) {
          tip.innerHTML = '<i class="ph-fill ph-arrow-left text-sun-ink"></i> Pick a card on the left first.';
          tip.classList.remove('text-muted');
          tip.classList.add('text-coral-ink');
          setTimeout(resetCheckedState, 1200);
          return;
        }
        const leftKey = activeLeft.getAttribute('data-key');
        const rightKey = b.getAttribute('data-key');
        // drop any existing pairing on either side
        removePairsTouching(leftKey, 'L');
        removePairsTouching(rightKey, 'R');
        pairs.set(leftKey, rightKey);
        activeLeft = null;
        render();
      });
    });

    resetBtn.addEventListener('click', function () {
      pairs.clear();
      activeLeft = null;
      resetCheckedState();
      render();
    });

    checkBtn.addEventListener('click', function () {
      if (pairs.size !== TOTAL) return;
      checkBtn.dataset.checked = '1';
      const correct = countCorrect();
      // paint cards
      function paint(el, good) {
        el.classList.add('ring-2');
        el.classList.remove(good ? 'ring-[#D2304A]' : 'ring-[#0E7A5A]');
        el.classList.add(good ? 'ring-[#0E7A5A]' : 'ring-[#D2304A]');
      }
      leftBtns.forEach(function (l) {
        const leftKey = l.getAttribute('data-key');
        const rightKey = pairs.get(leftKey);
        const isRight = rightKey === leftKey;
        paint(l, isRight);
        const r = rightBtns.find(function (x) { return x.getAttribute('data-key') === rightKey; });
        if (r) paint(r, isRight);
      });
      render();

      if (correct === TOTAL) {
        tip.innerHTML = '<i class="ph-fill ph-confetti text-mint-ink"></i> Perfect! All 3 pairs matched.';
        tip.classList.remove('text-muted', 'text-coral-ink');
        tip.classList.add('text-mint-ink');
        checkBtn.innerHTML = '<i class="ph-fill ph-arrow-right"></i> Next';
        checkBtn.disabled = false;
        checkBtn.classList.remove('opacity-50');
        checkBtn.onclick = function () { location.href = 'game-guess-animal.html'; };
      } else {
        tip.innerHTML = '<i class="ph-fill ph-warning-circle text-coral-ink"></i> ' + correct + ' / ' + TOTAL + ' correct. Tap a wrong card to redo.';
        tip.classList.remove('text-muted', 'text-mint-ink');
        tip.classList.add('text-coral-ink');
        checkBtn.innerHTML = '<i class="ph ph-arrow-counter-clockwise"></i> Try again';
        checkBtn.disabled = false;
        checkBtn.classList.remove('opacity-50');
        checkBtn.onclick = function () {
          // keep correct pairs, wipe wrong ones
          Array.from(pairs.keys()).forEach(function (leftKey) {
            if (pairs.get(leftKey) !== leftKey) pairs.delete(leftKey);
          });
          checkBtn.onclick = null;
          resetCheckedState();
          render();
        };
      }
    });

    render();
  })();
