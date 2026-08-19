// Page script for learn-alphabet.html — extracted from inline <script>.
// Loaded via <script src="./assets/js/learn-alphabet.js"></script>.

  (function () {
    const letters = ['A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z'];
    const currentIndex = 12;   // M is current
    const host = document.getElementById('letters');
    const frag = document.createDocumentFragment();
    letters.forEach((l, i) => {
      const done = i < currentIndex;
      const current = i === currentIndex;
      const locked = i > currentIndex;
      const a = document.createElement('a');
      a.href = locked ? 'lesson-locked.html' : current ? 'game-trace-letter.html' : 'game-spell-word.html';
      a.className = 'letter-tile ' + (done ? 'is-done' : current ? 'is-current' : locked ? 'is-locked' : '');
      a.setAttribute('aria-label', locked ? `Letter ${l} locked` : `Practice letter ${l}`);
      if (locked) {
        a.innerHTML = '<i class="ph-fill ph-lock text-base"></i>';
      } else {
        a.innerHTML = l + (done ? '<span class="lt-star" aria-hidden="true">⭐</span>' : '');
      }
      frag.appendChild(a);
    });
    host.appendChild(frag);
  })();
