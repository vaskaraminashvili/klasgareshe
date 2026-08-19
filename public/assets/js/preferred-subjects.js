// Page script for preferred-subjects.html — extracted from inline <script>.
// Loaded via <script src="./assets/js/preferred-subjects.js"></script>.

/* ═══════════════════════════════════════════════════════════════════
 *  Page script: preferred-subjects.html
 * 
 *  TABLE OF CONTENTS
 *  ─────────────────
 *    Tile interactions: short click picks, long-pres… .... line  197
 *    PREVIEW BOTTOM SHEET ................................ line  253
 *    SAVED SUCCESS SHEET ................................. line  349
 * ═══════════════════════════════════════════════════════════════════ */
  (function () {
    const tiles = Array.from(document.querySelectorAll('[data-subject]'));
    const selVal = document.getElementById('selVal');
    const lessonsVal = document.getElementById('lessonsVal');
    const minsVal = document.getElementById('minsVal');
    const heroFill = document.getElementById('heroFill');
    const heroHint = document.getElementById('heroHint');
    const pickCount = document.getElementById('pickCount');
    const saveBtn = document.getElementById('saveBtn');
    const saveCount = document.getElementById('saveCount');
    const planEmpty = document.getElementById('planEmpty');
    const planRows = document.getElementById('planRows');
    const planMeta = document.getElementById('planMeta');
    const diffPicker = document.getElementById('diffPicker');
    const resetBtn = document.getElementById('resetBtn');
    const quickChips = document.querySelectorAll('[data-quick]');

    const SUBJECT_META = {
      math:      { lessons: ['Counting to 20', 'Shape match', 'First addition', 'Compare numbers'], skills: ['Number sense', 'Sequencing', 'Logic'] },
      alphabet:  { lessons: ['Letters A–F', 'Sounds of M & S', 'Trace letter N', 'Capital vs small'], skills: ['Phonics', 'Handwriting', 'Reading'] },
      animals:   { lessons: ['Jungle animals', 'Farm friends', 'Ocean life', 'Baby & parent'], skills: ['Vocabulary', 'Habitats', 'Nature'] },
      words:     { lessons: ['Sight words 1', 'Spell short words', 'Rhyming', 'Opposites of words'], skills: ['Spelling', 'Reading', 'Memory'] },
      knowledge: { lessons: ['Space & planets', 'Weather', 'Community helpers', 'Seasons'], skills: ['Curiosity', 'Science', 'Observation'] },
      opposites: { lessons: ['Big & small', 'Hot & cold', 'Up & down', 'Fast & slow'], skills: ['Comparison', 'Vocabulary', 'Attention'] }
    };

    // Seed from parent-controls hint
    const initialPicks = ['math', 'alphabet', 'animals'];
    let picked = initialPicks.slice();
    let currentDiff = 'easy';

    function tileBy(key) { return tiles.find(function (t) { return t.getAttribute('data-subject') === key; }); }
    function isPicked(k) { return picked.indexOf(k) !== -1; }

    function paint(animateCount) {
      tiles.forEach(function (t) {
        const k = t.getAttribute('data-subject');
        const on = isPicked(k);
        const badge = t.querySelector('.check-badge');
        if (badge) badge.classList.toggle('hidden', !on);
        t.classList.toggle('ring-2', on);
        t.classList.toggle('ring-[var(--color-k-primary)]', on);
        t.classList.toggle('opacity-60', picked.length >= 3 && !on);
      });

      let lessons = 0; let mins = 0;
      picked.forEach(function (k) {
        const el = tileBy(k);
        if (!el) return;
        lessons += parseInt(el.getAttribute('data-lessons'), 10) || 0;
        mins += parseInt(el.getAttribute('data-mins'), 10) || 0;
      });

      if (animateCount) {
        animate(selVal, picked.length, 300);
        animate(lessonsVal, lessons, 500);
        animate(minsVal, mins, 500);
      } else {
        selVal.textContent = picked.length;
        lessonsVal.textContent = lessons;
        minsVal.textContent = mins;
      }

      // Hero fill 0/1/2/3 → 0/33/66/100
      const fillMap = ['w-0', 'w-33', 'w-66', 'w-100'];
      heroFill.classList.remove('w-0', 'w-33', 'w-66', 'w-100');
      heroFill.classList.add(fillMap[Math.min(picked.length, 3)]);
      heroHint.textContent = picked.length === 0
        ? 'Pick up to 3 · you\u2019re at 0 of 3'
        : picked.length === 3 ? 'All 3 picked — ready to save'
        : (3 - picked.length) + ' more to pick a full plan';

      pickCount.textContent = picked.length === 0 ? 'Pick up to 3' : picked.length + ' / 3 picked';
      saveBtn.disabled = picked.length === 0;
      saveBtn.classList.toggle('opacity-50', picked.length === 0);
      saveCount.textContent = picked.length;

      if (picked.length === 0) {
        planEmpty.classList.remove('hidden');
        planRows.classList.add('hidden');
        planMeta.textContent = 'Tap arrows to reorder';
        planRows.innerHTML = '';
      } else {
        planEmpty.classList.add('hidden');
        planRows.classList.remove('hidden');
        planMeta.textContent = 'Tap ▲▼ to reorder priority';
        planRows.innerHTML = '';
        picked.forEach(function (k, i) {
          const el = tileBy(k);
          if (!el) return;
          const row = document.createElement('div');
          row.className = 'setting-row opacity-0 translate-y-1 transition-all duration-300';
          const name = el.getAttribute('data-name');
          const emoji = el.getAttribute('data-emoji');
          const m = el.getAttribute('data-mins');
          const l = el.getAttribute('data-lessons');
          const tileClass = Array.from(el.classList).find(function (c) { return c.startsWith('tile-'); });
          const canUp = i > 0;
          const canDown = i < picked.length - 1;
          row.innerHTML =
            '<div class="setting-ico ' + tileClass + ' text-xl">' + emoji + '</div>' +
            '<div class="grow min-w-0">' +
              '<div class="flex items-center gap-2">' +
                '<p class="setting-text font-extrabold text-sm text-ink">' + (i + 1) + ' · ' + name + '</p>' +
                '<span class="chip chip-primary">Priority ' + (i + 1) + '</span>' +
              '</div>' +
              '<p class="text-[11px] text-muted">' + l + ' lessons · ~' + m + ' min / day</p>' +
            '</div>' +
            '<div class="flex flex-col gap-1 shrink-0">' +
              '<button type="button" class="w-7 h-7 rounded-lg bg-[var(--color-k-bg)] text-ink grid place-items-center ' + (canUp ? '' : 'opacity-30 pointer-events-none') + '" data-move="up" data-key="' + k + '" aria-label="Move up"><i class="ph ph-caret-up"></i></button>' +
              '<button type="button" class="w-7 h-7 rounded-lg bg-[var(--color-k-bg)] text-ink grid place-items-center ' + (canDown ? '' : 'opacity-30 pointer-events-none') + '" data-move="down" data-key="' + k + '" aria-label="Move down"><i class="ph ph-caret-down"></i></button>' +
            '</div>' +
            '<button type="button" class="icon-btn shrink-0 ml-1" data-remove="' + k + '" aria-label="Remove">' +
              '<i class="ph ph-x text-muted"></i>' +
            '</button>';
          planRows.appendChild(row);
          setTimeout(function () { row.classList.remove('opacity-0', 'translate-y-1'); }, i * 90);
        });

        // wire the per-row controls
        planRows.querySelectorAll('[data-move]').forEach(function (b) {
          b.addEventListener('click', function () {
            const key = b.getAttribute('data-key');
            const dir = b.getAttribute('data-move');
            const idx = picked.indexOf(key);
            if (idx < 0) return;
            const j = dir === 'up' ? idx - 1 : idx + 1;
            if (j < 0 || j >= picked.length) return;
            const tmp = picked[j]; picked[j] = picked[idx]; picked[idx] = tmp;
            paint(false);
          });
        });
        planRows.querySelectorAll('[data-remove]').forEach(function (b) {
          b.addEventListener('click', function () {
            const key = b.getAttribute('data-remove');
            picked = picked.filter(function (k) { return k !== key; });
            paint(true);
          });
        });
      }
    }

    function animate(el, target, duration) {
      const start = performance.now();
      const from = parseInt((el.textContent || '').toString().replace(/\D/g, ''), 10) || 0;
      (function step(now) {
        const t = Math.min(1, (now - start) / duration);
        const ease = 1 - Math.pow(1 - t, 3);
        el.textContent = Math.round(from + (target - from) * ease);
        if (t < 1) requestAnimationFrame(step);
      })(start);
    }

    function pop(el) {
      el.animate([
        { transform: 'scale(1)' }, { transform: 'scale(1.06)' }, { transform: 'scale(1)' }
      ], { duration: 260, easing: 'ease-out' });
    }
    function shake(el) {
      el.animate([
        { transform: 'translateX(0)' }, { transform: 'translateX(-6px)' },
        { transform: 'translateX(6px)' }, { transform: 'translateX(-4px)' },
        { transform: 'translateX(4px)' }, { transform: 'translateX(0)' }
      ], { duration: 320, easing: 'ease-out' });
    }

    function togglePick(k, sourceEl) {
      if (isPicked(k)) {
        picked = picked.filter(function (x) { return x !== k; });
        paint(true);
        return false;
      }
      if (picked.length >= 3) {
        if (sourceEl) shake(sourceEl);
        toast('Max 3 subjects — remove one first');
        return false;
      }
      picked.push(k);
      if (sourceEl) pop(sourceEl);
      paint(true);
      return true;
    }

    // ---------- Tile interactions: short click picks, long-press / dblclick previews ----------
    tiles.forEach(function (t) {
      let pressTimer = null;
      let longPressed = false;

      t.addEventListener('pointerdown', function () {
        longPressed = false;
        pressTimer = setTimeout(function () {
          longPressed = true;
          openPreview(t.getAttribute('data-subject'));
        }, 450);
      });
      t.addEventListener('pointerup', function () { clearTimeout(pressTimer); });
      t.addEventListener('pointerleave', function () { clearTimeout(pressTimer); });
      t.addEventListener('pointercancel', function () { clearTimeout(pressTimer); });

      t.addEventListener('click', function () {
        if (longPressed) { longPressed = false; return; }
        togglePick(t.getAttribute('data-subject'), t);
      });
    });

    // Difficulty picker
    diffPicker.querySelectorAll('[data-diff]').forEach(function (b) {
      b.addEventListener('click', function () {
        diffPicker.querySelectorAll('[data-diff]').forEach(function (o) { o.classList.remove('is-selected'); });
        b.classList.add('is-selected');
        currentDiff = b.getAttribute('data-diff');
      });
    });

    // Quick bundles
    const bundles = {
      top:      ['math', 'alphabet', 'animals'],
      read:     ['alphabet', 'words', 'opposites'],
      stem:     ['math', 'knowledge', 'animals'],
      balanced: ['math', 'words', 'knowledge']
    };
    quickChips.forEach(function (c) {
      c.addEventListener('click', function () {
        const keys = bundles[c.getAttribute('data-quick')];
        if (!keys) return;
        picked = keys.slice();
        quickChips.forEach(function (o) { o.classList.remove('chip-primary'); });
        c.classList.add('chip-primary');
        paint(true);
      });
    });

    resetBtn.addEventListener('click', function () {
      picked = [];
      quickChips.forEach(function (o) { o.classList.remove('chip-primary'); });
      paint(true);
      toast('Cleared');
    });

    // ---------- PREVIEW BOTTOM SHEET ----------
    const previewSheet = document.getElementById('previewSheet');
    const previewPanel = document.getElementById('previewPanel');
    const previewBackdrop = document.getElementById('previewBackdrop');
    const previewClose = document.getElementById('previewClose');
    const previewToggle = document.getElementById('previewToggle');
    const previewTile = document.getElementById('previewTile');
    const previewTitle = document.getElementById('previewTitle');
    const previewSub = document.getElementById('previewSub');
    const previewFit = document.getElementById('previewFit');
    const previewLessons = document.getElementById('previewLessons');
    const previewSkills = document.getElementById('previewSkills');
    let previewKey = null;

    function fitLabel(v) {
      return v === 'best' ? { label: 'Best for age 6', cls: 'chip-mint' }
           : v === 'good' ? { label: 'Good fit', cls: 'chip-primary' }
           : { label: 'A stretch', cls: 'chip-sun' };
    }

    function openPreview(key) {
      previewKey = key;
      const el = tileBy(key);
      if (!el) return;
      const tileCls = Array.from(el.classList).find(function (c) { return c.startsWith('tile-'); });
      previewTile.className = 'w-14 h-14 rounded-2xl ' + tileCls + ' grid place-items-center text-3xl shrink-0';
      previewTile.textContent = el.getAttribute('data-emoji');
      previewTitle.textContent = el.getAttribute('data-name');
      previewSub.textContent = el.getAttribute('data-lessons') + ' lessons · ~' + el.getAttribute('data-mins') + ' min / day';

      const fit = fitLabel(el.getAttribute('data-fit'));
      previewFit.className = 'chip ' + fit.cls + ' shrink-0';
      previewFit.textContent = fit.label;

      const meta = SUBJECT_META[key] || { lessons: [], skills: [] };
      previewLessons.innerHTML = '';
      meta.lessons.forEach(function (name, i) {
        const row = document.createElement('div');
        row.className = 'setting-row opacity-0 translate-y-1 transition-all duration-300';
        row.innerHTML =
          '<div class="setting-ico ' + tileCls + ' text-base"><i class="ph-fill ph-book-open"></i></div>' +
          '<div class="grow min-w-0">' +
            '<p class="setting-text font-extrabold text-sm text-ink">' + name + '</p>' +
            '<p class="text-[11px] text-muted">~5 min · beginner</p>' +
          '</div>' +
          '<span class="chip chip-mint">+20 XP</span>';
        previewLessons.appendChild(row);
        setTimeout(function () { row.classList.remove('opacity-0', 'translate-y-1'); }, 80 + i * 70);
      });

      previewSkills.innerHTML = '';
      meta.skills.forEach(function (s) {
        const span = document.createElement('span');
        span.className = 'chip chip-primary';
        span.textContent = s;
        previewSkills.appendChild(span);
      });

      // toggle button label
      syncPreviewToggle();

      previewSheet.classList.remove('hidden');
      document.body.style.overflow = 'hidden';
      requestAnimationFrame(function () {
        previewBackdrop.classList.remove('opacity-0');
        previewBackdrop.classList.add('opacity-100');
        previewPanel.classList.remove('translate-y-full');
      });
    }
    function closePreview() {
      previewBackdrop.classList.remove('opacity-100');
      previewBackdrop.classList.add('opacity-0');
      previewPanel.classList.add('translate-y-full');
      setTimeout(function () {
        previewSheet.classList.add('hidden');
        document.body.style.overflow = '';
      }, 280);
    }
    function syncPreviewToggle() {
      if (!previewKey) return;
      const on = isPicked(previewKey);
      previewToggle.innerHTML = on
        ? '<i class="ph-fill ph-minus-circle"></i> Remove from plan'
        : '<i class="ph-fill ph-plus-circle"></i> Add to plan';
      previewToggle.classList.toggle('btn-primary', !on);
      previewToggle.classList.toggle('btn-danger', on);
    }
    previewClose.addEventListener('click', closePreview);
    previewBackdrop.addEventListener('click', closePreview);
    previewToggle.addEventListener('click', function () {
      if (!previewKey) return;
      const el = tileBy(previewKey);
      togglePick(previewKey, el);
      syncPreviewToggle();
    });

    // ---------- SAVED SUCCESS SHEET ----------
    const savedSheet = document.getElementById('savedSheet');
    const savedPanel = document.getElementById('savedPanel');
    const savedBackdrop = document.getElementById('savedBackdrop');
    const savedStay = document.getElementById('savedStay');
    const savedRows = document.getElementById('savedRows');
    const savedTitle = document.getElementById('savedTitle');
    const savedSub = document.getElementById('savedSub');

    function openSaved() {
      savedTitle.textContent = 'Plan saved!';
      savedSub.textContent = 'Luna will see ' + picked.length + ' favourite' + (picked.length > 1 ? 's' : '') + ' every day.';
      savedRows.innerHTML = '';
      picked.forEach(function (k, i) {
        const el = tileBy(k);
        if (!el) return;
        const tileCls = Array.from(el.classList).find(function (c) { return c.startsWith('tile-'); });
        const row = document.createElement('div');
        row.className = 'setting-row opacity-0 translate-y-1 transition-all duration-300';
        row.innerHTML =
          '<div class="setting-ico ' + tileCls + ' text-xl">' + el.getAttribute('data-emoji') + '</div>' +
          '<div class="grow min-w-0">' +
            '<p class="setting-text font-extrabold text-sm text-ink">' + (i + 1) + ' · ' + el.getAttribute('data-name') + '</p>' +
            '<p class="text-[11px] text-muted">~' + el.getAttribute('data-mins') + ' min / day</p>' +
          '</div>' +
          '<span class="chip chip-primary">Priority ' + (i + 1) + '</span>';
        savedRows.appendChild(row);
        setTimeout(function () { row.classList.remove('opacity-0', 'translate-y-1'); }, 120 + i * 110);
      });

      savedSheet.classList.remove('hidden');
      document.body.style.overflow = 'hidden';
      requestAnimationFrame(function () {
        savedBackdrop.classList.remove('opacity-0');
        savedBackdrop.classList.add('opacity-100');
        savedPanel.classList.remove('translate-y-full');
      });
    }
    function closeSaved() {
      savedBackdrop.classList.remove('opacity-100');
      savedBackdrop.classList.add('opacity-0');
      savedPanel.classList.add('translate-y-full');
      setTimeout(function () {
        savedSheet.classList.add('hidden');
        document.body.style.overflow = '';
      }, 280);
    }
    savedBackdrop.addEventListener('click', closeSaved);
    savedStay.addEventListener('click', closeSaved);

    saveBtn.addEventListener('click', function () {
      if (picked.length === 0) return;
      openSaved();
    });

    // Esc closes any open sheet
    document.addEventListener('keydown', function (e) {
      if (e.key !== 'Escape') return;
      if (!previewSheet.classList.contains('hidden')) closePreview();
      else if (!savedSheet.classList.contains('hidden')) closeSaved();
    });

    // initial paint with animation
    paint(true);
  })();
