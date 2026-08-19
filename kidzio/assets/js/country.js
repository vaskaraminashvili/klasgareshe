// Page script for country.html — extracted from inline <script>.
// Loaded via <script src="./assets/js/country.js"></script>.

  (function () {
    const countries = document.querySelectorAll('[data-country]');
    countries.forEach(function (c) {
      c.addEventListener('click', function () {
        toast('Country updated · ranking refreshed');
        setTimeout(function () { location.href = 'settings.html'; }, 900);
      });
    });

    const input = document.getElementById('countrySearch');
    const clearBtn = document.getElementById('countryClear');
    const noResults = document.getElementById('noResults');
    const sections = document.querySelectorAll('[data-search-section]');
    const continentTabs = document.querySelectorAll('[data-continent]');
    const originalLabels = new Map();
    sections.forEach(function (sec) {
      const el = sec.querySelector('[data-label-text]');
      if (el) originalLabels.set(sec, el.textContent);
    });

    let activeContinent = 'all';

    function norm(s) { return (s || '').toLowerCase().trim(); }

    function applyFilter() {
      const query = norm(input.value);
      const isSearching = query.length > 0;
      clearBtn.classList.toggle('hidden', !isSearching);

      let visibleTotal = 0;
      sections.forEach(function (sec) {
        const lockedAll = sec.getAttribute('data-continent-wrap') === 'all';
        const rows = sec.querySelectorAll('[data-country]');
        let shown = 0;
        rows.forEach(function (row) {
          const rowCont = row.getAttribute('data-continent') || 'all';
          const passContinent = lockedAll || activeContinent === 'all' || rowCont === activeContinent || rowCont === 'all';
          if (!passContinent) { row.classList.add('hidden'); return; }
          if (!isSearching) {
            row.classList.remove('hidden');
            shown++;
            return;
          }
          const name = norm(row.querySelector('.setting-text, .font-extrabold')?.textContent);
          const desc = norm(row.querySelector('.text-\\[11px\\], .text-\\[10px\\]')?.textContent);
          const keys = norm(row.getAttribute('data-keywords'));
          const match = name.includes(query) || desc.includes(query) || keys.includes(query);
          row.classList.toggle('hidden', !match);
          if (match) shown++;
        });
        const sectionHidden = shown === 0 && (isSearching || activeContinent !== 'all');
        sec.classList.toggle('hidden', sectionHidden && !lockedAll);
        if (lockedAll) sec.classList.toggle('hidden', activeContinent !== 'all');
        const labelEl = sec.querySelector('[data-label-text]');
        if (labelEl) {
          labelEl.textContent = isSearching ? 'Results · ' + shown : originalLabels.get(sec);
        }
        visibleTotal += shown;
      });

      noResults.classList.toggle('hidden', !(visibleTotal === 0 && (isSearching || activeContinent !== 'all')));
    }

    input.addEventListener('input', applyFilter);
    clearBtn.addEventListener('click', function () {
      input.value = '';
      applyFilter();
      input.focus();
    });

    continentTabs.forEach(function (t) {
      t.addEventListener('click', function () {
        activeContinent = t.getAttribute('data-continent');
        continentTabs.forEach(function (o) {
          const active = o === t;
          o.classList.toggle('chip-primary', active);
          o.setAttribute('aria-selected', active ? 'true' : 'false');
        });
        applyFilter();
      });
    });
  })();
