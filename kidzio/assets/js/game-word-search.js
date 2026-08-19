// Page script for game-word-search.html — extracted from inline <script>.
// Loaded via <script src="./assets/js/game-word-search.js"></script>.

/* ═══════════════════════════════════════════════════════════════════
 *  Page script: game-word-search.html
 * 
 *  TABLE OF CONTENTS
 *  ─────────────────
 *    Build grid with data-r / data-c ..................... line   48
 *    valid only if horizontal, vertical, or 45° diag… .... line   69
 *    Hint: reveal first cell of an unfound word .......... line  206
 * ═══════════════════════════════════════════════════════════════════ */
  (function () {
    const rows = [
      ['A','D','C','X','S','U','N','Q'],
      ['P','O','A','F','I','S','H','B'],
      ['P','G','T','M','N','K','L','P'],
      ['L','Q','R','E','X','Y','Z','D'],
      ['E','A','O','P','Q','C','A','T'],
      ['V','S','W','U','B','F','D','H'],
      ['C','D','O','G','T','U','R','K'],
      ['B','N','R','S','E','P','A','L']
    ];
    const words = {
      SUN:   [[0,4],[0,5],[0,6]],
      FISH:  [[1,3],[1,4],[1,5],[1,6]],
      CAT:   [[4,5],[4,6],[4,7]],
      DOG:   [[6,1],[6,2],[6,3]],
      APPLE: [[0,0],[1,0],[2,0],[3,0],[4,0]]
    };

    const grid = document.getElementById('grid');
    const progressChip = document.getElementById('progressChip');
    const chips = document.querySelectorAll('[data-word-chip]');
    const hintBtn = document.getElementById('hintBtn');
    const hintsVal = document.getElementById('hintsVal');
    const scoreVal = document.getElementById('scoreVal');
    const timeVal = document.getElementById('timeVal');
    const tip = document.getElementById('tip');
    const continueBtn = document.getElementById('continueBtn');

    const found = new Set();
    let hints = 3;
    let score = 0;
    let first = null;

    // Build grid with data-r / data-c
    let html = '';
    rows.forEach(function (r, ri) {
      r.forEach(function (c, ci) {
        html += '<button type="button" data-r="' + ri + '" data-c="' + ci + '" '
             + 'class="aspect-square rounded-lg grid place-items-center bg-[var(--color-k-bg)] text-ink cell">'
             + c + '</button>';
      });
    });
    grid.innerHTML = html;
    const cells = Array.from(grid.querySelectorAll('.cell'));

    function cellAt(r, c) {
      return cells.find(function (x) {
        return +x.getAttribute('data-r') === r && +x.getAttribute('data-c') === c;
      });
    }

    function keysFrom(a, b) {
      const dr = Math.sign(b[0] - a[0]);
      const dc = Math.sign(b[1] - a[1]);
      // valid only if horizontal, vertical, or 45° diagonal
      if (dr === 0 && dc === 0) return null;
      if (dr !== 0 && dc !== 0 && Math.abs(b[0] - a[0]) !== Math.abs(b[1] - a[1])) return null;
      if (dr === 0 && dc === 0) return null;
      const steps = Math.max(Math.abs(b[0] - a[0]), Math.abs(b[1] - a[1])) + 1;
      const out = [];
      for (let i = 0; i < steps; i++) out.push([a[0] + dr * i, a[1] + dc * i]);
      return out;
    }

    function lettersFor(keys) {
      return keys.map(function (k) { return rows[k[0]][k[1]]; }).join('');
    }

    function matchWord(letters) {
      if (words[letters]) return letters;
      const rev = letters.split('').reverse().join('');
      if (words[rev]) return rev;
      return null;
    }

    function sameKeys(a, b) {
      if (a.length !== b.length) return false;
      for (let i = 0; i < a.length; i++) if (a[i][0] !== b[i][0] || a[i][1] !== b[i][1]) return false;
      return true;
    }

    function markFound(word) {
      found.add(word);
      const path = words[word];
      path.forEach(function (p) {
        const cell = cellAt(p[0], p[1]);
        if (cell) {
          cell.classList.remove('bg-[var(--color-k-bg)]');
          cell.classList.add('bg-[rgba(78,214,168,.3)]', 'text-mint-ink', 'cell-found');
        }
      });
      const chip = document.querySelector('[data-word-chip="' + word + '"]');
      if (chip) {
        chip.classList.add('chip-mint', 'line-through');
      }
      score += word.length * 10;
      scoreVal.textContent = String(score);
      progressChip.textContent = found.size + ' / 5';

      if (found.size === 5) {
        clearInterval(timerId);
        tip.innerHTML = '<i class="ph-fill ph-confetti text-mint-ink"></i> All 5 words found! Great job.';
        tip.classList.remove('text-muted');
        tip.classList.add('text-mint-ink');
        continueBtn.disabled = false;
        continueBtn.classList.remove('opacity-50');
        continueBtn.onclick = function () { location.href = 'game-connect-pair.html'; };
      }
    }

    function clearSelection() {
      cells.forEach(function (c) { c.classList.remove('ring-2', 'ring-[var(--color-k-primary)]'); });
      first = null;
    }

    function highlightFirst(cell) {
      clearSelection();
      cell.classList.add('ring-2', 'ring-[var(--color-k-primary)]');
      first = cell;
    }

    function flashWrong(keys) {
      keys.forEach(function (k) {
        const c = cellAt(k[0], k[1]);
        if (c) c.classList.add('ring-2', 'ring-[#D2304A]');
      });
      setTimeout(function () {
        keys.forEach(function (k) {
          const c = cellAt(k[0], k[1]);
          if (c) c.classList.remove('ring-2', 'ring-[#D2304A]');
        });
      }, 400);
    }

    cells.forEach(function (cell) {
      cell.addEventListener('click', function () {
        if (!first) {
          highlightFirst(cell);
          tip.innerHTML = '<i class="ph-fill ph-cursor text-primary-ink"></i> Now tap the last letter.';
          tip.classList.remove('text-muted', 'text-mint-ink', 'text-coral-ink');
          tip.classList.add('text-primary-ink');
          return;
        }
        if (first === cell) { clearSelection(); return; }
        const a = [+first.getAttribute('data-r'), +first.getAttribute('data-c')];
        const b = [+cell.getAttribute('data-r'), +cell.getAttribute('data-c')];
        const keys = keysFrom(a, b);
        if (!keys) {
          flashWrong([a, b]);
          tip.innerHTML = '<i class="ph-fill ph-warning-circle text-coral-ink"></i> Pick a straight or diagonal line.';
          tip.classList.remove('text-muted', 'text-primary-ink');
          tip.classList.add('text-coral-ink');
          clearSelection();
          return;
        }
        const letters = lettersFor(keys);
        const matched = matchWord(letters);
        if (matched && !found.has(matched)) {
          // confirm this line matches the target word's exact keys (or reversed)
          const target = words[matched];
          const reversed = target.slice().reverse();
          if (sameKeys(keys, target) || sameKeys(keys, reversed)) {
            markFound(matched);
            clearSelection();
            if (found.size < 5) {
              tip.innerHTML = '<i class="ph-fill ph-sparkle text-mint-ink"></i> Nice! Keep going.';
              tip.classList.remove('text-muted', 'text-primary-ink', 'text-coral-ink');
              tip.classList.add('text-mint-ink');
            }
            return;
          }
        }
        flashWrong(keys);
        tip.innerHTML = '<i class="ph-fill ph-warning-circle text-coral-ink"></i> Not a match. Try again.';
        tip.classList.remove('text-muted', 'text-primary-ink', 'text-mint-ink');
        tip.classList.add('text-coral-ink');
        clearSelection();
      });
    });

    // Tap a chip to briefly pulse its cells (free preview, not a hint)
    chips.forEach(function (chip) {
      chip.addEventListener('click', function () {
        const w = chip.getAttribute('data-word-chip');
        if (found.has(w)) return;
        tip.innerHTML = '<i class="ph-fill ph-info text-primary-ink"></i> ' + w + ' has ' + w.length + ' letters.';
        tip.classList.remove('text-muted', 'text-mint-ink', 'text-coral-ink');
        tip.classList.add('text-primary-ink');
      });
    });

    // Hint: reveal first cell of an unfound word
    hintBtn.addEventListener('click', function () {
      if (hints <= 0) {
        tip.innerHTML = '<i class="ph-fill ph-warning-circle text-coral-ink"></i> No hints left.';
        tip.classList.remove('text-muted', 'text-mint-ink', 'text-primary-ink');
        tip.classList.add('text-coral-ink');
        return;
      }
      const remaining = Object.keys(words).filter(function (w) { return !found.has(w); });
      if (!remaining.length) return;
      const word = remaining[0];
      const start = words[word][0];
      const cell = cellAt(start[0], start[1]);
      if (!cell) return;
      cell.classList.add('ring-2', 'ring-[var(--color-k-primary)]');
      setTimeout(function () { cell.classList.remove('ring-2', 'ring-[var(--color-k-primary)]'); }, 1500);
      hints--;
      hintsVal.textContent = String(hints);
      score = Math.max(0, score - 5);
      scoreVal.textContent = String(score);
      tip.innerHTML = '<i class="ph-fill ph-lightbulb text-sun-ink"></i> Start of <b>' + word + '</b> (-5 pts).';
      tip.classList.remove('text-muted', 'text-mint-ink', 'text-coral-ink');
      tip.classList.add('text-sun-ink');
    });

    // Timer
    let seconds = 0;
    const timerId = setInterval(function () {
      seconds++;
      const mm = Math.floor(seconds / 60);
      const ss = seconds % 60;
      timeVal.textContent = mm + ':' + (ss < 10 ? '0' + ss : ss);
    }, 1000);
  })();
