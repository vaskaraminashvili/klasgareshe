// Page script for home.html — extracted from inline <script>.
// Loaded via <script src="./assets/js/home.js"></script>.

/* ═══════════════════════════════════════════════════════════════════
 *  Page script: home.html
 * 
 *  TABLE OF CONTENTS
 *  ─────────────────
 *    Time-aware greeting subline ................. line   27
 *    Animated XP counter ......................... line   38
 *    Cascade streak dots in ...................... line   52
 *    Notifications bell → bottom sheet ........... line   61
 *    Mark all as read ............................ line  109
 *    Esc to close sheet .......................... line  123
 *    PWA install prompt — real wiring ............ line  130
 *    SEARCH OVERLAY .............................. line  156
 *    Full index of items searchable from home .... line  169
 *    Voice search ................................ line  272
 *    Esc close ................................... line  293
 * ═══════════════════════════════════════════════════════════════════ */
  (function () {
    // Horizontal rails (.subjects-swiper, .achievements-swiper) are
    // auto-initialised by the shared helper in app.js via the
    // `data-swiper-rail` attribute — no per-page setup needed here.

    // Time-aware greeting subline
    const greet = document.getElementById('greetLine');
    if (greet) {
      const h = new Date().getHours();
      const msg = h < 12 ? 'Good morning · let\u2019s learn'
                : h < 17 ? 'Good afternoon · let\u2019s play'
                : h < 21 ? 'Good evening · keep your streak'
                : 'Bedtime brain boost';
      greet.textContent = msg;
    }

    // Animated XP counter
    const xp = document.getElementById('xpStat');
    if (xp) {
      const target = parseInt(xp.getAttribute('data-target'), 10) || 0;
      const duration = 1100;
      const start = performance.now();
      (function step(now) {
        const t = Math.min(1, (now - start) / duration);
        const ease = 1 - Math.pow(1 - t, 3);
        xp.textContent = Math.round(target * ease).toLocaleString();
        if (t < 1) requestAnimationFrame(step);
      })(start);
    }

    // Cascade streak dots in
    const dots = document.querySelectorAll('#weekStreak .streak-dot');
    dots.forEach(function (d, i) {
      setTimeout(function () {
        d.classList.remove('opacity-0');
        d.classList.add('opacity-100');
      }, 250 + i * 90);
    });

    // Notifications bell → bottom sheet
    const bell = document.getElementById('bellBtn');
    const badge = document.getElementById('bellBadge');
    const sheet = document.getElementById('notifSheet');
    const countEl = document.getElementById('notifCount');
    const markAllBtn = document.getElementById('markAllBtn');
    const notifList = document.getElementById('notifList');

    function unreadCount() {
      return notifList ? notifList.querySelectorAll('[data-notif]:not([data-read])').length : 0;
    }
    function paintBadge() {
      const n = unreadCount();
      if (countEl) countEl.textContent = String(n);
      if (!badge) return;
      if (n === 0) {
        badge.classList.add('opacity-0', 'scale-50', 'transition-all', 'duration-300');
      } else {
        badge.textContent = String(n);
        badge.classList.remove('opacity-0', 'scale-50');
      }
    }
    paintBadge();

    if (bell) {
      bell.addEventListener('click', function () {
        // the global data-sheet handler in app.js opens/closes the sheet
        if (badge) {
          badge.classList.add('transition-all', 'duration-300');
        }
      });
    }

    // Mark individual as read when tapped (before navigation fires)
    if (notifList) {
      notifList.querySelectorAll('[data-notif]').forEach(function (row) {
        row.addEventListener('click', function () {
          if (!row.hasAttribute('data-read')) {
            row.setAttribute('data-read', '');
            row.classList.add('opacity-70');
            const dot = row.querySelector('span[aria-label="Unread"]');
            if (dot) dot.remove();
            paintBadge();
          }
        });
      });
    }

    // Mark all as read
    if (markAllBtn && notifList) {
      markAllBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        notifList.querySelectorAll('[data-notif]:not([data-read])').forEach(function (row) {
          row.setAttribute('data-read', '');
          row.classList.add('opacity-70');
          const dot = row.querySelector('span[aria-label="Unread"]');
          if (dot) dot.remove();
        });
        paintBadge();
      });
    }

    // Esc to close sheet
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && sheet && !sheet.classList.contains('hidden')) {
        sheet.classList.add('hidden');
      }
    });

    // PWA install prompt — real wiring
    const installBtn = document.querySelector('[data-install]');
    let deferred = null;
    window.addEventListener('beforeinstallprompt', function (e) {
      e.preventDefault();
      deferred = e;
      if (installBtn) installBtn.hidden = false;
    });
    if (installBtn) {
      installBtn.addEventListener('click', function () {
        if (!deferred) { toast('Install not available here'); return; }
        deferred.prompt();
        deferred.userChoice.then(function (choice) {
          if (choice && choice.outcome === 'accepted') {
            toast('Installing Kidzio\u2026');
          }
          deferred = null;
          installBtn.hidden = true;
        });
      });
    }
    window.addEventListener('appinstalled', function () {
      if (installBtn) installBtn.hidden = true;
      toast('Kidzio installed \u2713');
    });

    // ---------- SEARCH OVERLAY ----------
    const searchOverlay = document.getElementById('searchOverlay');
    const searchPanel = document.getElementById('searchPanel');
    const searchBackdrop = document.getElementById('searchBackdrop');
    const searchClose = document.getElementById('searchClose');
    const searchIcon = document.getElementById('searchIconBtn');
    const homeSearch = document.getElementById('homeSearch');
    const clearBtn = document.getElementById('clearBtn');
    const micBtn = document.getElementById('micBtn');
    const suggestBlock = document.getElementById('searchSuggest');
    const resultsBlock = document.getElementById('searchResults');
    const recentChips = document.querySelectorAll('[data-recent]');

    // Full index of items searchable from home
    const INDEX = [
      { name: 'Math',            keys: 'math numbers counting shapes addition',      href: 'learn-math.html',      ico: '➗', tile: 'tile-violet' },
      { name: 'Alphabet',        keys: 'alphabet abc letters phonics reading',        href: 'learn-alphabet.html',  ico: '🔤', tile: 'tile-sun' },
      { name: 'Animals',         keys: 'animals wildlife lion giraffe dog cat',       href: 'learn-animals.html',   ico: '🦁', tile: 'tile-mint' },
      { name: 'Words',           keys: 'words sight spelling vocabulary reading',     href: 'learn-words.html',     ico: '📚', tile: 'tile-coral' },
      { name: 'Knowledge',       keys: 'knowledge world science space planets',       href: 'learn-knowledge.html', ico: '🌍', tile: 'tile-sky' },
      { name: 'Opposites',       keys: 'opposites big small hot cold up down',        href: 'learn-opposites.html', ico: '⚖️', tile: 'tile-pink' },
      { name: 'Quick Quiz',      keys: 'quiz multiple choice questions game',         href: 'game-multiple-choice.html', ico: '❓', tile: 'tile-violet' },
      { name: 'Match Words',     keys: 'match words drag drop vocabulary game',       href: 'game-match-word.html', ico: '🧩', tile: 'tile-mint' },
      { name: 'Word Search',     keys: 'word search find letters game',               href: 'game-word-search.html', ico: '🔎', tile: 'tile-coral' },
      { name: 'Counting game',   keys: 'counting numbers math apples game',           href: 'game-counting.html',   ico: '🔢', tile: 'tile-sky' },
      { name: 'Trace letter',    keys: 'trace letters handwriting alphabet',          href: 'game-trace-letter.html', ico: '✍️', tile: 'tile-sun' },
      { name: 'Spell it',        keys: 'spell words letters apple game',              href: 'game-spell-word.html', ico: '✏️', tile: 'tile-sun' },
      { name: 'Match animal',    keys: 'animals match giraffe zebra lion game',       href: 'game-match-animal.html', ico: '🦒', tile: 'tile-mint' },
      { name: 'Daily mission',   keys: 'mission daily tasks goal',                    href: 'daily-mission.html',   ico: '🎯', tile: 'tile-violet' },
      { name: 'Streak',          keys: 'streak fire days habit',                      href: 'streak.html',          ico: '🔥', tile: 'tile-sun' },
      { name: 'Badges',          keys: 'badges achievements medals trophies',         href: 'badges.html',          ico: '🏅', tile: 'tile-mint' },
      { name: 'Leaderboard',     keys: 'ranking leaderboard compete friends',         href: 'leaderboard.html',     ico: '🏆', tile: 'tile-sun' }
    ];

    function normS(s) { return (s || '').toLowerCase().trim(); }

    function openSearch() {
      searchOverlay.classList.remove('hidden');
      requestAnimationFrame(function () {
        searchBackdrop.classList.remove('opacity-0');
        searchBackdrop.classList.add('opacity-100');
        searchPanel.classList.remove('translate-y-full');
      });
      document.body.style.overflow = 'hidden';
      setTimeout(function () { homeSearch.focus(); }, 200);
      homeSearch.value = '';
      renderSearch();
    }
    function closeSearch() {
      searchBackdrop.classList.remove('opacity-100');
      searchBackdrop.classList.add('opacity-0');
      searchPanel.classList.add('translate-y-full');
      setTimeout(function () {
        searchOverlay.classList.add('hidden');
        document.body.style.overflow = '';
      }, 280);
    }

    function renderSearch() {
      const q = normS(homeSearch.value);
      clearBtn.classList.toggle('hidden', q.length === 0);
      micBtn.classList.toggle('hidden', q.length > 0);
      const hasQuery = q.length > 0;
      suggestBlock.classList.toggle('hidden', hasQuery);
      resultsBlock.classList.toggle('hidden', !hasQuery);

      if (!hasQuery) return;

      resultsBlock.innerHTML = '';
      const matches = INDEX.filter(function (it) {
        return normS(it.name).includes(q) || normS(it.keys).includes(q);
      });

      if (matches.length === 0) {
        resultsBlock.innerHTML = '<div class="k-card text-center p-6">'
          + '<div class="w-16 h-16 mx-auto rounded-2xl tile-sky grid place-items-center text-3xl">🔍</div>'
          + '<p class="h-display text-lg mt-3 text-ink">No matches</p>'
          + '<p class="text-xs text-muted mt-1">Try different words like "counting" or "animals".</p>'
          + '</div>';
        return;
      }

      matches.forEach(function (m, i) {
        const row = document.createElement('a');
        row.href = m.href;
        row.className = 'setting-row opacity-0 translate-y-1 transition-all duration-300';
        row.innerHTML = '<div class="setting-ico ' + m.tile + ' text-xl">' + m.ico + '</div>'
          + '<div class="grow min-w-0">'
          +   '<p class="setting-text font-extrabold text-sm text-ink">' + m.name + '</p>'
          +   '<p class="text-[11px] text-muted">' + m.keys.slice(0, 60) + '</p>'
          + '</div>'
          + '<i class="ph ph-caret-right text-muted"></i>';
        resultsBlock.appendChild(row);
        setTimeout(function () {
          row.classList.remove('opacity-0', 'translate-y-1');
        }, i * 40);
      });
    }

    searchIcon.addEventListener('click', openSearch);
    searchClose.addEventListener('click', closeSearch);
    searchBackdrop.addEventListener('click', closeSearch);
    homeSearch.addEventListener('input', renderSearch);
    clearBtn.addEventListener('click', function () {
      homeSearch.value = '';
      renderSearch();
      homeSearch.focus();
    });

    recentChips.forEach(function (c) {
      c.addEventListener('click', function () {
        homeSearch.value = c.textContent.trim();
        renderSearch();
      });
    });

    // Voice search
    if (micBtn) {
      const Rec = window.SpeechRecognition || window.webkitSpeechRecognition;
      micBtn.addEventListener('click', function () {
        if (!Rec) { toast('Voice search not supported'); return; }
        const r = new Rec();
        r.lang = 'en-US';
        r.interimResults = false;
        r.maxAlternatives = 1;
        micBtn.classList.add('animate-pulse');
        toast('Listening\u2026');
        r.onresult = function (ev) {
          homeSearch.value = ev.results[0][0].transcript;
          renderSearch();
        };
        r.onend = function () { micBtn.classList.remove('animate-pulse'); };
        r.onerror = function () { micBtn.classList.remove('animate-pulse'); toast('Couldn\u2019t hear you'); };
        try { r.start(); } catch (e) { micBtn.classList.remove('animate-pulse'); }
      });
    }

    // Esc close
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && !searchOverlay.classList.contains('hidden')) closeSearch();
    });
  })();
