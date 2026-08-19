// Page script for game-body-parts.html — extracted from inline <script>.
// Loaded via <script src="./assets/js/game-body-parts.js"></script>.

  (function () {
    const cards = Array.from(document.querySelectorAll('.part-card'));
    const promptLabel = document.getElementById('promptLabel');
    const pulseRing = document.getElementById('pulseRing');
    const pulseDot = document.getElementById('pulseDot');
    const scoreChip = document.getElementById('scoreChip');
    const progressFill = document.getElementById('progressFill');
    const nextBtn = document.getElementById('nextBtn');

    const rounds = [
      { part: 'elbow', label: 'elbow' },
      { part: 'hand', label: 'hand' },
      { part: 'foot', label: 'foot' }
    ];
    let idx = 0;
    let score = 20;
    let done = false;
    let locked = false;

    function currentTarget() { return rounds[idx]; }
    function cardFor(part) { return cards.find(function (c) { return c.getAttribute('data-part') === part; }); }

    function movePulse() {
      const t = currentTarget();
      const card = cardFor(t.part);
      if (!card) return;
      pulseRing.setAttribute('cx', card.getAttribute('data-x'));
      pulseRing.setAttribute('cy', card.getAttribute('data-y'));
      pulseDot.setAttribute('cx', card.getAttribute('data-x'));
      pulseDot.setAttribute('cy', card.getAttribute('data-y'));
    }

    function clearRings() {
      cards.forEach(function (c) {
        c.classList.remove('ring-2', 'ring-[var(--color-k-primary)]', 'ring-[#0E7A5A]', 'ring-[#D2304A]');
      });
    }

    function paintPrompt() {
      const t = currentTarget();
      promptLabel.textContent = t.label;
      clearRings();
      const right = cardFor(t.part);
      if (right) right.classList.add('ring-2', 'ring-[var(--color-k-primary)]');
      movePulse();
    }

    function advance() {
      idx++;
      if (idx >= rounds.length) {
        done = true;
        progressFill.classList.remove('w-50');
        progressFill.classList.add('w-80');
        nextBtn.disabled = false;
        nextBtn.classList.remove('opacity-50');
        nextBtn.onclick = function () { location.href = 'game-knowledge.html'; };
        // freeze prompt on last correct part
        cards.forEach(function (c) { c.disabled = true; });
        promptLabel.textContent = 'all done!';
        promptLabel.classList.remove('text-primary-ink');
        promptLabel.classList.add('text-mint-ink');
        return;
      }
      paintPrompt();
    }

    cards.forEach(function (c) {
      c.addEventListener('click', function () {
        if (done || locked) return;
        const target = currentTarget();
        const ok = c.getAttribute('data-part') === target.part;
        if (ok) {
          locked = true;
          c.classList.remove('ring-[var(--color-k-primary)]');
          c.classList.add('ring-2', 'ring-[#0E7A5A]');
          score += 10;
          scoreChip.textContent = '⭐ ' + score;
          setTimeout(function () { locked = false; advance(); }, 700);
        } else {
          c.classList.remove('ring-[var(--color-k-primary)]');
          c.classList.add('ring-2', 'ring-[#D2304A]');
          score = Math.max(0, score - 5);
          scoreChip.textContent = '⭐ ' + score;
          setTimeout(function () {
            c.classList.remove('ring-2', 'ring-[#D2304A]');
            const right = cardFor(target.part);
            if (right) right.classList.add('ring-2', 'ring-[var(--color-k-primary)]');
          }, 700);
        }
      });
    });

    nextBtn.classList.add('opacity-50');
    paintPrompt();
  })();
