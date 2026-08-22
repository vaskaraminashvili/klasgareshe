// Page script for badges.html — extracted from inline <script>.
// Loaded via <script src="./assets/js/badges.js"></script>.

  (function () {
    const statusChips = document.querySelectorAll('[data-status]');
    const catChips = document.querySelectorAll('[data-cat]');
    const items = document.querySelectorAll('[data-badge-item]');
    const sections = document.querySelectorAll('[data-badge-section]');
    const noBadges = document.getElementById('noBadges');
    const shareBtn = document.getElementById('shareBtn');

    let activeStatus = 'all';
    let activeCat = 'all';

    function applyFilter() {
      let visible = 0;
      items.forEach(function (el) {
        const s = el.getAttribute('data-status');
        const c = el.getAttribute('data-cat');
        const r = el.getAttribute('data-rarity');
        const passStatus = activeStatus === 'all'
          || activeStatus === s
          || (activeStatus === 'rare' && (r === 'rare' || r === 'epic' || r === 'legend'));
        const passCat = activeCat === 'all' || activeCat === c;
        const show = passStatus && passCat;
        el.classList.toggle('hidden', !show);
        if (show) visible++;
      });

      sections.forEach(function (sec) {
        const secStatus = sec.getAttribute('data-badge-section');
        const visibleHere = sec.querySelectorAll('[data-badge-item]:not(.hidden)').length;
        const hideSection = visibleHere === 0 || (activeStatus !== 'all' && activeStatus !== 'rare' && activeStatus !== secStatus);
        sec.classList.toggle('hidden', hideSection);
        const count = sec.querySelector('[data-count]');
        if (count) {
          const noun = secStatus === 'got' ? 'earned' : secStatus === 'inprog' ? 'close' : 'locked';
          count.textContent = visibleHere + ' ' + noun;
        }
      });

      noBadges.classList.toggle('hidden', visible !== 0);
    }

    function wireChips(list, onPick) {
      list.forEach(function (c) {
        c.addEventListener('click', function () {
          list.forEach(function (o) {
            const active = o === c;
            o.classList.toggle('chip-primary', active);
            o.setAttribute('aria-selected', active ? 'true' : 'false');
          });
          onPick(c);
          applyFilter();
        });
      });
    }
    wireChips(statusChips, function (c) { activeStatus = c.getAttribute('data-status'); });
    wireChips(catChips, function (c) { activeCat = c.getAttribute('data-cat'); });

    // Locked / in-progress tap → show unlock hint
    items.forEach(function (el) {
      if (el.tagName.toLowerCase() !== 'button') return;
      el.addEventListener('click', function () {
        const hint = el.getAttribute('data-hint');
        if (hint) toast(hint);
      });
    });

    // Share button
    if (shareBtn) {
      shareBtn.addEventListener('click', function () {
        const shareText = shareBtn.getAttribute('data-share-text') || '';
        const copied = shareBtn.getAttribute('data-share-copied') || '';
        const url = window.location.href;
        if (navigator.share) {
          navigator.share({ title: shareText, text: shareText, url: url }).catch(function () {});
        } else if (navigator.clipboard) {
          navigator.clipboard.writeText(url).then(function () { toast(copied); });
        } else {
          toast(url);
        }
      });
    }
  })();
