// Page script for parent-verify.html — extracted from inline <script>.
// Loaded via <script src="./assets/js/parent-verify.js"></script>.

/* ═══════════════════════════════════════════════════════════════════
 *  Page script: parent-verify.html
 * 
 *  TABLE OF CONTENTS
 *  ─────────────────
 *    Method switcher ............................... line   15
 *    OTP inputs: auto-advance, paste, backspace .... line   26
 *    Resend countdown .............................. line   70
 * ═══════════════════════════════════════════════════════════════════ */
  (function () {
    // Method switcher
    const methods = document.querySelectorAll('#methodPicker [data-method]');
    const panels = document.querySelectorAll('[data-panel]');
    methods.forEach(function (m) {
      m.addEventListener('click', function () {
        const key = m.getAttribute('data-method');
        methods.forEach(function (o) { o.classList.toggle('is-selected', o === m); });
        panels.forEach(function (p) { p.classList.toggle('hidden', p.getAttribute('data-panel') !== key); });
      });
    });

    // OTP inputs: auto-advance, paste, backspace
    const boxes = Array.from(document.querySelectorAll('.otp-box'));
    const verifyBtn = document.getElementById('verifyBtn');
    const hint = document.getElementById('otpHint');

    function syncState() {
      const code = boxes.map(function (b) { return b.value; }).join('');
      verifyBtn.disabled = code.length !== 6;
      if (code.length === 6) {
        hint.textContent = 'Looks good — tap verify!';
        hint.classList.remove('text-muted');
        hint.classList.add('text-mint-ink');
      } else {
        hint.textContent = 'Tip: paste the code and it auto-fills.';
        hint.classList.add('text-muted');
        hint.classList.remove('text-mint-ink');
      }
    }

    boxes.forEach(function (b, i) {
      b.addEventListener('input', function (e) {
        b.value = (b.value || '').replace(/\D/g, '').slice(0, 1);
        if (b.value && i < boxes.length - 1) boxes[i + 1].focus();
        syncState();
      });
      b.addEventListener('keydown', function (e) {
        if (e.key === 'Backspace' && !b.value && i > 0) boxes[i - 1].focus();
      });
      b.addEventListener('paste', function (e) {
        const text = (e.clipboardData || window.clipboardData).getData('text') || '';
        const digits = text.replace(/\D/g, '').slice(0, 6).split('');
        if (!digits.length) return;
        e.preventDefault();
        digits.forEach(function (d, k) { if (boxes[k]) boxes[k].value = d; });
        boxes[Math.min(digits.length, boxes.length - 1)].focus();
        syncState();
      });
    });

    verifyBtn.addEventListener('click', function () {
      toast('Verified! Loading…');
      setTimeout(function () { location.href = 'onboarding-age.html'; }, 800);
    });

    // Resend countdown
    const timerEl = document.getElementById('resendTimer');
    const resendBtn = document.getElementById('resendLink');
    let remaining = 30;
    function tick() {
      if (remaining > 0) {
        timerEl.textContent = '(' + remaining + 's)';
        resendBtn.disabled = true;
        resendBtn.classList.add('opacity-60');
        remaining--;
        setTimeout(tick, 1000);
      } else {
        timerEl.textContent = '';
        resendBtn.disabled = false;
        resendBtn.classList.remove('opacity-60');
      }
    }
    tick();
  })();
