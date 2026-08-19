// Page script for otp.html — extracted from inline <script>.
// Loaded via <script src="./assets/js/otp.js"></script>.

  (function () {
    const inputs = Array.from(document.querySelectorAll('#otp input'));
    const verifyBtn = document.getElementById('verifyBtn');
    const resendBtn = document.getElementById('resendBtn');
    const resendLabel = document.getElementById('resendLabel');
    const timerEl = document.getElementById('t');
    const hint = document.getElementById('hint');

    function code() { return inputs.map(function (i) { return i.value; }).join(''); }
    function syncState() {
      const full = code().length === 4;
      verifyBtn.disabled = !full;
      verifyBtn.classList.toggle('opacity-50', !full);
      if (full) {
        hint.textContent = 'Looks good \u2014 tap verify!';
        hint.classList.remove('text-muted');
        hint.classList.add('text-mint-ink');
      } else {
        hint.textContent = 'Tip: paste the code and it auto-fills.';
        hint.classList.add('text-muted');
        hint.classList.remove('text-mint-ink');
      }
    }
    inputs.forEach(function (i, idx) {
      i.addEventListener('input', function () {
        i.value = (i.value || '').replace(/\D/g, '').slice(0, 1);
        if (i.value && inputs[idx + 1]) inputs[idx + 1].focus();
        syncState();
      });
      i.addEventListener('keydown', function (e) {
        if (e.key === 'Backspace' && !i.value && idx > 0) inputs[idx - 1].focus();
      });
      i.addEventListener('paste', function (e) {
        const text = (e.clipboardData || window.clipboardData).getData('text') || '';
        const digits = text.replace(/\D/g, '').slice(0, 4).split('');
        if (!digits.length) return;
        e.preventDefault();
        digits.forEach(function (d, k) { if (inputs[k]) inputs[k].value = d; });
        inputs[Math.min(digits.length, inputs.length - 1)].focus();
        syncState();
      });
    });

    window.submitCode = function () {
      if (code().length !== 4) return;
      toast('Code verified');
      setTimeout(function () { location.href = 'new-password.html'; }, 600);
    };

    // countdown
    let s = 42;
    const id = setInterval(function () {
      s--;
      if (s <= 0) {
        clearInterval(id);
        timerEl.textContent = '00:00';
        resendLabel.textContent = "Didn't get it?";
        resendBtn.disabled = false;
        resendBtn.classList.remove('opacity-50');
        return;
      }
      timerEl.textContent = '00:' + String(s).padStart(2, '0');
    }, 1000);

    resendBtn.classList.add('opacity-50');
    resendBtn.addEventListener('click', function () {
      if (resendBtn.disabled) return;
      toast('Code resent');
      resendBtn.disabled = true;
      resendBtn.classList.add('opacity-50');
      s = 42;
      resendLabel.innerHTML = 'Resend code in <span class="font-extrabold text-ink" id="t">00:42</span>';
      const tEl = document.getElementById('t');
      const id2 = setInterval(function () {
        s--;
        if (s <= 0) {
          clearInterval(id2);
          if (tEl) tEl.textContent = '00:00';
          resendLabel.textContent = "Didn't get it?";
          resendBtn.disabled = false;
          resendBtn.classList.remove('opacity-50');
          return;
        }
        if (tEl) tEl.textContent = '00:' + String(s).padStart(2, '0');
      }, 1000);
    });
  })();
