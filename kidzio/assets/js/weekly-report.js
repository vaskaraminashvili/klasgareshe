// Page script for weekly-report.html — extracted from inline <script>.
// Loaded via <script src="./assets/js/weekly-report.js"></script>.

/* ═══════════════════════════════════════════════════════════════════
 *  Page script: weekly-report.html
 * 
 *  TABLE OF CONTENTS
 *  ─────────────────
 *    Hero counters + fill ..... line   25
 *    Line chart ............... line   51
 *    Row cascade .............. line   57
 *    Bottom sheet helpers ..... line   63
 *    WEEK PICKER SHEET ........ line   84
 *    DAY DETAIL SHEET ......... line  104
 *    HIGHLIGHT SHEET .......... line  158
 *    Subject rows navigate .... line  186
 *    SHARE SHEET .............. line  194
 *    EMAIL EDIT SHEET ......... line  221
 *    Download PDF ............. line  264
 *    Toggles .................. line  270
 *    Esc close ................ line  277
 * ═══════════════════════════════════════════════════════════════════ */
  (function () {
    // ---------- Hero counters + fill ----------
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
    animate(document.getElementById('xpVal'), 940, 1000);
    animate(document.getElementById('lessonsVal'), 12, 800);
    animate(document.getElementById('activeVal'), 6, 700);
    animate(document.getElementById('goalPct'), 117, 1000);
    // weekly goal fill: 940 of 800 = 117%, cap at 100 for bar
    const fill = document.getElementById('weekFill');
    setTimeout(function () {
      fill.classList.remove('w-0');
      fill.classList.add('w-100');
    }, 250);
    setTimeout(function () {
      const el = document.getElementById('goalPct');
      el.textContent = '117%';
    }, 1100);

    // ---------- Line chart ----------
    KCharts.line(document.getElementById('line'), [
      { label: 'Mon', value: 120 }, { label: 'Tue', value: 200 }, { label: 'Wed', value: 170 },
      { label: 'Thu', value: 240 }, { label: 'Fri', value: 360 }, { label: 'Sat', value: 210 }, { label: 'Sun', value: 80 }
    ]);

    // ---------- Row cascade ----------
    document.querySelectorAll('[data-highlight], [data-subj]').forEach(function (row, i) {
      row.classList.add('opacity-0', 'translate-y-1', 'transition-all', 'duration-300');
      setTimeout(function () { row.classList.remove('opacity-0', 'translate-y-1'); }, 250 + i * 80);
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

    // ---------- WEEK PICKER SHEET ----------
    const weekSheet = document.getElementById('weekSheet');
    const weekPanel = document.getElementById('weekPanel');
    const weekBackdrop = document.getElementById('weekBackdrop');
    const weekSheetClose = document.getElementById('weekSheetClose');
    const weekBtn = document.getElementById('weekBtn');
    const weekLabel = document.getElementById('weekLabel');
    const weekRange = document.getElementById('weekRange');
    weekBtn.addEventListener('click', function () { openSheet(weekSheet, weekPanel, weekBackdrop); });
    weekSheetClose.addEventListener('click', function () { closeSheet(weekSheet, weekPanel, weekBackdrop); });
    weekBackdrop.addEventListener('click', function () { closeSheet(weekSheet, weekPanel, weekBackdrop); });
    document.querySelectorAll('[data-week-opt]').forEach(function (r) {
      r.addEventListener('click', function () {
        weekLabel.textContent = r.getAttribute('data-week-opt');
        weekRange.textContent = r.getAttribute('data-range') + ' · parent@example.com';
        toast('Switched to ' + r.getAttribute('data-week-opt'));
        closeSheet(weekSheet, weekPanel, weekBackdrop);
      });
    });

    // ---------- DAY DETAIL SHEET ----------
    const daySheet = document.getElementById('daySheet');
    const dayPanel = document.getElementById('dayPanel');
    const dayBackdrop = document.getElementById('dayBackdrop');
    const daySheetClose = document.getElementById('daySheetClose');
    const dayTitle = document.getElementById('daySheetTitle');
    const daySub = document.getElementById('daySheetSub');
    const dayXp = document.getElementById('dayXp');
    const dayMins = document.getElementById('dayMins');
    const dayLessons = document.getElementById('dayLessons');
    const dayActivities = document.getElementById('dayActivities');
    const dayChips = document.querySelectorAll('[data-day]');

    dayChips.forEach(function (c) {
      c.addEventListener('click', function () {
        dayChips.forEach(function (o) { o.classList.remove('chip-primary'); });
        c.classList.add('chip-primary');
        const xp = parseInt(c.getAttribute('data-xp'), 10);
        const name = c.getAttribute('data-day');
        const top = c.getAttribute('data-top');
        const mins = Math.round(xp / 8);
        const lessons = Math.max(1, Math.round(xp / 60));
        dayTitle.textContent = name;
        daySub.textContent = '+' + xp + ' XP · top subject ' + top;
        dayXp.textContent = '0';
        dayMins.textContent = '0';
        dayLessons.textContent = '0';
        animate(dayXp, xp, 600);
        animate(dayMins, mins, 600);
        animate(dayLessons, lessons, 600);

        dayActivities.innerHTML = '';
        const list = [
          { n: top + ' lesson', m: Math.round(mins * 0.6), x: Math.round(xp * 0.55), t: 'tile-violet', e: '📚' },
          { n: 'Quick quiz', m: Math.round(mins * 0.25), x: Math.round(xp * 0.3), t: 'tile-mint', e: '❓' },
          { n: 'Badge check', m: Math.max(1, mins - Math.round(mins * 0.85)), x: Math.round(xp * 0.15), t: 'tile-sun', e: '🏅' }
        ];
        list.forEach(function (a, i) {
          const row = document.createElement('div');
          row.className = 'setting-row opacity-0 translate-y-1 transition-all duration-300';
          row.innerHTML = '<div class="setting-ico ' + a.t + ' text-xl">' + a.e + '</div>' +
            '<div class="grow min-w-0"><p class="setting-text font-extrabold text-sm text-ink">' + a.n + '</p>' +
            '<p class="text-[11px] text-muted">' + a.m + ' min</p></div>' +
            '<span class="chip chip-mint">+' + a.x + ' XP</span>';
          dayActivities.appendChild(row);
          setTimeout(function () { row.classList.remove('opacity-0', 'translate-y-1'); }, 80 + i * 100);
        });

        openSheet(daySheet, dayPanel, dayBackdrop);
      });
    });
    daySheetClose.addEventListener('click', function () { closeSheet(daySheet, dayPanel, dayBackdrop); });
    dayBackdrop.addEventListener('click', function () { closeSheet(daySheet, dayPanel, dayBackdrop); });

    // ---------- HIGHLIGHT SHEET ----------
    const hlSheet = document.getElementById('hlSheet');
    const hlPanel = document.getElementById('hlPanel');
    const hlBackdrop = document.getElementById('hlBackdrop');
    const hlClose = document.getElementById('hlClose');
    const hlTile = document.getElementById('hlTile');
    const hlTitle = document.getElementById('hlSheetTitle');
    const hlBody = document.getElementById('hlSheetBody');
    const hlCta = document.getElementById('hlCta');

    document.querySelectorAll('[data-highlight]').forEach(function (b) {
      b.addEventListener('click', function () {
        hlTile.className = 'mx-auto w-20 h-20 rounded-2xl ' + b.getAttribute('data-tile') + ' grid place-items-center text-4xl';
        hlTile.textContent = b.getAttribute('data-emoji');
        hlTitle.textContent = b.getAttribute('data-title');
        hlBody.textContent = b.getAttribute('data-body');
        hlCta.innerHTML = '<i class="ph-fill ph-arrow-right"></i> ' + b.getAttribute('data-cta');
        hlCta.href = b.getAttribute('data-href');
        // pop animation on the tile
        hlTile.animate([
          { transform: 'scale(0.8)' }, { transform: 'scale(1.08)' }, { transform: 'scale(1)' }
        ], { duration: 380, easing: 'ease-out' });
        openSheet(hlSheet, hlPanel, hlBackdrop);
      });
    });
    hlClose.addEventListener('click', function () { closeSheet(hlSheet, hlPanel, hlBackdrop); });
    hlBackdrop.addEventListener('click', function () { closeSheet(hlSheet, hlPanel, hlBackdrop); });

    // ---------- Subject rows navigate ----------
    document.querySelectorAll('[data-subj]').forEach(function (b) {
      b.addEventListener('click', function () {
        const href = b.getAttribute('data-href');
        if (href) location.href = href;
      });
    });

    // ---------- SHARE SHEET ----------
    const shareSheet = document.getElementById('shareSheet');
    const sharePanel = document.getElementById('sharePanel');
    const shareBackdrop = document.getElementById('shareBackdrop');
    const shareBtn = document.getElementById('shareBtn');
    const shareClose = document.getElementById('shareClose');
    const shareText = "Luna had a great week on Kidzio: +940 XP, 12 lessons, 6 active days.";
    const shareUrl = "https://kidzio.app/report/wk16";

    shareBtn.addEventListener('click', function () { openSheet(shareSheet, sharePanel, shareBackdrop); });
    shareClose.addEventListener('click', function () { closeSheet(shareSheet, sharePanel, shareBackdrop); });
    shareBackdrop.addEventListener('click', function () { closeSheet(shareSheet, sharePanel, shareBackdrop); });

    document.querySelectorAll('[data-share]').forEach(function (t) {
      t.addEventListener('click', function () {
        const k = t.getAttribute('data-share');
        if (k === 'email') window.open('mailto:?subject=' + encodeURIComponent('Luna\u2019s Kidzio week') + '&body=' + encodeURIComponent(shareText + ' ' + shareUrl));
        else if (k === 'whatsapp') window.open('https://wa.me/?text=' + encodeURIComponent(shareText + ' ' + shareUrl), '_blank', 'noopener');
        else if (k === 'messages') window.open('sms:?body=' + encodeURIComponent(shareText + ' ' + shareUrl));
        else if (k === 'copy') {
          if (navigator.clipboard) navigator.clipboard.writeText(shareUrl).then(function () { toast('Link copied'); });
          else toast('Link: ' + shareUrl);
        }
        closeSheet(shareSheet, sharePanel, shareBackdrop);
      });
    });

    // ---------- EMAIL EDIT SHEET ----------
    const emailSheet = document.getElementById('emailSheet');
    const emailPanel = document.getElementById('emailPanel');
    const emailBackdrop = document.getElementById('emailBackdrop');
    const emailSheetClose = document.getElementById('emailSheetClose');
    const emailEditBtn = document.getElementById('emailEditBtn');
    const emailInput = document.getElementById('emailInput');
    const emailHint = document.getElementById('emailSheetHint');
    const emailSaveBtn = document.getElementById('emailSaveBtn');
    const emailCancelBtn = document.getElementById('emailCancelBtn');
    const emailCurrent = document.getElementById('emailCurrent');
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    function validateEmail() {
      const v = (emailInput.value || '').trim();
      const ok = re.test(v);
      emailHint.innerHTML = ok
        ? '<i class="ph-fill ph-check-circle text-mint-ink"></i> Looks good.'
        : '<i class="ph-fill ph-warning-circle text-coral-ink"></i> Enter a valid email like name@example.com';
      emailHint.classList.toggle('text-coral-ink', !ok);
      emailHint.classList.toggle('text-muted', ok);
      emailSaveBtn.disabled = !ok;
      emailSaveBtn.classList.toggle('opacity-50', !ok);
      return ok;
    }
    emailEditBtn.addEventListener('click', function () {
      openSheet(emailSheet, emailPanel, emailBackdrop, function () {
        setTimeout(function () { emailInput.focus(); emailInput.select(); }, 200);
        validateEmail();
      });
    });
    emailSheetClose.addEventListener('click', function () { closeSheet(emailSheet, emailPanel, emailBackdrop); });
    emailBackdrop.addEventListener('click', function () { closeSheet(emailSheet, emailPanel, emailBackdrop); });
    emailCancelBtn.addEventListener('click', function () { closeSheet(emailSheet, emailPanel, emailBackdrop); });
    emailInput.addEventListener('input', validateEmail);
    emailSaveBtn.addEventListener('click', function () {
      if (!validateEmail()) return;
      emailCurrent.textContent = emailInput.value.trim();
      weekRange.textContent = (weekRange.textContent.split('·')[0] || '').trim() + ' · ' + emailInput.value.trim();
      toast('Verification link sent');
      closeSheet(emailSheet, emailPanel, emailBackdrop);
    });

    // ---------- Download PDF ----------
    document.getElementById('downloadBtn').addEventListener('click', function () {
      toast('Building PDF…');
      setTimeout(function () { location.href = 'export-progress.html'; }, 600);
    });

    // ---------- Toggles ----------
    document.querySelectorAll('[data-pref]').forEach(function (t) {
      t.addEventListener('change', function () {
        toast(t.getAttribute('data-pref') + ': ' + (t.checked ? 'on' : 'off'));
      });
    });

    // ---------- Esc close ----------
    document.addEventListener('keydown', function (e) {
      if (e.key !== 'Escape') return;
      [
        [weekSheet, weekPanel, weekBackdrop],
        [daySheet, dayPanel, dayBackdrop],
        [hlSheet, hlPanel, hlBackdrop],
        [shareSheet, sharePanel, shareBackdrop],
        [emailSheet, emailPanel, emailBackdrop]
      ].forEach(function (s) {
        if (!s[0].classList.contains('hidden')) closeSheet(s[0], s[1], s[2]);
      });
    });
  })();
