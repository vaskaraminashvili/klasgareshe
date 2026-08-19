// Page script for monthly-goals.html — extracted from inline <script>.
// Loaded via <script src="./assets/js/monthly-goals.js"></script>.

/* ═══════════════════════════════════════════════════════════════════
 *  Page script: monthly-goals.html
 * 
 *  TABLE OF CONTENTS
 *  ─────────────────
 *    Hero counters ........... line   19
 *    Charts .................. line   35
 *    Week rail chips ......... line   41
 *    Bottom sheet helpers .... line   60
 *    GOAL DETAIL SHEET ....... line   81
 *    MONTH PICKER SHEET ...... line  180
 *    ADD GOAL SHEET .......... line  205
 * ═══════════════════════════════════════════════════════════════════ */
  (function () {
    // ---------- Hero counters ----------
    function animate(el, target, duration) {
      const start = performance.now();
      const from = parseInt((el.textContent || '').toString().replace(/\D/g, ''), 10) || 0;
      (function step(now) {
        const t = Math.min(1, (now - start) / duration);
        const ease = 1 - Math.pow(1 - t, 3);
        el.textContent = Math.round(from + (target - from) * ease).toLocaleString();
        if (t < 1) requestAnimationFrame(step);
      })(start);
    }
    animate(document.getElementById('monthPct'), 62, 1000);
    animate(document.getElementById('xpVal'), 930, 900);
    animate(document.getElementById('doneVal'), 2, 700);
    animate(document.getElementById('rateVal'), 55, 900);

    // ---------- Charts ----------
    KCharts.donut(document.getElementById('donut'), 62, { label: 'April' });
    KCharts.bars(document.getElementById('bars'), [
      { label: 'W1', value: 220 }, { label: 'W2', value: 340 }, { label: 'W3', value: 290 }, { label: 'W4', value: 180 }
    ]);

    // ---------- Week rail chips ----------
    const weekData = {
      W1: { xp: 220, days: 6, lessons: 7 },
      W2: { xp: 340, days: 7, lessons: 11 },
      W3: { xp: 290, days: 5, lessons: 9 },
      W4: { xp: 180, days: 4, lessons: 6 }
    };
    const weekChips = document.querySelectorAll('[data-week]');
    const weeklyTotal = document.getElementById('weeklyTotal');
    weekChips.forEach(function (c) {
      c.addEventListener('click', function () {
        weekChips.forEach(function (o) { o.classList.remove('chip-primary'); });
        c.classList.add('chip-primary');
        const d = weekData[c.getAttribute('data-week')];
        toast(c.textContent + ' · ' + d.xp + ' XP · ' + d.days + ' days · ' + d.lessons + ' lessons');
        weeklyTotal.textContent = d.xp + ' XP';
      });
    });

    // ---------- Bottom sheet helpers ----------
    function openSheet(overlay, panel, backdrop, onOpen) {
      overlay.classList.remove('hidden');
      document.body.style.overflow = 'hidden';
      requestAnimationFrame(function () {
        backdrop.classList.remove('opacity-0');
        backdrop.classList.add('opacity-100');
        panel.classList.remove('translate-y-full');
      });
      if (onOpen) onOpen();
    }
    function closeSheet(overlay, panel, backdrop) {
      backdrop.classList.remove('opacity-100');
      backdrop.classList.add('opacity-0');
      panel.classList.add('translate-y-full');
      setTimeout(function () {
        overlay.classList.add('hidden');
        document.body.style.overflow = '';
      }, 280);
    }

    // ---------- GOAL DETAIL SHEET ----------
    const goalSheet = document.getElementById('goalSheet');
    const goalPanel = document.getElementById('goalPanel');
    const goalBackdrop = document.getElementById('goalBackdrop');
    const goalSheetClose = document.getElementById('goalSheetClose');
    const goalBtns = document.querySelectorAll('[data-goal]');
    const goalTile = document.getElementById('goalTile');
    const goalTitle = document.getElementById('goalSheetTitle');
    const goalSub = document.getElementById('goalSheetSub');
    const goalRing = document.getElementById('goalRing');
    const targetVal = document.getElementById('targetVal');
    const targetUnit = document.getElementById('targetUnit');
    const targetMinus = document.getElementById('targetMinus');
    const targetPlus = document.getElementById('targetPlus');
    const goalReset = document.getElementById('goalReset');
    const goalSave = document.getElementById('goalSave');
    const goalPace = document.getElementById('goalPace');
    const goalProj = document.getElementById('goalProj');

    let activeGoal = null;
    let originalTarget = 0;

    function recomputeGoal() {
      const now = activeGoal.now;
      const target = parseInt(targetVal.textContent, 10);
      const pct = target > 0 ? Math.round((now / target) * 100) : 0;
      KCharts.donut(goalRing, Math.min(pct, 100), { label: pct + '%' });
      goalSub.textContent = now + ' / ' + target + ' ' + activeGoal.unit;
      // Projected based on remaining time (13 of 30 days left → 17 days elapsed)
      const elapsed = 17;
      const remaining = 13;
      const rate = elapsed > 0 ? now / elapsed : 0;
      const projected = Math.round(now + rate * remaining);
      const ahead = projected >= target;
      goalPace.textContent = ahead ? 'Ahead of pace' : 'Behind pace';
      goalPace.classList.toggle('text-mint-ink', ahead);
      goalPace.classList.toggle('text-coral-ink', !ahead);
      goalPace.classList.toggle('text-ink', false);
      goalProj.textContent = projected.toLocaleString() + ' ' + activeGoal.unit;
    }

    goalBtns.forEach(function (b) {
      b.addEventListener('click', function () {
        activeGoal = {
          el: b,
          name: b.getAttribute('data-name'),
          now: parseInt(b.getAttribute('data-now'), 10),
          target: parseInt(b.getAttribute('data-target'), 10),
          unit: b.getAttribute('data-unit'),
          tile: b.getAttribute('data-tile'),
          progress: b.getAttribute('data-progress'),
          emoji: b.getAttribute('data-emoji')
        };
        originalTarget = activeGoal.target;
        goalTitle.textContent = activeGoal.name;
        goalTile.className = 'w-14 h-14 rounded-2xl ' + activeGoal.tile + ' grid place-items-center text-3xl shrink-0';
        goalTile.textContent = activeGoal.emoji;
        targetVal.textContent = activeGoal.target;
        targetUnit.textContent = activeGoal.unit;
        recomputeGoal();
        openSheet(goalSheet, goalPanel, goalBackdrop);
      });
    });

    targetMinus.addEventListener('click', function () {
      const v = parseInt(targetVal.textContent, 10);
      const step = activeGoal && activeGoal.unit === 'XP' ? 100 : 1;
      if (v - step < 1) return;
      targetVal.textContent = v - step;
      recomputeGoal();
    });
    targetPlus.addEventListener('click', function () {
      const v = parseInt(targetVal.textContent, 10);
      const step = activeGoal && activeGoal.unit === 'XP' ? 100 : 1;
      targetVal.textContent = v + step;
      recomputeGoal();
    });
    goalReset.addEventListener('click', function () {
      targetVal.textContent = originalTarget;
      recomputeGoal();
      toast('Target reset to ' + originalTarget);
    });
    goalSave.addEventListener('click', function () {
      const newTarget = parseInt(targetVal.textContent, 10);
      if (activeGoal && activeGoal.el) {
        activeGoal.el.setAttribute('data-target', newTarget);
        activeGoal.el.querySelector('p.text-\\[11px\\]').textContent = activeGoal.now + ' / ' + newTarget + ' · updated';
        activeGoal.el.querySelector('.setting-text, .font-extrabold')?.textContent;
        // update chip %
        const chip = activeGoal.el.querySelectorAll('.chip')[0];
        const pct = Math.min(100, Math.round((activeGoal.now / newTarget) * 100));
        if (chip) chip.textContent = pct + '%';
      }
      toast('Goal saved ✓');
      closeSheet(goalSheet, goalPanel, goalBackdrop);
    });
    goalSheetClose.addEventListener('click', function () { closeSheet(goalSheet, goalPanel, goalBackdrop); });
    goalBackdrop.addEventListener('click', function () { closeSheet(goalSheet, goalPanel, goalBackdrop); });

    // ---------- MONTH PICKER SHEET ----------
    const monthSheet = document.getElementById('monthSheet');
    const monthPanel = document.getElementById('monthPanel');
    const monthBackdrop = document.getElementById('monthBackdrop');
    const monthSheetClose = document.getElementById('monthSheetClose');
    const monthBtn = document.getElementById('monthBtn');
    const monthLabel = document.getElementById('monthLabel');
    const monthRows = document.querySelectorAll('#monthRows [data-month]');

    monthBtn.addEventListener('click', function () {
      openSheet(monthSheet, monthPanel, monthBackdrop);
    });
    monthSheetClose.addEventListener('click', function () { closeSheet(monthSheet, monthPanel, monthBackdrop); });
    monthBackdrop.addEventListener('click', function () { closeSheet(monthSheet, monthPanel, monthBackdrop); });

    monthRows.forEach(function (r) {
      r.addEventListener('click', function () {
        const m = r.getAttribute('data-month');
        const pct = parseInt(r.getAttribute('data-pct'), 10);
        monthLabel.textContent = m;
        toast('Switched to ' + m + ' · ' + pct + '%');
        closeSheet(monthSheet, monthPanel, monthBackdrop);
      });
    });

    // ---------- ADD GOAL SHEET ----------
    const addSheet = document.getElementById('addSheet');
    const addPanel = document.getElementById('addPanel');
    const addBackdrop = document.getElementById('addBackdrop');
    const addSheetClose = document.getElementById('addSheetClose');
    const addGoalBtn = document.getElementById('addGoalBtn');
    const addCustomBtn = document.getElementById('addCustomBtn');
    const presetBtns = document.querySelectorAll('[data-preset]');

    addGoalBtn.addEventListener('click', function () {
      openSheet(addSheet, addPanel, addBackdrop);
    });
    addSheetClose.addEventListener('click', function () { closeSheet(addSheet, addPanel, addBackdrop); });
    addBackdrop.addEventListener('click', function () { closeSheet(addSheet, addPanel, addBackdrop); });
    addCustomBtn.addEventListener('click', function () {
      toast('Custom goal builder coming soon');
      closeSheet(addSheet, addPanel, addBackdrop);
    });
    presetBtns.forEach(function (p) {
      p.addEventListener('click', function () {
        toast('Added goal: ' + p.getAttribute('data-preset'));
        closeSheet(addSheet, addPanel, addBackdrop);
      });
    });

    // Esc closes whatever sheet is open
    document.addEventListener('keydown', function (e) {
      if (e.key !== 'Escape') return;
      if (!goalSheet.classList.contains('hidden')) closeSheet(goalSheet, goalPanel, goalBackdrop);
      else if (!monthSheet.classList.contains('hidden')) closeSheet(monthSheet, monthPanel, monthBackdrop);
      else if (!addSheet.classList.contains('hidden')) closeSheet(addSheet, addPanel, addBackdrop);
    });
  })();
