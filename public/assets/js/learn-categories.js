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
      subject: 'all',
      week: 'all',
      status: 'all'
    };
    const draft = { subject: 'all', week: 'all', status: 'all' };

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

    const items = document.querySelectorAll('[data-item]');
    const sections = document.querySelectorAll('[data-search-section]');
    const noResults = document.getElementById('noResults');
    const sectionCount = document.querySelector('[data-section-count]');
    const queryStrip = document.getElementById('queryStrip');
    const queryChips = document.getElementById('queryChips');
    const filterDot = document.getElementById('filterDot');
    const clearAllBtn = document.getElementById('clearAllBtn');

    function norm(s) { return window.KidzioSearch ? KidzioSearch.norm(s) : (s || '').toLowerCase().trim(); }

    function matchItem(el, s) {
      const name = el.getAttribute('data-name') || '';
      const keys = el.getAttribute('data-keywords') || '';
      const subject = el.getAttribute('data-subject') || '';
      const week = el.getAttribute('data-week') || '';
      const status = el.getAttribute('data-status') || '';

      if (s.subject !== 'all' && subject !== s.subject) return false;
      if (s.week !== 'all' && week !== String(s.week)) return false;
      if (s.status !== 'all' && status !== s.status) return false;
      if (s.query && !(window.KidzioSearch ? KidzioSearch.matches(name + ' ' + keys, s.query) : (norm(name).includes(norm(s.query)) || norm(keys).includes(norm(s.query))))) return false;
      return true;
    }

    function renderQueryChips() {
      queryChips.innerHTML = '';
      const parts = [];
      if (state.query) parts.push({ k: 'query', l: '"' + state.query + '"' });
      if (state.subject !== 'all') parts.push({ k: 'subject', l: labelFor('subject', state.subject) });
      if (state.week !== 'all') parts.push({ k: 'week', l: labelFor('week', state.week) });
      if (state.status !== 'all') parts.push({ k: 'status', l: labelFor('status', state.status) });

      parts.forEach(function (p) {
        const b = document.createElement('button');
        b.type = 'button';
        b.className = 'chip';
        b.innerHTML = p.l + ' <i class="ph ph-x ml-1"></i>';
        b.addEventListener('click', function () {
          if (p.k === 'query') state.query = '';
          else state[p.k] = 'all';
          if (p.k !== 'query') draft[p.k] = state[p.k];
          paintAll();
        });
        queryChips.appendChild(b);
      });

      const active = parts.length > 0;
      queryStrip.classList.toggle('hidden', !active);
      filterDot.classList.toggle('hidden', !(state.subject !== 'all' || state.week !== 'all' || state.status !== 'all'));
    }

    function labelFor(kind, v) {
      const maps = {
        subject: {},
        week: {},
        status: { inprogress: '⏳ მიმდინარე', new: '🆕 დაუწყებელი', done: 'მზადაა' }
      };
      if (maps[kind] && maps[kind][v]) return maps[kind][v];
      const chip = document.querySelector('[data-' + kind + '="' + v + '"]');
      return chip ? chip.textContent.trim() : v;
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
        const filtering = state.query || state.subject !== 'all' || state.week !== 'all' || state.status !== 'all';
        sectionCount.textContent = filtering ? subjectVisible + ' ნაჩვენები' : '6 სულ';
      }
      noResults.classList.toggle('hidden', visible !== 0);
      renderQueryChips();
    }

    function draftCount() {
      let n = 0;
      items.forEach(function (el) {
        if (matchItem(el, { query: state.query, subject: draft.subject, week: draft.week, status: draft.status })) n++;
      });
      return n;
    }

    clearAllBtn.addEventListener('click', function () {
      state.query = '';
      state.subject = 'all';
      state.week = 'all';
      state.status = 'all';
      draft.subject = 'all'; draft.week = 'all'; draft.status = 'all';
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
      if (micBtn) micBtn.classList.toggle('hidden', q.length > 0);
      applySearchBtn.disabled = q.length === 0;
      applySearchBtn.classList.toggle('opacity-50', applySearchBtn.disabled);

      searchResults.innerHTML = '';
      if (!q) return;

      let hits = 0;
      INDEX.forEach(function (entry) {
        if (!(window.KidzioSearch ? KidzioSearch.matches((entry.name || '') + ' ' + (entry.keys || ''), libSearch.value) : (norm(entry.name).includes(q) || norm(entry.keys).includes(q)))) return;
        if (state.subject !== 'all' && entry.subject !== state.subject) return;
        if (state.week !== 'all' && String(entry.week) !== String(state.week)) return;
        if (state.status !== 'all' && entry.status !== state.status) return;
        hits++;
        const row = document.createElement('a');
        row.href = entry.href;
        row.className = 'setting-row';
        row.addEventListener('click', function () {
          if (window.KidzioSearch) KidzioSearch.record(libSearch.value.trim());
        });
        row.innerHTML = '<div class="setting-ico text-xl"></div>'
          + '<div class="grow min-w-0">'
          + '<p class="setting-text font-extrabold text-sm text-ink"></p>'
          + '<p class="text-[11px] text-muted"></p>'
          + '</div><i class="ph ph-caret-right text-muted"></i>';
        const ico = row.querySelector('.setting-ico');
        ico.classList.add(entry.tile || 'tile-violet');
        ico.textContent = entry.ico || '🔍';
        const text = row.querySelectorAll('p');
        text[0].textContent = entry.name;
        text[1].textContent = (entry.keys || '').slice(0, 60);
        searchResults.appendChild(row);
      });
      if (hits === 0) {
        const empty = document.createElement('p');
        empty.className = 'text-xs text-muted text-center py-8';
        empty.textContent = (searchResults.dataset.empty || '') + ' "' + libSearch.value.trim() + '"';
        searchResults.appendChild(empty);
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
      if (window.KidzioSearch && state.query) KidzioSearch.record(state.query);
      paintAll();
      closeSearch();
    });

    // Voice search
    if (micBtn) {
      const Rec = window.SpeechRecognition || window.webkitSpeechRecognition;
      micBtn.addEventListener('click', function () {
        if (!Rec) { toast('ხმოვანი ძიება არ არის მხარდაჭერილი'); return; }
        const r = new Rec();
        r.lang = 'ka-GE';
        r.interimResults = false;
        r.maxAlternatives = 1;
        micBtn.classList.add('animate-pulse');
        toast('ვუსმენ\u2026');
        r.onresult = function (ev) {
          libSearch.value = ev.results[0][0].transcript;
          renderResults();
        };
        r.onend = function () { micBtn.classList.remove('animate-pulse'); };
        r.onerror = function () { micBtn.classList.remove('animate-pulse'); toast('ვერ გავიგე'); };
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
    const subjectChips = document.querySelectorAll('#subjectChips [data-subject]');
    const weekChips = document.querySelectorAll('#weekChips [data-week]');
    const statusChips = document.querySelectorAll('#statusChips [data-status]');

    function syncOverlayChipsToDraft() {
      subjectChips.forEach(function (o) {
        const active = o.getAttribute('data-subject') === draft.subject;
        o.classList.toggle('chip-primary', active);
        o.setAttribute('aria-selected', active ? 'true' : 'false');
      });
      weekChips.forEach(function (o) {
        const active = o.getAttribute('data-week') === String(draft.week);
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
      draft.subject = 'all'; draft.week = 'all'; draft.status = 'all';
      syncOverlayChipsToDraft();
    }

    function openFilter() {
      // seed draft from current state
      draft.subject = state.subject;
      draft.week = state.week;
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
    wireChipGroup(subjectChips, 'data-subject', 'subject');
    wireChipGroup(weekChips, 'data-week', 'week');
    wireChipGroup(statusChips, 'data-status', 'status');

    filterReset.addEventListener('click', resetOverlayChips);

    applyFilterBtn.addEventListener('click', function () {
      state.subject = draft.subject;
      state.week = draft.week;
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
