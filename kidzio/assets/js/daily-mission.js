// Page script for daily-mission.html — extracted from inline <script>.
// Loaded via <script src="./assets/js/daily-mission.js"></script>.

/* ═══════════════════════════════════════════════════════════════════
 *  Page script: daily-mission.html
 * 
 *  TABLE OF CONTENTS
 *  ─────────────────
 *    Live countdown until midnight ...... line   16
 *    Share button ....................... line   35
 *    Completed task tap → show recap .... line   51
 *    Locked task tap → hint ............. line   58
 * ═══════════════════════════════════════════════════════════════════ */
  (function () {
    // Live countdown until midnight
    const h = document.querySelector('[data-cd="h"]');
    const m = document.querySelector('[data-cd="m"]');
    const s = document.querySelector('[data-cd="s"]');
    function pad(n) { return n < 10 ? '0' + n : String(n); }
    function tick() {
      const now = new Date();
      const end = new Date(now);
      end.setHours(24, 0, 0, 0);
      let diff = Math.max(0, Math.floor((end - now) / 1000));
      const hh = Math.floor(diff / 3600); diff -= hh * 3600;
      const mm = Math.floor(diff / 60); const ss = diff - mm * 60;
      if (h) h.textContent = pad(hh);
      if (m) m.textContent = pad(mm);
      if (s) s.textContent = pad(ss);
    }
    tick();
    setInterval(tick, 1000);

    // Share button
    const shareBtn = document.getElementById('shareBtn');
    if (shareBtn) {
      shareBtn.addEventListener('click', function () {
        const text = "I'm 2/3 through today's Kidzio mission 🚀 Join me!";
        const url = 'https://kidzio.app/mission/today';
        if (navigator.share) {
          navigator.share({ title: 'Kidzio daily mission', text: text, url: url }).catch(function () {});
        } else if (navigator.clipboard) {
          navigator.clipboard.writeText(url).then(function () { toast('Mission link copied'); });
        } else {
          toast('Mission link: ' + url);
        }
      });
    }

    // Completed task tap → show recap
    document.querySelectorAll('[data-task-review]').forEach(function (el) {
      el.addEventListener('click', function () {
        toast('✓ ' + el.getAttribute('data-task-review'));
      });
    });

    // Locked task tap → hint
    document.querySelectorAll('[data-task-lock]').forEach(function (el) {
      el.addEventListener('click', function () {
        toast('🔒 ' + el.getAttribute('data-task-lock'));
      });
    });

    // Remind-me toggle (persists for this session)
    const remind = document.getElementById('remindMe');
    if (remind) {
      let on = localStorage.getItem('kidzio.missionRemind') === '1';
      function paint() {
        remind.textContent = on ? '✓ Reminder on' : 'Remind me';
        remind.classList.toggle('chip-mint', on);
        remind.classList.toggle('chip-primary', !on);
      }
      paint();
      remind.addEventListener('click', function () {
        on = !on;
        try { localStorage.setItem('kidzio.missionRemind', on ? '1' : '0'); } catch (e) {}
        paint();
        toast(on ? 'We\u2019ll ping you before midnight' : 'Reminder off');
      });
    }
  })();
