// Page script for app-language.html — extracted from inline <script>.
// Loaded via <script src="./assets/js/app-language.js"></script>.

  (function () {
    const langs = document.querySelectorAll('[data-lang]');
    langs.forEach(function (l) {
      l.addEventListener('click', function () {
        toast('Language changed · reloading…');
        setTimeout(function () { location.href = 'settings.html'; }, 900);
      });
    });

    const input = document.getElementById('langSearch');
    const clearBtn = document.getElementById('langClear');
    const noResults = document.getElementById('noResults');
    const sections = document.querySelectorAll('[data-search-section]');
    const allSection = sections[sections.length - 1];
    const allLabel = allSection.querySelector('[data-label-text]');
    const allCount = allSection.querySelector('[data-count]');
    const totalAllLangs = allSection.querySelectorAll('[data-lang]').length;

    function norm(s) { return (s || '').toLowerCase().trim(); }

    function applyFilter(q) {
      const query = norm(q);
      const isSearching = query.length > 0;
      clearBtn.classList.toggle('hidden', !isSearching);

      let visibleTotal = 0;
      sections.forEach(function (sec) {
        const rows = sec.querySelectorAll('[data-lang]');
        let shown = 0;
        rows.forEach(function (row) {
          if (!isSearching) {
            row.classList.remove('hidden');
            shown++;
            return;
          }
          const name = norm(row.querySelector('.setting-text')?.textContent);
          const desc = norm(row.querySelector('.text-\\[11px\\]')?.textContent);
          const keys = norm(row.getAttribute('data-keywords'));
          const match = name.includes(query) || desc.includes(query) || keys.includes(query);
          row.classList.toggle('hidden', !match);
          if (match) shown++;
        });
        sec.classList.toggle('hidden', isSearching && shown === 0);
        visibleTotal += shown;
      });

      if (isSearching) {
        allLabel.textContent = 'Results';
        allCount.textContent = String(visibleTotal);
      } else {
        allLabel.textContent = 'All languages';
        allCount.textContent = String(totalAllLangs);
      }

      noResults.classList.toggle('hidden', !(isSearching && visibleTotal === 0));
    }

    input.addEventListener('input', function (e) { applyFilter(e.target.value); });
    clearBtn.addEventListener('click', function () {
      input.value = '';
      applyFilter('');
      input.focus();
    });
  })();
