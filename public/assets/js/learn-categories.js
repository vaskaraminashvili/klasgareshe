// Page script for learn-categories.html — extracted from inline <script>.
// Loaded via <script src="./assets/js/learn-categories.js"></script>.

/* ═══════════════════════════════════════════════════════════════════
 *  Page script: learn-categories.html
 * 
 *  TABLE OF CONTENTS
 *  ─────────────────
 *    State ................... line   18
 *    Open/close helpers ...... line  148
 *    SEARCH OVERLAY .......... line  169
 *    FILTER OVERLAY .......... line  267
 *    Global Esc .............. line  348
 *    Tile cascade intro ...... line  355
 * ═══════════════════════════════════════════════════════════════════ */
  (function () {
    // ---------- State ----------
    const state = {
      query: '',
      filter: 'all',    // category tag
      diff: 'all',
      age: 'all',
      status: 'all'
    };
    // draft state is for the filter overlay until Apply is pressed
    const draft = { filter: 'all', diff: 'all', age: 'all', status: 'all' };

    const items = document.querySelectorAll('[data-item]');
    const sections = document.querySelectorAll('[data-search-section]');
    const noResults = document.getElementById('noResults');
    const sectionCount = document.querySelector('[data-section-count]');
    const queryStrip = document.getElementById('queryStrip');
    const queryChips = document.getElementById('queryChips');
    const filterDot = document.getElementById('filterDot');
    const clearAllBtn = document.getElementById('clearAllBtn');

    function norm(s) { return (s || '').toLowerCase().trim(); }

    function matchItem(el, s) {
      const name = norm(el.getAttribute('data-name'));
      const keys = norm(el.getAttribute('data-keywords'));
      const tags = (el.getAttribute('data-tags') || '').split(/\s+/);
      const diff = el.getAttribute('data-diff') || 'all';
      const ageAttr = parseInt(el.getAttribute('data-age'), 10);
      const status = el.getAttribute('data-status') || 'all';
      const q = norm(s.query);

      if (s.filter !== 'all' && tags.indexOf(s.filter) === -1) return false;
      if (s.diff !== 'all' && diff !== s.diff) return false;
      if (s.status !== 'all' && status !== s.status) return false;
      if (s.age !== 'all') {
        const minAge = parseInt(s.age, 10);
        if (isNaN(ageAttr)) return true; // unknown age passes
        // bucket: 4 → 4–6, 6 → 6–8, 8 → 8+
        if (minAge === 4 && !(ageAttr >= 4 && ageAttr <= 6)) return false;
        if (minAge === 6 && !(ageAttr >= 6 && ageAttr <= 8)) return false;
        if (minAge === 8 && ageAttr < 8) return false;
      }
      if (q && !(name.includes(q) || keys.includes(q))) return false;
      return true;
    }

    function renderQueryChips() {
      queryChips.innerHTML = '';
      const parts = [];
      if (state.query) parts.push({ k: 'query', l: '"' + state.query + '"' });
      if (state.filter !== 'all') parts.push({ k: 'filter', l: labelFor('filter', state.filter) });
      if (state.diff !== 'all') parts.push({ k: 'diff', l: labelFor('diff', state.diff) });
      if (state.age !== 'all') parts.push({ k: 'age', l: labelFor('age', state.age) });
      if (state.status !== 'all') parts.push({ k: 'status', l: labelFor('status', state.status) });

      parts.forEach(function (p) {
        const b = document.createElement('button');
        b.type = 'button';
        b.className = 'chip';
        b.innerHTML = p.l + ' <i class="ph ph-x ml-1"></i>';
        b.addEventListener('click', function () {
          if (p.k === 'query') state.query = '';
          else state[p.k] = 'all';
          draft[p.k === 'query' ? 'filter' : p.k] = state[p.k === 'query' ? 'filter' : p.k];
          paintAll();
        });
        queryChips.appendChild(b);
      });

      const active = parts.length > 0;
      queryStrip.classList.toggle('hidden', !active);
      filterDot.classList.toggle('hidden', !(state.filter !== 'all' || state.diff !== 'all' || state.age !== 'all' || state.status !== 'all'));
    }

    function labelFor(kind, v) {
      const maps = {
        filter: { pop: '🔥 Popular', new: '🆕 New', games: '🎮 Games', read: '📖 Reading', math: '➗ Math' },
        diff: { easy: '🌱 Easy', medium: '🌿 Medium', hard: '🌳 Challenge' },
        age: { '4': 'Age 4–6', '6': 'Age 6–8', '8': 'Age 8+' },
        status: { inprogress: '⏳ In progress', new: '🆕 Not started' }
      };
      return (maps[kind] && maps[kind][v]) || v;
    }

    function paintAll() {
      let visible = 0;
      let subjectVisible = 0;
      items.forEach(function (el) {
        const show = matchItem(el, state);
        if (show) {
          el.classList.remove('hidden');
          el.classList.add('opacity-0', 'transition-opacity', 'duration-300');
          requestAnimationFrame(function () { el.classList.remove('opacity-0'); });
          visible++;
          if (el.closest('[data-search-section]')?.querySelector('[data-section-count]')) subjectVisible++;
        } else {
          el.classList.add('hidden');
        }
      });
      sections.forEach(function (sec) {
        const visibleHere = sec.querySelectorAll('[data-item]:not(.hidden)').length;
        sec.classList.toggle('hidden', visibleHere === 0);
      });
      if (sectionCount) {
        const filtering = state.query || state.filter !== 'all' || state.diff !== 'all' || state.age !== 'all' || state.status !== 'all';
        sectionCount.textContent = filtering ? subjectVisible + ' shown' : '6 total';
      }
      noResults.classList.toggle('hidden', visible !== 0);
      renderQueryChips();
    }

    function draftCount() {
      let n = 0;
      items.forEach(function (el) {
        if (matchItem(el, { query: state.query, filter: draft.filter, diff: draft.diff, age: draft.age, status: draft.status })) n++;
      });
      return n;
    }

    clearAllBtn.addEventListener('click', function () {
      state.query = '';
      state.filter = 'all';
      state.diff = 'all';
      state.age = 'all';
      state.status = 'all';
      draft.filter = 'all'; draft.diff = 'all'; draft.age = 'all'; draft.status = 'all';
      resetOverlayChips();
      paintAll();
    });

    // ---------- Open/close helpers ----------
    function openOverlay(overlay, panel, backdrop, onOpen) {
      overlay.classList.remove('hidden');
      requestAnimationFrame(function () {
        backdrop.classList.remove('opacity-0');
        backdrop.classList.add('opacity-100');
        panel.classList.remove('translate-y-full');
      });
      document.body.style.overflow = 'hidden';
      if (onOpen) onOpen();
    }
    function closeOverlay(overlay, panel, backdrop) {
      backdrop.classList.remove('opacity-100');
      backdrop.classList.add('opacity-0');
      panel.classList.add('translate-y-full');
      setTimeout(function () {
        overlay.classList.add('hidden');
        document.body.style.overflow = '';
      }, 280);
    }

    // ---------- SEARCH OVERLAY ----------
    const searchOverlay = document.getElementById('searchOverlay');
    const searchPanel = document.getElementById('searchPanel');
    const searchBackdrop = document.getElementById('searchBackdrop');
    const searchClose = document.getElementById('searchClose');
    const searchIcon = document.getElementById('searchIconBtn');
    const libSearch = document.getElementById('libSearch');
    const clearBtn = document.getElementById('clearBtn');
    const micBtn = document.getElementById('micBtn');
    const searchResults = document.getElementById('searchResults');
    const recentChips = document.querySelectorAll('[data-recent]');
    const applySearchBtn = document.getElementById('applySearchBtn');

    function openSearch() {
      openOverlay(searchOverlay, searchPanel, searchBackdrop, function () {
        setTimeout(function () { libSearch.focus(); }, 200);
        libSearch.value = state.query || '';
        renderResults();
      });
    }
    function closeSearch() { closeOverlay(searchOverlay, searchPanel, searchBackdrop); }

    searchIcon.addEventListener('click', openSearch);
    searchClose.addEventListener('click', closeSearch);
    searchBackdrop.addEventListener('click', closeSearch);

    function renderResults() {
      const q = norm(libSearch.value);
      clearBtn.classList.toggle('hidden', q.length === 0);
      micBtn.classList.toggle('hidden', q.length > 0);
      applySearchBtn.disabled = q.length === 0;
      applySearchBtn.classList.toggle('opacity-50', applySearchBtn.disabled);

      searchResults.innerHTML = '';
      if (!q) return;

      let hits = 0;
      items.forEach(function (el) {
        const name = norm(el.getAttribute('data-name'));
        const keys = norm(el.getAttribute('data-keywords'));
        if (!(name.includes(q) || keys.includes(q))) return;
        hits++;
        const row = document.createElement('a');
        row.href = el.getAttribute('href');
        row.className = 'setting-row';
        row.innerHTML = '<div class="setting-ico tile-violet"><i class="ph-fill ph-magnifying-glass"></i></div>'
          + '<div class="grow min-w-0">'
          + '<p class="setting-text font-extrabold text-sm text-ink">' + el.getAttribute('data-name') + '</p>'
          + '<p class="text-[11px] text-muted">' + (el.getAttribute('data-keywords') || '').slice(0, 60) + '</p>'
          + '</div><i class="ph ph-caret-right text-muted"></i>';
        searchResults.appendChild(row);
      });
      if (hits === 0) {
        searchResults.innerHTML = '<p class="text-xs text-muted text-center py-8">No matches for "' + q + '"</p>';
      }
    }

    libSearch.addEventListener('input', renderResults);
    clearBtn.addEventListener('click', function () {
      libSearch.value = '';
      renderResults();
      libSearch.focus();
    });

    recentChips.forEach(function (c) {
      c.addEventListener('click', function () {
        libSearch.value = c.textContent.trim();
        renderResults();
      });
    });

    applySearchBtn.addEventListener('click', function () {
      state.query = libSearch.value.trim();
      paintAll();
      closeSearch();
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
          libSearch.value = ev.results[0][0].transcript;
          renderResults();
        };
        r.onend = function () { micBtn.classList.remove('animate-pulse'); };
        r.onerror = function () { micBtn.classList.remove('animate-pulse'); toast('Couldn\u2019t hear you'); };
        try { r.start(); } catch (e) { micBtn.classList.remove('animate-pulse'); }
      });
    }

    // ---------- FILTER OVERLAY ----------
    const filterOverlay = document.getElementById('filterOverlay');
    const filterPanel = document.getElementById('filterPanel');
    const filterBackdrop = document.getElementById('filterBackdrop');
    const filterClose = document.getElementById('filterClose');
    const filterIcon = document.getElementById('filterIconBtn');
    const filterReset = document.getElementById('filterReset');
    const applyFilterBtn = document.getElementById('applyFilterBtn');
    const applyCountEl = document.getElementById('applyCount');
    const catChips = document.querySelectorAll('#catChips [data-filter]');
    const diffChips = document.querySelectorAll('#diffChips [data-diff]');
    const ageChips = document.querySelectorAll('#ageChips [data-age]');
    const statusChips = document.querySelectorAll('#statusChips [data-status]');

    function syncOverlayChipsToDraft() {
      catChips.forEach(function (o) {
        const active = o.getAttribute('data-filter') === draft.filter;
        o.classList.toggle('chip-primary', active);
        o.setAttribute('aria-selected', active ? 'true' : 'false');
      });
      diffChips.forEach(function (o) {
        const active = o.getAttribute('data-diff') === draft.diff;
        o.classList.toggle('chip-primary', active);
        o.setAttribute('aria-selected', active ? 'true' : 'false');
      });
      ageChips.forEach(function (o) {
        const active = o.getAttribute('data-age') === draft.age;
        o.classList.toggle('chip-primary', active);
        o.setAttribute('aria-selected', active ? 'true' : 'false');
      });
      statusChips.forEach(function (o) {
        const active = o.getAttribute('data-status') === draft.status;
        o.classList.toggle('chip-primary', active);
        o.setAttribute('aria-selected', active ? 'true' : 'false');
      });
      applyCountEl.textContent = draftCount();
    }
    function resetOverlayChips() {
      draft.filter = 'all'; draft.diff = 'all'; draft.age = 'all'; draft.status = 'all';
      syncOverlayChipsToDraft();
    }

    function openFilter() {
      // seed draft from current state
      draft.filter = state.filter;
      draft.diff = state.diff;
      draft.age = state.age;
      draft.status = state.status;
      syncOverlayChipsToDraft();
      openOverlay(filterOverlay, filterPanel, filterBackdrop);
    }
    function closeFilter() { closeOverlay(filterOverlay, filterPanel, filterBackdrop); }

    filterIcon.addEventListener('click', openFilter);
    filterClose.addEventListener('click', closeFilter);
    filterBackdrop.addEventListener('click', closeFilter);

    function wireChipGroup(list, attr, key) {
      list.forEach(function (c) {
        c.addEventListener('click', function () {
          draft[key] = c.getAttribute(attr);
          syncOverlayChipsToDraft();
        });
      });
    }
    wireChipGroup(catChips, 'data-filter', 'filter');
    wireChipGroup(diffChips, 'data-diff', 'diff');
    wireChipGroup(ageChips, 'data-age', 'age');
    wireChipGroup(statusChips, 'data-status', 'status');

    filterReset.addEventListener('click', resetOverlayChips);

    applyFilterBtn.addEventListener('click', function () {
      state.filter = draft.filter;
      state.diff = draft.diff;
      state.age = draft.age;
      state.status = draft.status;
      paintAll();
      closeFilter();
    });

    // ---------- Global Esc ----------
    document.addEventListener('keydown', function (e) {
      if (e.key !== 'Escape') return;
      if (!searchOverlay.classList.contains('hidden')) closeSearch();
      else if (!filterOverlay.classList.contains('hidden')) closeFilter();
    });

    // ---------- Tile cascade intro ----------
    const tiles = document.querySelectorAll('.tile[data-item]');
    tiles.forEach(function (t, i) {
      t.classList.add('opacity-0', 'translate-y-2', 'transition-all', 'duration-400');
      setTimeout(function () {
        t.classList.remove('opacity-0', 'translate-y-2');
      }, 120 + i * 70);
    });

    // initial paint
    paintAll();
  })();
