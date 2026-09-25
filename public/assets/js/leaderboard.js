// Page script for leaderboard.html — extracted from inline <script>.
// Loaded via <script src="./assets/js/leaderboard.js"></script>.

/* ═══════════════════════════════════════════════════════════════════
 *  Page script: leaderboard.html
 * 
 *  TABLE OF CONTENTS
 *  ─────────────────
 *    SEARCH OVERLAY ...................................... line   77
 *    Voice search ........................................ line  199
 *    Esc closes overlay .................................. line  220
 *    Season countdown: 14 days from today, plus live… .... line  225
 * ═══════════════════════════════════════════════════════════════════ */
  (function () {
    const noRank = document.getElementById('noRank');
    const queryStrip = document.getElementById('queryStrip');
    const queryLabel = document.getElementById('queryLabel');
    const queryClear = document.getElementById('queryClear');
    let activeFilter = 'all';
    let currentQuery = '';

    function norm(s) { return (s || '').toLowerCase().trim(); }

    function applyFilter() {
      const rows = document.querySelectorAll('[data-row]');
      const q = norm(currentQuery);
      const isSearching = q.length > 0;
      if (queryStrip) queryStrip.classList.toggle('hidden', !isSearching);
      if (isSearching && queryLabel) queryLabel.textContent = '"' + currentQuery + '"';

      let visible = 0;
      rows.forEach(function (row) {
        const name = norm(row.getAttribute('data-name'));
        const country = norm(row.getAttribute('data-country'));
        const streak = row.getAttribute('data-streak') === '1';
        const online = row.getAttribute('data-online') === '1';
        const isMe = row.hasAttribute('data-me');

        let passFilter = true;
        if (activeFilter === 'country') passFilter = country.includes('usa') || country.includes('us') || country.includes('united states') || isMe;
        else if (activeFilter === 'streak') passFilter = streak || isMe;
        else if (activeFilter === 'online') passFilter = online || isMe;

        const passSearch = !isSearching || name.includes(q) || country.includes(q);

        const show = passFilter && passSearch;
        row.classList.toggle('hidden', !show);
        if (show) {
          row.classList.add('opacity-0', 'transition-opacity', 'duration-300');
          requestAnimationFrame(function () { row.classList.remove('opacity-0'); });
          visible++;
        }
      });

      if (noRank) noRank.classList.toggle('hidden', visible !== 0);
    }

    if (queryClear) {
      queryClear.addEventListener('click', function () {
        currentQuery = '';
        applyFilter();
      });
    }

    if (!window.__kidzioLeaderboardFilters) {
      window.__kidzioLeaderboardFilters = true;
      document.addEventListener('click', function (e) {
        const c = e.target.closest('[data-filter]');
        if (!c || !c.closest('.rail-swiper')) return;
        const chips = document.querySelectorAll('[data-filter]');
        activeFilter = c.getAttribute('data-filter');
        chips.forEach(function (o) {
          const active = o === c;
          o.classList.toggle('chip-primary', active);
          o.setAttribute('aria-selected', active ? 'true' : 'false');
        });
        applyFilter();
      });
    }

    // ---------- SEARCH OVERLAY ----------
    const searchOverlay = document.getElementById('searchOverlay');
    const searchPanel = document.getElementById('searchPanel');
    const searchBackdrop = document.getElementById('searchBackdrop');
    const searchClose = document.getElementById('searchClose');
    const searchIcon = document.getElementById('searchIconBtn');
    const rankInput = document.getElementById('rankSearchInput');
    const clearBtn = document.getElementById('clearBtn');
    const micBtn = document.getElementById('micBtn');
    const suggestBlock = document.getElementById('searchSuggest');
    const resultsBlock = document.getElementById('searchResults');
    const applySearchBtn = document.getElementById('applySearchBtn');
    const recentChips = document.querySelectorAll('[data-recent]');
    const suggestBtns = document.querySelectorAll('[data-suggest]');

    function openSearch() {
      searchOverlay.classList.remove('hidden');
      requestAnimationFrame(function () {
        searchBackdrop.classList.remove('opacity-0');
        searchBackdrop.classList.add('opacity-100');
        searchPanel.classList.remove('translate-y-full');
      });
      document.body.style.overflow = 'hidden';
      setTimeout(function () { rankInput.focus(); }, 200);
      rankInput.value = currentQuery;
      renderResults();
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

    function rankingComponent() {
      const root = document.querySelector('main[wire\\:id]');
      return root && window.Livewire ? Livewire.find(root.getAttribute('wire:id')) : null;
    }

    function renderResults() {
      const q = norm(rankInput.value);
      clearBtn.classList.toggle('hidden', q.length === 0);
      if (micBtn) micBtn.classList.toggle('hidden', q.length > 0);
      const hasQuery = q.length > 0;
      suggestBlock.classList.toggle('hidden', hasQuery);
      resultsBlock.classList.toggle('hidden', !hasQuery);
      applySearchBtn.disabled = !hasQuery;
      applySearchBtn.classList.toggle('opacity-50', !hasQuery);

      if (!hasQuery) return;

      const live = rankingComponent();
      if (!live) return;
      const typed = rankInput.value.trim();
      live.call('searchPlayers', typed).then(function (hits) {
        if (norm(rankInput.value) !== q) return;
        resultsBlock.innerHTML = '';
        const list = Array.isArray(hits) ? hits : [];
        list.forEach(function (hit, i) {
          const node = document.createElement('button');
          node.type = 'button';
          node.className = 'setting-row opacity-0 translate-y-1 transition-all duration-300 w-full text-left';
          node.innerHTML = '<div class="setting-ico tile-sun text-xl"></div>'
            + '<div class="grow min-w-0">'
            +   '<p class="setting-text font-extrabold text-sm text-ink"></p>'
            +   '<p class="text-[11px] text-muted"></p>'
            + '</div>'
            + '<span class="chip shrink-0"></span>';
          node.querySelector('.setting-ico').textContent = hit.avatar || '🐻';
          const text = node.querySelectorAll('p');
          text[0].textContent = (hit.rank ? '#' + hit.rank + ' · ' : '') + hit.nickname;
          text[1].textContent = hit.name || '';
          node.querySelector('.chip').textContent = (hit.xp || 0) + ' XP';
          node.addEventListener('click', function () {
            rankInput.value = hit.nickname;
            const component = rankingComponent();
            if (component) component.call('applyPlayerSearch', hit.nickname);
          });
          resultsBlock.appendChild(node);
          setTimeout(function () { node.classList.remove('opacity-0', 'translate-y-1'); }, i * 40);
        });
        if (list.length === 0) {
          resultsBlock.innerHTML = '<div class="k-card text-center p-6">'
            + '<div class="w-16 h-16 mx-auto rounded-2xl tile-sky grid place-items-center text-3xl">🔍</div>'
            + '<p class="h-display text-lg mt-3 text-ink"></p>'
            + '<p class="text-xs text-muted mt-1"></p>'
            + '</div>';
          const lines = resultsBlock.querySelectorAll('p');
          lines[0].textContent = resultsBlock.dataset.emptyTitle || '';
          lines[1].textContent = resultsBlock.dataset.emptyHint || '';
        }
      });
    }

    if (searchIcon && searchOverlay && searchPanel && searchBackdrop && searchClose && rankInput && clearBtn && suggestBlock && resultsBlock && applySearchBtn) {
    searchIcon.addEventListener('click', openSearch);
    searchClose.addEventListener('click', closeSearch);
    searchBackdrop.addEventListener('click', closeSearch);
    rankInput.addEventListener('input', renderResults);
    clearBtn.addEventListener('click', function () {
      rankInput.value = '';
      renderResults();
      rankInput.focus();
    });

    recentChips.forEach(function (c) {
      c.addEventListener('click', function () {
        rankInput.value = c.textContent.trim();
        renderResults();
      });
    });

    suggestBtns.forEach(function (b) {
      b.addEventListener('click', function () {
        rankInput.value = b.getAttribute('data-name');
        renderResults();
      });
    });

    applySearchBtn.addEventListener('click', function () {
      const component = rankingComponent();
      if (component) component.call('applyPlayerSearch', rankInput.value.trim());
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
          rankInput.value = ev.results[0][0].transcript;
          renderResults();
        };
        r.onend = function () { micBtn.classList.remove('animate-pulse'); };
        r.onerror = function () { micBtn.classList.remove('animate-pulse'); toast('Couldn\u2019t hear you'); };
        try { r.start(); } catch (e) { micBtn.classList.remove('animate-pulse'); }
      });
    }

    // Esc closes overlay
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && !searchOverlay.classList.contains('hidden')) closeSearch();
    });
    }

    // Season countdown: 14 days from today, plus live daily clock
    const seasonDays = document.getElementById('seasonDays');
    const seasonClock = document.getElementById('seasonClock');
    const seasonEnd = new Date();
    seasonEnd.setDate(seasonEnd.getDate() + 14);
    seasonEnd.setHours(23, 59, 59, 0);

    function pad(n) { return n < 10 ? '0' + n : String(n); }
    function tickSeason() {
      const now = new Date();
      const daysLeft = Math.max(0, Math.ceil((seasonEnd - now) / 86400000));
      if (seasonDays) seasonDays.textContent = daysLeft + (daysLeft === 1 ? ' day' : ' days');

      const midnight = new Date(now);
      midnight.setHours(24, 0, 0, 0);
      let diff = Math.max(0, Math.floor((midnight - now) / 1000));
      const hh = Math.floor(diff / 3600); diff -= hh * 3600;
      const mm = Math.floor(diff / 60); const ss = diff - mm * 60;
      if (seasonClock) seasonClock.textContent = pad(hh) + ':' + pad(mm) + ':' + pad(ss);
    }
    tickSeason();
    setInterval(tickSeason, 1000);
  })();
