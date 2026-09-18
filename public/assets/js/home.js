// Page script for home.html — extracted from inline <script>.
// Loaded via <script src="./assets/js/home.js"></script>.

/* ═══════════════════════════════════════════════════════════════════
 *  Page script: home.html
 * 
 *  TABLE OF CONTENTS
 *  ─────────────────
 *    Animated XP counter ......................... line   25
 *    Cascade streak dots in ...................... line   39
 *    Notifications bell → bottom sheet ........... line   48
 *    Mark all as read ............................ line   96
 *    Esc to close sheet .......................... line  110
 *    PWA install prompt — real wiring ............ line  117
 *    SEARCH OVERLAY .............................. line  143
 *    Full index of items searchable from home .... line  156
 *    Voice search ................................ line  259
 *    Esc close ................................... line  280
 * ═══════════════════════════════════════════════════════════════════ */
  (function () {
    // Horizontal rails (.subjects-swiper, .achievements-swiper) are
    // auto-initialised by the shared helper in app.js via the
    // `data-swiper-rail` attribute — no per-page setup needed here.

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

    // Searchable destinations, rendered by the Livewire page into #searchIndex so the
    // names are Georgian and every href is a real route. Empty until it ships.
    const INDEX = (function () {
      const node = document.getElementById('searchIndex');
      if (!node) return [];
      try {
        const parsed = JSON.parse(node.textContent || '[]');
        return Array.isArray(parsed) ? parsed : [];
      } catch (e) {
        return [];
      }
    })();

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
      if (clearBtn) clearBtn.classList.toggle('hidden', q.length === 0);
      if (micBtn) micBtn.classList.toggle('hidden', q.length > 0);
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
          + '<p class="h-display text-lg mt-3 text-ink"></p>'
          + '<p class="text-xs text-muted mt-1"></p>'
          + '</div>';
        const lines = resultsBlock.querySelectorAll('p');
        lines[0].textContent = resultsBlock.dataset.emptyTitle || '';
        lines[1].textContent = resultsBlock.dataset.emptyHint || '';
        return;
      }

      matches.forEach(function (m, i) {
        const row = document.createElement('a');
        row.href = m.href;
        row.className = 'setting-row opacity-0 translate-y-1 transition-all duration-300';
        row.innerHTML = '<div class="setting-ico text-xl"></div>'
          + '<div class="grow min-w-0">'
          +   '<p class="setting-text font-extrabold text-sm text-ink"></p>'
          +   '<p class="text-[11px] text-muted"></p>'
          + '</div>'
          + '<i class="ph ph-caret-right text-muted"></i>';
        const ico = row.querySelector('.setting-ico');
        ico.classList.add(m.tile);
        ico.textContent = m.ico;
        const text = row.querySelectorAll('p');
        text[0].textContent = m.name;
        text[1].textContent = m.keys.slice(0, 60);
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
