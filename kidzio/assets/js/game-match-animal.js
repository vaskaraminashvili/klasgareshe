// Page script for game-match-animal.html — extracted from inline <script>.
// Loaded via <script src="./assets/js/game-match-animal.js"></script>.

  (function () {
    const group = document.getElementById('ansGroup');
    const btns = Array.from(group.querySelectorAll('[data-val]'));
    const nextBtn = document.getElementById('nextBtn');
    const speakerBtn = document.getElementById('speakerBtn');
    const streakChip = document.getElementById('streakChip');
    const progressFill = document.getElementById('progressFill');
    const hearText = document.getElementById('hearText');

    const CORRECT = 'Giraffe';
    let streak = 3;
    let done = false;

    function speak(text) {
      if ('speechSynthesis' in window) {
        const u = new SpeechSynthesisUtterance(text);
        u.rate = 0.85;
        window.speechSynthesis.cancel();
        window.speechSynthesis.speak(u);
      } else {
        toast('Hear: ' + text);
      }
    }

    btns.forEach(function (b) {
      b.addEventListener('click', function (e) {
        if (done) { e.stopImmediatePropagation(); return; }
        const val = b.getAttribute('data-val');
        const ok = b.getAttribute('data-ans') === 'correct';
        if (ok) {
          done = true;
          btns.forEach(function (o) { o.disabled = true; });
          streak++;
          streakChip.textContent = '🔥 ' + streak;
          progressFill.classList.remove('w-90');
          progressFill.classList.add('w-100');
          hearText.innerHTML = '<span class="font-extrabold text-mint-ink">Correct!</span> It\u2019s a giraffe — the tallest animal on land.';
          nextBtn.disabled = false;
          nextBtn.classList.remove('opacity-50');
          nextBtn.onclick = function () { location.href = 'game-opposites.html'; };
          speak('Giraffe');
        } else {
          streak = 0;
          streakChip.textContent = '🔥 ' + streak;
          hearText.innerHTML = '<span class="font-extrabold text-coral-ink">Not quite.</span> Hear the word again and try another.';
          setTimeout(function () {
            b.classList.remove('wrong');
            // also clear the auto-revealed correct from the global handler
            btns.forEach(function (o) { o.classList.remove('correct'); });
          }, 900);
        }
      });
    });

    speakerBtn.addEventListener('click', function () { speak(CORRECT); });

    nextBtn.classList.add('opacity-50');
  })();
