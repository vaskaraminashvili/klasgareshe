// Page script for parent-email.html — extracted from inline <script>.
// Loaded via <script src="./assets/js/parent-email.js"></script>.

  (function () {
    const current = document.getElementById('currentEmail');
    const hero = document.getElementById('heroEmail');
    const newEmail = document.getElementById('newEmail');
    const confirmEmail = document.getElementById('confirmEmail');
    const currentPin = document.getElementById('currentPin');
    const saveBtn = document.getElementById('saveBtn');
    const emailHint = document.getElementById('emailHint');
    const confirmHint = document.getElementById('confirmHint');
    const emailStatus = document.getElementById('emailStatus');
    const copyBtn = document.getElementById('copyBtn');
    const PIN = '1234';
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    function animate(el, target, duration) {
      const start = performance.now();
      (function step(now) {
        const t = Math.min(1, (now - start) / duration);
        const ease = 1 - Math.pow(1 - t, 3);
        el.textContent = Math.round(target * ease);
        if (t < 1) requestAnimationFrame(step);
      })(start);
    }
    animate(document.getElementById('reportsVal'), 12, 900);
    animate(document.getElementById('opensVal'), 11, 900);
    animate(document.getElementById('daysVal'), 7, 900);

    function validate() {
      const e1 = (newEmail.value || '').trim();
      const e2 = (confirmEmail.value || '').trim();
      const pin = (currentPin.value || '').trim();
      const emailOk = re.test(e1);
      const matchOk = e1 && e2 && e1 === e2;
      const pinOk = pin.length === 4;

      if (!e1) {
        emailHint.innerHTML = '<i class="ph ph-info"></i> Enter the parent\'s email.';
        emailHint.classList.remove('text-coral-ink', 'text-mint-ink'); emailHint.classList.add('text-muted');
        emailStatus.classList.add('hidden');
      } else if (!emailOk) {
        emailHint.innerHTML = '<i class="ph-fill ph-warning-circle"></i> Format like name@example.com';
        emailHint.classList.remove('text-muted', 'text-mint-ink'); emailHint.classList.add('text-coral-ink');
        emailStatus.classList.add('hidden');
      } else {
        emailHint.innerHTML = '<i class="ph-fill ph-check-circle"></i> Looks good.';
        emailHint.classList.remove('text-muted', 'text-coral-ink'); emailHint.classList.add('text-mint-ink');
        emailStatus.classList.remove('hidden');
      }

      if (!e2) {
        confirmHint.innerHTML = '<i class="ph ph-info"></i> Must match the email above.';
        confirmHint.classList.remove('text-coral-ink', 'text-mint-ink'); confirmHint.classList.add('text-muted');
      } else if (!matchOk) {
        confirmHint.innerHTML = '<i class="ph-fill ph-warning-circle"></i> Emails don\'t match.';
        confirmHint.classList.remove('text-muted', 'text-mint-ink'); confirmHint.classList.add('text-coral-ink');
      } else {
        confirmHint.innerHTML = '<i class="ph-fill ph-check-circle"></i> Matches.';
        confirmHint.classList.remove('text-muted', 'text-coral-ink'); confirmHint.classList.add('text-mint-ink');
      }

      const ok = emailOk && matchOk && pinOk;
      saveBtn.disabled = !ok;
      saveBtn.classList.toggle('opacity-50', !ok);
    }
    newEmail.addEventListener('input', validate);
    confirmEmail.addEventListener('input', validate);
    currentPin.addEventListener('input', validate);

    window.submitChange = function () {
      if (currentPin.value !== PIN) {
        currentPin.animate([
          { transform: 'translateX(0)' }, { transform: 'translateX(-6px)' },
          { transform: 'translateX(6px)' }, { transform: 'translateX(-4px)' },
          { transform: 'translateX(4px)' }, { transform: 'translateX(0)' }
        ], { duration: 320 });
        toast('Wrong PIN — try 1234 in this demo');
        return;
      }
      const e = newEmail.value.trim();
      toast('Verification link sent to ' + e);
      current.textContent = e + ' · pending';
      hero.textContent = e;
      newEmail.value = ''; confirmEmail.value = ''; currentPin.value = '';
      validate();
    };

    copyBtn.addEventListener('click', function () {
      const text = current.textContent.replace(' · pending', '');
      if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(function () { toast('Email copied'); });
      } else {
        toast('Email: ' + text);
      }
    });

    document.querySelectorAll('[data-pref]').forEach(function (t) {
      t.addEventListener('change', function () {
        toast(t.getAttribute('data-pref') + ': ' + (t.checked ? 'on' : 'off'));
      });
    });

    validate();
  })();
