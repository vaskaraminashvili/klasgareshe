// Page script for parent-controls.html — extracted from inline <script>.
// Loaded via <script src="./assets/js/parent-controls.js"></script>.

/* ═══════════════════════════════════════════════════════════════════
 *  Page script: parent-controls.html
 * 
 *  TABLE OF CONTENTS
 *  ─────────────────
 *    PIN GATE .................. line   17
 *    HERO COUNTER ANIMATION .... line  139
 *    WEEK BARS ANIMATION ....... line  153
 *    TOGGLES ................... line  162
 *    DANGER .................... line  169
 * ═══════════════════════════════════════════════════════════════════ */
  (function () {
    // ---------- PIN GATE ----------
    const PIN = '1234';
    const gate = document.getElementById('pinGate');
    const panel = document.getElementById('pinPanel');
    const pinInputs = Array.from(document.querySelectorAll('.pin-box'));
    const pinHint = document.getElementById('pinHint');
    const padBtns = document.querySelectorAll('#pinPad [data-pin]');
    const lockBtn = document.getElementById('lockBtn');
    const lockNowBtn = document.getElementById('lockNowBtn');

    function openGate() {
      gate.classList.remove('hidden');
      document.body.style.overflow = 'hidden';
      requestAnimationFrame(function () {
        panel.classList.remove('opacity-0', 'translate-y-3');
      });
      setTimeout(function () { pinInputs[0].focus(); }, 250);
      pinInputs.forEach(function (i) { i.value = ''; });
      paintHint('Hint: PIN is <b class="text-ink">1234</b> in this demo.', 'text-muted');
    }
    function closeGate() {
      panel.classList.add('opacity-0', 'translate-y-3');
      setTimeout(function () {
        gate.classList.add('hidden');
        document.body.style.overflow = '';
      }, 350);
    }

    function paintHint(html, cls) {
      pinHint.innerHTML = html;
      pinHint.classList.remove('text-muted', 'text-coral-ink', 'text-mint-ink');
      pinHint.classList.add(cls);
    }

    function currentPin() { return pinInputs.map(function (i) { return i.value; }).join(''); }
    function checkPin() {
      const p = currentPin();
      if (p.length !== 4) return;
      if (p === PIN) {
        paintHint('<i class="ph-fill ph-check-circle"></i> Unlocked', 'text-mint-ink');
        setTimeout(closeGate, 400);
      } else {
        paintHint('<i class="ph-fill ph-warning-circle"></i> Wrong PIN — try again', 'text-coral-ink');
        // shake animation
        panel.animate([
          { transform: 'translateX(0)' },
          { transform: 'translateX(-8px)' },
          { transform: 'translateX(8px)' },
          { transform: 'translateX(-6px)' },
          { transform: 'translateX(6px)' },
          { transform: 'translateX(0)' }
        ], { duration: 350, easing: 'ease-out' });
        setTimeout(function () {
          pinInputs.forEach(function (i) { i.value = ''; });
          pinInputs[0].focus();
        }, 400);
      }
    }

    // Typed input
    pinInputs.forEach(function (inp, idx) {
      inp.addEventListener('input', function () {
        inp.value = (inp.value || '').replace(/\D/g, '').slice(0, 1);
        if (inp.value && pinInputs[idx + 1]) pinInputs[idx + 1].focus();
        if (currentPin().length === 4) checkPin();
      });
      inp.addEventListener('keydown', function (e) {
        if (e.key === 'Backspace' && !inp.value && idx > 0) pinInputs[idx - 1].focus();
      });
    });

    // Numeric pad
    padBtns.forEach(function (b) {
      b.addEventListener('click', function () {
        const k = b.getAttribute('data-pin');
        if (k === 'back') {
          for (let i = pinInputs.length - 1; i >= 0; i--) {
            if (pinInputs[i].value) { pinInputs[i].value = ''; pinInputs[i].focus(); return; }
          }
          return;
        }
        for (let i = 0; i < pinInputs.length; i++) {
          if (!pinInputs[i].value) {
            pinInputs[i].value = k;
            if (pinInputs[i + 1]) pinInputs[i + 1].focus();
            if (currentPin().length === 4) checkPin();
            return;
          }
        }
      });
    });

    // Lock button in header
    lockBtn.addEventListener('click', function () {
      lockBtn.innerHTML = '<i class="ph-fill ph-lock text-xl"></i>';
      openGate();
    });
    if (lockNowBtn) lockNowBtn.addEventListener('click', openGate);

    // Initial: show gate once per session
    const verified = sessionStorage.getItem('kidzio.parentVerified') === '1';
    if (!verified) {
      openGate();
      // also update lockBtn on success
      const origClose = closeGate;
      window.__pinOnSuccess = function () {
        try { sessionStorage.setItem('kidzio.parentVerified', '1'); } catch (e) {}
        lockBtn.innerHTML = '<i class="ph ph-lock-simple-open text-xl"></i>';
      };
    }
    // chain: after closeGate, mark session
    const oldCheckPin = checkPin;
    // wrap: add session flag after unlock
    pinInputs.forEach(function (inp) {
      inp.addEventListener('input', function () {
        if (currentPin() === PIN) {
          try { sessionStorage.setItem('kidzio.parentVerified', '1'); } catch (e) {}
          lockBtn.innerHTML = '<i class="ph ph-lock-simple-open text-xl"></i>';
        }
      });
    });

    // ---------- HERO COUNTER ANIMATION ----------
    function animate(el, target, duration) {
      const start = performance.now();
      (function step(now) {
        const t = Math.min(1, (now - start) / duration);
        const ease = 1 - Math.pow(1 - t, 3);
        el.textContent = Math.round(target * ease).toLocaleString();
        if (t < 1) requestAnimationFrame(step);
      })(start);
    }
    animate(document.getElementById('xpVal'), 940, 900);
    animate(document.getElementById('minsVal'), 365, 900);
    animate(document.getElementById('lessonsVal'), 18, 900);

    // ---------- WEEK BARS ANIMATION ----------
    const bars = document.querySelectorAll('[data-bar]');
    bars.forEach(function (bar, i) {
      bar.style.height = '0%';
      setTimeout(function () {
        bar.style.height = bar.getAttribute('data-bar') + '%';
      }, 250 + i * 80);
    });

    // ---------- TOGGLES ----------
    document.querySelectorAll('[data-toggle]').forEach(function (t) {
      t.addEventListener('change', function () {
        toast(t.getAttribute('data-toggle') + ': ' + (t.checked ? 'on' : 'off'));
      });
    });

    // ---------- DANGER ----------
    const deleteBtn = document.getElementById('deleteBtn');
    if (deleteBtn) {
      deleteBtn.addEventListener('click', function () {
        if (confirm('Delete the account? All data removed within 24h. This cannot be undone.')) {
          toast('Deletion request sent');
          setTimeout(function () { location.href = 'splash.html'; }, 900);
        }
      });
    }
  })();
