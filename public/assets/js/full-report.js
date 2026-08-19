// Page script for full-report.html — extracted from inline <script>.
// Loaded via <script src="./assets/js/full-report.js"></script>.

/* ═══════════════════════════════════════════════════════════════════
 *  Page script: full-report.html
 * 
 *  TABLE OF CONTENTS
 *  ─────────────────
 *    Counter animation ....... line   24
 *    SCOPE tabs .............. line   36
 *    KPI counter cascade ..... line   93
 *    Row cascade ............. line   99
 *    Bottom sheet helpers .... line  105
 *    COMPARE SHEET ........... line  126
 *    KPI SHEET ............... line  136
 *    EVENT SHEET ............. line  173
 *    SUBJECTS navigate ....... line  199
 *    SHARE SHEET ............. line  207
 *    Export .................. line  234
 *    Esc close ............... line  240
 * ═══════════════════════════════════════════════════════════════════ */
  (function () {
    // ---------- Counter animation ----------
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

    // ---------- SCOPE tabs ----------
    const scopes = {
      week:   { chip: 'THIS WEEK',    title: 'Luna\u2019s week',    sub: '6 of 7 active days · +12% vs last', xp: 940,  lessons: 12,  acc: 92, days: '6 / 7',   chart: 'XP by day',   label: 'Apr 11 \u2014 17', pace: 'Ahead of pace' },
      month:  { chip: 'APRIL SO FAR', title: 'Luna\u2019s full month', sub: '17 days active · +12% vs March',   xp: 1030, lessons: 52,  acc: 92, days: '17 days', chart: 'XP by week',  label: 'April 2026',      pace: 'Ahead of pace' },
      season: { chip: 'THIS SEASON',  title: 'Spring season',      sub: '3 months active · +18% vs winter', xp: 2630, lessons: 148, acc: 89, days: '72 days', chart: 'XP by month', label: 'Spring 2026',     pace: 'Strong pace' },
      all:    { chip: 'ALL-TIME',     title: 'Since day one',      sub: '142 days active · lifetime stats',  xp: 5180, lessons: 286, acc: 87, days: '142 days', chart: 'XP by season', label: 'All-time',        pace: 'Steady climber' }
    };
    const scopeBtns = document.querySelectorAll('[data-scope]');
    const scopeChip = document.getElementById('scopeChip');
    const scopeTitle = document.getElementById('scopeTitle');
    const scopeSub = document.getElementById('scopeSub');
    const xpVal = document.getElementById('xpVal');
    const lessonsVal = document.getElementById('lessonsVal');
    const accVal = document.getElementById('accVal');
    const daysChip = document.getElementById('daysChip');
    const paceChip = document.getElementById('paceChip');
    const chartTitle = document.getElementById('chartTitle');
    const rangeLabel = document.getElementById('rangeLabel');

    function drawChart(key) {
      const datasets = {
        week:   [{label:'Mon',value:120},{label:'Tue',value:200},{label:'Wed',value:170},{label:'Thu',value:240},{label:'Fri',value:360},{label:'Sat',value:210},{label:'Sun',value:80}],
        month:  [{label:'W1',value:220},{label:'W2',value:340},{label:'W3',value:290},{label:'W4',value:180}],
        season: [{label:'Feb',value:760},{label:'Mar',value:840},{label:'Apr',value:1030}],
        all:    [{label:'Fall',value:620},{label:'Win',value:1140},{label:'Spr',value:2630},{label:'Sum',value:790}]
      };
      KCharts.line(document.getElementById('line'), datasets[key]);
    }

    function applyScope(key) {
      const s = scopes[key] || scopes.month;
      scopeChip.innerHTML = '<i class="ph-fill ph-sparkle"></i> ' + s.chip;
      scopeTitle.textContent = s.title;
      scopeSub.textContent = s.sub;
      chartTitle.textContent = s.chart;
      rangeLabel.textContent = 'Luna · ' + s.label;
      daysChip.innerHTML = '<i class="ph-fill ph-calendar"></i> ' + s.days;
      paceChip.innerHTML = '<i class="ph-fill ph-trend-up"></i> ' + s.pace;
      xpVal.textContent = '0'; lessonsVal.textContent = '0'; accVal.textContent = '0';
      animate(xpVal, s.xp, 900);
      animate(lessonsVal, s.lessons, 700);
      animate(accVal, s.acc, 800);
      drawChart(key);
    }

    scopeBtns.forEach(function (b) {
      b.addEventListener('click', function () {
        scopeBtns.forEach(function (o) {
          o.classList.remove('chip-primary');
          o.setAttribute('aria-selected', 'false');
        });
        b.classList.add('chip-primary');
        b.setAttribute('aria-selected', 'true');
        applyScope(b.getAttribute('data-scope'));
      });
    });

    // ---------- KPI counter cascade ----------
    document.querySelectorAll('[data-anim]').forEach(function (el, i) {
      const target = parseInt(el.getAttribute('data-target'), 10) || 0;
      setTimeout(function () { animate(el, target, 700); }, 200 + i * 80);
    });

    // ---------- Row cascade ----------
    document.querySelectorAll('[data-kpi], [data-subj], [data-event]').forEach(function (r, i) {
      r.classList.add('opacity-0', 'translate-y-1', 'transition-all', 'duration-300');
      setTimeout(function () { r.classList.remove('opacity-0', 'translate-y-1'); }, 250 + i * 60);
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

    // ---------- COMPARE SHEET ----------
    const compareSheet = document.getElementById('compareSheet');
    const comparePanel = document.getElementById('comparePanel');
    const compareBackdrop = document.getElementById('compareBackdrop');
    const compareClose = document.getElementById('compareClose');
    const compareBtn = document.getElementById('compareBtn');
    compareBtn.addEventListener('click', function () { openSheet(compareSheet, comparePanel, compareBackdrop); });
    compareClose.addEventListener('click', function () { closeSheet(compareSheet, comparePanel, compareBackdrop); });
    compareBackdrop.addEventListener('click', function () { closeSheet(compareSheet, comparePanel, compareBackdrop); });

    // ---------- KPI SHEET ----------
    const kpiSheet = document.getElementById('kpiSheet');
    const kpiPanel = document.getElementById('kpiPanel');
    const kpiBackdrop = document.getElementById('kpiBackdrop');
    const kpiClose = document.getElementById('kpiClose');
    const kpiTile = document.getElementById('kpiTile');
    const kpiTitle = document.getElementById('kpiTitle');
    const kpiValue = document.getElementById('kpiValue');
    const kpiDelta = document.getElementById('kpiDelta');
    const kpiBody = document.getElementById('kpiBody');
    const kpiBodies = {
      'Longest streak': 'Luna\u2019s longest streak this month was 14 days — a personal best. Streak freeze saved one of them.',
      'Avg session':    'Sessions averaged 11 minutes — 2 min longer than last month. Sweet spot is 10\u201315 min for age 6.',
      'Badges unlocked':'Earned 4 new badges including the rare Counter Champ. 16 badges still to unlock.',
      'Friends rank':   'Up 2 spots since last month. Leo is currently #1 at 2,140 XP.'
    };

    document.querySelectorAll('[data-kpi]').forEach(function (b) {
      b.addEventListener('click', function () {
        const name = b.getAttribute('data-name');
        kpiTile.className = 'mx-auto w-20 h-20 rounded-2xl ' + b.getAttribute('data-tile') + ' grid place-items-center text-4xl';
        kpiTile.textContent = b.getAttribute('data-emoji');
        kpiTitle.textContent = name;
        const val = b.getAttribute('data-val');
        const unit = b.getAttribute('data-unit');
        kpiValue.innerHTML = (name === 'Friends rank' ? '#' : '') + val + ' <span class="text-base text-muted">' + unit + '</span>';
        kpiDelta.innerHTML = '<i class="ph ph-arrow-up"></i> ' + b.getAttribute('data-delta');
        kpiBody.textContent = kpiBodies[name] || 'Great progress this month.';
        kpiTile.animate([
          { transform: 'scale(0.8)' }, { transform: 'scale(1.08)' }, { transform: 'scale(1)' }
        ], { duration: 380, easing: 'ease-out' });
        openSheet(kpiSheet, kpiPanel, kpiBackdrop);
      });
    });
    kpiClose.addEventListener('click', function () { closeSheet(kpiSheet, kpiPanel, kpiBackdrop); });
    kpiBackdrop.addEventListener('click', function () { closeSheet(kpiSheet, kpiPanel, kpiBackdrop); });

    // ---------- EVENT SHEET ----------
    const eventSheet = document.getElementById('eventSheet');
    const eventPanel = document.getElementById('eventPanel');
    const eventBackdrop = document.getElementById('eventBackdrop');
    const eventClose = document.getElementById('eventClose');
    const eventTile = document.getElementById('eventTile');
    const eventTitle = document.getElementById('eventTitle');
    const eventSub = document.getElementById('eventSub');
    const eventBody = document.getElementById('eventBody');

    document.querySelectorAll('[data-event]').forEach(function (b) {
      b.addEventListener('click', function () {
        eventTile.className = 'mx-auto w-20 h-20 rounded-2xl ' + b.getAttribute('data-tile') + ' grid place-items-center text-4xl';
        eventTile.textContent = b.getAttribute('data-emoji');
        eventTitle.textContent = b.getAttribute('data-title');
        eventSub.textContent = b.getAttribute('data-sub');
        eventBody.textContent = b.getAttribute('data-body');
        eventTile.animate([
          { transform: 'scale(0.8)' }, { transform: 'scale(1.08)' }, { transform: 'scale(1)' }
        ], { duration: 380, easing: 'ease-out' });
        openSheet(eventSheet, eventPanel, eventBackdrop);
      });
    });
    eventClose.addEventListener('click', function () { closeSheet(eventSheet, eventPanel, eventBackdrop); });
    eventBackdrop.addEventListener('click', function () { closeSheet(eventSheet, eventPanel, eventBackdrop); });

    // ---------- SUBJECTS navigate ----------
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
    const shareSheetClose = document.getElementById('shareSheetClose');
    const shareText = "Luna's Kidzio full report: +1,030 XP, 52 lessons, 92% accuracy this month.";
    const shareUrl = "https://kidzio.app/report/luna/april";

    shareBtn.addEventListener('click', function () { openSheet(shareSheet, sharePanel, shareBackdrop); });
    shareSheetClose.addEventListener('click', function () { closeSheet(shareSheet, sharePanel, shareBackdrop); });
    shareBackdrop.addEventListener('click', function () { closeSheet(shareSheet, sharePanel, shareBackdrop); });

    document.querySelectorAll('[data-share]').forEach(function (t) {
      t.addEventListener('click', function () {
        const k = t.getAttribute('data-share');
        if (k === 'email') window.open('mailto:?subject=' + encodeURIComponent('Luna\u2019s Kidzio full report') + '&body=' + encodeURIComponent(shareText + ' ' + shareUrl));
        else if (k === 'whatsapp') window.open('https://wa.me/?text=' + encodeURIComponent(shareText + ' ' + shareUrl), '_blank', 'noopener');
        else if (k === 'messages') window.open('sms:?body=' + encodeURIComponent(shareText + ' ' + shareUrl));
        else if (k === 'copy') {
          if (navigator.clipboard) navigator.clipboard.writeText(shareUrl).then(function () { toast('Link copied'); });
          else toast('Link: ' + shareUrl);
        }
        closeSheet(shareSheet, sharePanel, shareBackdrop);
      });
    });

    // ---------- Export ----------
    document.getElementById('exportBtn').addEventListener('click', function () {
      toast('Building PDF…');
      setTimeout(function () { location.href = 'export-progress.html'; }, 600);
    });

    // ---------- Esc close ----------
    document.addEventListener('keydown', function (e) {
      if (e.key !== 'Escape') return;
      [
        [compareSheet, comparePanel, compareBackdrop],
        [kpiSheet, kpiPanel, kpiBackdrop],
        [eventSheet, eventPanel, eventBackdrop],
        [shareSheet, sharePanel, shareBackdrop]
      ].forEach(function (s) {
        if (!s[0].classList.contains('hidden')) closeSheet(s[0], s[1], s[2]);
      });
    });

    // Initial month scope paint
    applyScope('month');
  })();
