// Page script for clear-cache.html — extracted from inline <script>.
// Loaded via <script src="./assets/js/clear-cache.js"></script>.

/* ═══════════════════════════════════════════════════════════════════
 *  Page script: clear-cache.html
 * 
 *  TABLE OF CONTENTS
 *  ─────────────────
 *    Initial hero counters ............... line   89
 *    Checkbox listeners .................. line   97
 *    Breakdown tappable .................. line  103
 *    Select all .......................... line  114
 *    Sheet helpers ....................... line  121
 *    CONFIRM SHEET ....................... line  142
 *    DONE SHEET .......................... line  186
 *    CONFIRM → CLEAR (live tick-down) .... line  208
 *    Esc close ........................... line  247
 * ═══════════════════════════════════════════════════════════════════ */
  (function () {
    const TOTAL = 24;  // MB
    const LIMIT = 100; // MB
    const sizes = { image: 6, audio: 3, offline: 14 }; // progress + 1MB
    const PROTECTED = 1; // MB (progress data)

    const checkboxes = Array.from(document.querySelectorAll('[data-clear]'));
    const totalVal = document.getElementById('totalVal');
    const totalFill = document.getElementById('totalFill');
    const totalPct = document.getElementById('totalPct');
    const heroFree = document.getElementById('heroFree');
    const heroKeep = document.getElementById('heroKeep');
    const heroItems = document.getElementById('heroItems');
    const ctaSize = document.getElementById('ctaSize');
    const clearBtn = document.getElementById('clearBtn');
    const selectAll = document.getElementById('selectAll');
    const breakdown = document.getElementById('breakdown');

    let cleared = false;
    let currentTotal = TOTAL;

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

    function paint(animated) {
      const selected = checkboxes.filter(function (c) { return c.checked; });
      const freed = selected.reduce(function (s, c) { return s + parseInt(c.getAttribute('data-mb'), 10); }, 0);
      const keep = currentTotal - freed;

      if (animated) {
        animate(heroFree, freed, 350);
        animate(heroKeep, keep, 350);
      } else {
        heroFree.textContent = freed;
        heroKeep.textContent = keep;
      }
      heroItems.textContent = selected.length;
      ctaSize.textContent = freed + ' MB';

      clearBtn.disabled = freed === 0 || cleared;
      clearBtn.classList.toggle('opacity-50', clearBtn.disabled);

      // Breakdown row opacity reflects selection
      document.querySelectorAll('[data-link]').forEach(function (row) {
        const key = row.getAttribute('data-link');
        const cb = checkboxes.find(function (c) { return c.getAttribute('data-key') === key; });
        const selectedRow = cb && cb.checked;
        row.classList.toggle('opacity-50', !selectedRow);
        // strike the size text when selected (visual "will remove")
        const sizeEl = row.querySelector('[data-size]');
        if (sizeEl) {
          sizeEl.classList.toggle('line-through', selectedRow);
          sizeEl.classList.toggle('text-coral-ink', selectedRow);
          sizeEl.classList.toggle('text-muted', !selectedRow);
        }
      });

      // Select-all link label
      selectAll.textContent = selected.length === checkboxes.length ? 'Clear all' : 'Select all';
    }

    // ---------- Initial hero counters ----------
    animate(totalVal, TOTAL, 900);
    animate(totalPct, Math.round((TOTAL / LIMIT) * 100), 900);
    setTimeout(function () {
      totalFill.classList.remove('w-0');
      totalFill.classList.add('w-25');
    }, 250);

    // ---------- Checkbox listeners ----------
    checkboxes.forEach(function (c) {
      c.addEventListener('change', function () { paint(true); });
    });
    paint(false);

    // ---------- Breakdown tappable ----------
    document.querySelectorAll('[data-link]').forEach(function (row) {
      row.addEventListener('click', function () {
        const key = row.getAttribute('data-link');
        const cb = checkboxes.find(function (c) { return c.getAttribute('data-key') === key; });
        if (!cb) return;
        cb.checked = !cb.checked;
        paint(true);
      });
    });

    // ---------- Select all ----------
    selectAll.addEventListener('click', function () {
      const allOn = checkboxes.every(function (c) { return c.checked; });
      checkboxes.forEach(function (c) { c.checked = !allOn; });
      paint(true);
    });

    // ---------- Sheet helpers ----------
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

    // ---------- CONFIRM SHEET ----------
    const confirmSheet = document.getElementById('confirmSheet');
    const confirmPanel = document.getElementById('confirmPanel');
    const confirmBackdrop = document.getElementById('confirmBackdrop');
    const confirmClose = document.getElementById('confirmClose');
    const confirmCancel = document.getElementById('confirmCancel');
    const confirmYes = document.getElementById('confirmYes');
    const confirmSize = document.getElementById('confirmSize');
    const confirmList = document.getElementById('confirmList');

    const META = {
      image:   { emoji: '🖼️', name: 'Image cache',     tile: 'tile-sun',    note: 'Re-downloads on next use' },
      audio:   { emoji: '🔊', name: 'Audio cache',     tile: 'tile-mint',   note: 'Sounds fetch again when played' },
      offline: { emoji: '📖', name: 'Offline lessons', tile: 'tile-violet', note: 'No offline access until reopened' }
    };

    clearBtn.addEventListener('click', function () {
      const selected = checkboxes.filter(function (c) { return c.checked; });
      if (!selected.length) return;
      const freed = selected.reduce(function (s, c) { return s + parseInt(c.getAttribute('data-mb'), 10); }, 0);
      confirmSize.textContent = freed + ' MB';
      confirmList.innerHTML = '';
      selected.forEach(function (c, i) {
        const k = c.getAttribute('data-key');
        const m = META[k] || {};
        const mb = c.getAttribute('data-mb');
        const row = document.createElement('div');
        row.className = 'setting-row opacity-0 translate-y-1 transition-all duration-300';
        row.innerHTML =
          '<div class="setting-ico ' + (m.tile || 'tile-violet') + ' text-lg">' + (m.emoji || '📦') + '</div>' +
          '<div class="grow min-w-0">' +
            '<p class="setting-text font-extrabold text-sm text-ink">' + (m.name || k) + '</p>' +
            '<p class="text-[11px] text-muted">' + (m.note || '') + '</p>' +
          '</div>' +
          '<span class="chip chip-coral">' + mb + ' MB</span>';
        confirmList.appendChild(row);
        setTimeout(function () { row.classList.remove('opacity-0', 'translate-y-1'); }, 80 + i * 90);
      });
      openSheet(confirmSheet, confirmPanel, confirmBackdrop);
    });
    confirmClose.addEventListener('click', function () { closeSheet(confirmSheet, confirmPanel, confirmBackdrop); });
    confirmBackdrop.addEventListener('click', function () { closeSheet(confirmSheet, confirmPanel, confirmBackdrop); });
    confirmCancel.addEventListener('click', function () { closeSheet(confirmSheet, confirmPanel, confirmBackdrop); });

    // ---------- DONE SHEET ----------
    const doneSheet = document.getElementById('doneSheet');
    const donePanel = document.getElementById('donePanel');
    const doneBackdrop = document.getElementById('doneBackdrop');
    const doneTile = document.getElementById('doneTile');
    const doneFreed = document.getElementById('doneFreed');
    const doneKept = document.getElementById('doneKept');
    const doneStay = document.getElementById('doneStay');

    function openDone(freed, kept) {
      doneFreed.textContent = freed + ' MB';
      doneKept.textContent = kept + ' MB';
      openSheet(doneSheet, donePanel, doneBackdrop);
      doneTile.animate([
        { transform: 'scale(0.7) rotate(-10deg)' },
        { transform: 'scale(1.1) rotate(5deg)' },
        { transform: 'scale(1) rotate(0)' }
      ], { duration: 500, easing: 'cubic-bezier(.34,1.56,.64,1)' });
    }
    doneStay.addEventListener('click', function () { closeSheet(doneSheet, donePanel, doneBackdrop); });
    doneBackdrop.addEventListener('click', function () { closeSheet(doneSheet, donePanel, doneBackdrop); });

    // ---------- CONFIRM → CLEAR (live tick-down) ----------
    confirmYes.addEventListener('click', function () {
      const selected = checkboxes.filter(function (c) { return c.checked; });
      if (!selected.length) return;
      const freed = selected.reduce(function (s, c) { return s + parseInt(c.getAttribute('data-mb'), 10); }, 0);
      const kept = currentTotal - freed;

      // Remove selected items' breakdown rows with a fade
      selected.forEach(function (c) {
        const key = c.getAttribute('data-key');
        const row = document.querySelector('[data-link="' + key + '"]');
        if (row) {
          row.classList.add('opacity-0', 'transition-all', 'duration-400', 'scale-95');
          setTimeout(function () { row.classList.add('hidden'); }, 400);
        }
        // disable and hide the switch row
        const swRow = c.closest('.setting-row');
        if (swRow) {
          setTimeout(function () {
            swRow.classList.add('opacity-0', 'transition-all', 'duration-400');
            setTimeout(function () { swRow.classList.add('hidden'); }, 400);
          }, 200);
        }
      });

      // Animate the hero total down
      animate(totalVal, kept, 900);
      animate(totalPct, Math.round((kept / LIMIT) * 100), 900);
      totalFill.classList.remove('w-25');
      totalFill.classList.add(kept <= 1 ? 'w-0' : kept <= 10 ? 'w-10' : kept <= 15 ? 'w-15' : 'w-20');

      cleared = true;
      currentTotal = kept;
      checkboxes.forEach(function (c) { c.checked = false; c.disabled = true; });
      paint(false);
      closeSheet(confirmSheet, confirmPanel, confirmBackdrop);
      setTimeout(function () { openDone(freed, kept); }, 400);
    });

    // ---------- Esc close ----------
    document.addEventListener('keydown', function (e) {
      if (e.key !== 'Escape') return;
      if (!confirmSheet.classList.contains('hidden')) closeSheet(confirmSheet, confirmPanel, confirmBackdrop);
      else if (!doneSheet.classList.contains('hidden')) closeSheet(doneSheet, donePanel, doneBackdrop);
    });
  })();
