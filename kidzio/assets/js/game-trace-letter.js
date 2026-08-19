// Page script for game-trace-letter.html — extracted from inline <script>.
// Loaded via <script src="./assets/js/game-trace-letter.js"></script>.

/* ═══════════════════════════════════════════════════════════════════
 *  Page script: game-trace-letter.html
 * 
 *  TABLE OF CONTENTS
 *  ─────────────────
 *    % of reference points within ACC_RADIUS of any … .... line   67
 *    unlock Next at 60%+ ................................. line   86
 *    sparsify — avoid tiny jitter ........................ line  113
 * ═══════════════════════════════════════════════════════════════════ */
  (function () {
    const svg = document.getElementById('traceSvg');
    const userPath = document.getElementById('userPath');
    const pencil = document.getElementById('pencil');
    const starsVal = document.getElementById('starsVal');
    const triesVal = document.getElementById('triesVal');
    const accVal = document.getElementById('accVal');
    const restartBtn = document.getElementById('restartBtn');
    const nextBtn = document.getElementById('nextBtn');
    const speakerBtn = document.getElementById('speakerBtn');

    // Reference points sampled along the outline of letter A (3 stroke segments)
    // seg1: (30,200)->(100,30)  seg2: (100,30)->(170,200)  seg3: (60,140)->(140,140)
    const refs = [];
    function sample(ax, ay, bx, by, n) {
      for (let i = 0; i <= n; i++) {
        const t = i / n;
        refs.push([ax + (bx - ax) * t, ay + (by - ay) * t]);
      }
    }
    sample(30, 200, 100, 30, 24);
    sample(100, 30, 170, 200, 24);
    sample(60, 140, 140, 140, 14);

    let drawing = false;
    let points = [];
    let tries = 0;
    let locked = false;
    const MIN_POINTS = 20;
    const ACC_RADIUS = 18; // svg units

    function svgPoint(evt) {
      const rect = svg.getBoundingClientRect();
      const x = ((evt.clientX - rect.left) / rect.width) * 200;
      const y = ((evt.clientY - rect.top) / rect.height) * 220;
      return [x, y];
    }

    function toPathD(pts) {
      if (!pts.length) return '';
      let d = 'M' + pts[0][0].toFixed(1) + ' ' + pts[0][1].toFixed(1);
      for (let i = 1; i < pts.length; i++) d += ' L' + pts[i][0].toFixed(1) + ' ' + pts[i][1].toFixed(1);
      return d;
    }

    function updatePencil(pt) {
      if (!pt) { pencil.setAttribute('x', -50); pencil.setAttribute('y', -50); return; }
      pencil.setAttribute('x', pt[0] - 8);
      pencil.setAttribute('y', pt[1] + 8);
    }

    function accuracy(pts) {
      if (pts.length < MIN_POINTS) return 0;
      // % of reference points within ACC_RADIUS of any drawn point
      let hits = 0;
      for (let i = 0; i < refs.length; i++) {
        const r = refs[i];
        let ok = false;
        for (let j = 0; j < pts.length; j++) {
          const dx = pts[j][0] - r[0];
          const dy = pts[j][1] - r[1];
          if (dx * dx + dy * dy <= ACC_RADIUS * ACC_RADIUS) { ok = true; break; }
        }
        if (ok) hits++;
      }
      return Math.round((hits / refs.length) * 100);
    }

    function paintStats(acc) {
      accVal.textContent = acc + '%';
      const stars = acc >= 90 ? '⭐⭐⭐' : acc >= 75 ? '⭐⭐☆' : acc >= 60 ? '⭐☆☆' : '☆☆☆';
      starsVal.textContent = stars;
      // unlock Next at 60%+
      const unlocked = acc >= 60;
      nextBtn.disabled = !unlocked;
      nextBtn.classList.toggle('opacity-50', !unlocked);
      if (unlocked && !locked) {
        locked = true;
        nextBtn.onclick = function () { location.href = 'game-match-word.html'; };
      }
    }

    function startStroke(e) {
      if (nextBtn.disabled === false && locked) return; // already passed, no more input
      drawing = true;
      if (!points.length) {
        tries++;
        triesVal.textContent = Math.min(tries, 3) + '/3';
      }
      const p = svgPoint(e);
      points.push(p);
      userPath.setAttribute('d', toPathD(points));
      updatePencil(p);
    }
    function moveStroke(e) {
      if (!drawing) return;
      e.preventDefault();
      const p = svgPoint(e);
      const last = points[points.length - 1];
      // sparsify — avoid tiny jitter
      if (!last || Math.hypot(p[0] - last[0], p[1] - last[1]) >= 2) {
        points.push(p);
        userPath.setAttribute('d', toPathD(points));
        updatePencil(p);
      }
    }
    function endStroke() {
      if (!drawing) return;
      drawing = false;
      paintStats(accuracy(points));
    }

    // pointer events (mouse + touch via pointer)
    svg.addEventListener('pointerdown', function (e) { svg.setPointerCapture(e.pointerId); startStroke(e); });
    svg.addEventListener('pointermove', moveStroke);
    svg.addEventListener('pointerup', endStroke);
    svg.addEventListener('pointercancel', endStroke);
    svg.addEventListener('pointerleave', endStroke);

    restartBtn.addEventListener('click', function () {
      points = [];
      userPath.setAttribute('d', '');
      updatePencil(null);
      locked = false;
      paintStats(0);
      if (tries >= 3) {
        tries = 0;
        triesVal.textContent = '0/3';
      }
    });

    speakerBtn.addEventListener('click', function () {
      if ('speechSynthesis' in window) {
        const u = new SpeechSynthesisUtterance('A. Uppercase A.');
        u.rate = 0.85;
        window.speechSynthesis.cancel();
        window.speechSynthesis.speak(u);
      } else {
        toast('Hear: A');
      }
    });

    paintStats(0);
  })();
