// Page script for change-pin.html — extracted from inline <script>.
// Loaded via <script src="./assets/js/change-pin.js"></script>.

  (function () {
    const CURRENT_PIN = '1234';
    const inputs = Array.from(document.querySelectorAll('#pinGroup input'));
    const pad = document.querySelectorAll('#pinPad [data-pin]');
    const nextBtn = document.getElementById('nextBtn');
    const nextLabel = document.getElementById('nextLabel');
    const hint = document.getElementById('pinHint');
    const heroTitle = document.getElementById('heroTitle');
    const heroSub = document.getElementById('heroSub');
    const stepLabel = document.getElementById('stepLabel');
    const strengthWrap = document.getElementById('strengthWrap');
    const strengthFill = document.getElementById('strengthFill');
    const strengthLabel = document.getElementById('strengthLabel');
    const stepBars = document.querySelectorAll('[data-step]');

    const steps = [
      { title: 'Confirm current PIN',  sub: 'Enter your current 4-digit PIN to continue.',    hint: 'Hint: current PIN is <b class="text-ink">1234</b> in this demo.', strength: false },
      { title: 'Choose a new PIN',     sub: 'Pick 4 digits only you will remember.',          hint: 'Avoid 1234, 0000, birthdays, or repeating numbers.', strength: true },
      { title: 'Confirm new PIN',      sub: 'Re-enter your new PIN one more time.',           hint: 'Must match the PIN you just entered.', strength: true }
    ];

    let step = 0;
    let newPin = '';

    function paintStep() {
      const s = steps[step];
      heroTitle.textContent = s.title;
      heroSub.textContent = s.sub;
      hint.innerHTML = s.hint;
      hint.classList.remove('text-coral-ink', 'text-mint-ink'); hint.classList.add('text-muted');
      strengthWrap.classList.toggle('hidden', !s.strength);
      stepLabel.textContent = 'Step ' + (step + 1) + ' of 3 · ' + s.title;
      stepBars.forEach(function (b, i) {
        const active = i <= step;
        b.classList.toggle('bg-[var(--color-k-primary)]', active);
        b.classList.toggle('bg-[var(--color-k-border)]', !active);
      });
      nextLabel.textContent = step === 2 ? 'Save new PIN' : 'Continue';
      inputs.forEach(function (i) { i.value = ''; });
      inputs[0].focus();
      syncBtn();
      if (s.strength) rateStrength('');
    }

    function code() { return inputs.map(function (i) { return i.value; }).join(''); }
    function syncBtn() {
      const full = code().length === 4;
      nextBtn.disabled = !full;
      nextBtn.classList.toggle('opacity-50', !full);
    }

    function rateStrength(p) {
      let score = 0;
      const uniqueOk = p !== '1234' && p !== '0000' && p !== '4321';
      const noSeq = !/^(?:0123|1234|2345|3456|4567|5678|6789|9876|8765|7654|6543|5432|4321|3210)$/.test(p);
      const noRepeat = !/^(\d)\1{3}$/.test(p);
      if (uniqueOk) score++;
      if (noSeq) score++;
      if (noRepeat) score++;

      const fillMap = ['w-10','w-40','w-70','w-100'];
      strengthFill.classList.remove('w-10','w-40','w-70','w-100');
      strengthFill.classList.add(fillMap[score]);
      strengthFill.classList.remove('bg-[var(--color-k-coral)]','bg-[var(--color-k-sun)]','bg-[var(--color-k-mint)]');
      if (score <= 1) strengthFill.classList.add('bg-[var(--color-k-coral)]');
      else if (score === 2) strengthFill.classList.add('bg-[var(--color-k-sun)]');
      else strengthFill.classList.add('bg-[var(--color-k-mint)]');
      strengthLabel.textContent = ['Weak','Fair','Good','Strong'][score];
      strengthLabel.classList.remove('text-coral-ink','text-sun-ink','text-mint-ink','text-muted');
      if (score <= 1) strengthLabel.classList.add('text-coral-ink');
      else if (score === 2) strengthLabel.classList.add('text-sun-ink');
      else strengthLabel.classList.add('text-mint-ink');

      paintRule('unique', uniqueOk);
      paintRule('noseq', noSeq);
      paintRule('norepeat', noRepeat);
    }
    function paintRule(name, ok) {
      const el = document.querySelector('[data-rule="' + name + '"]');
      if (!el) return;
      el.classList.toggle('chip-mint', ok);
      el.classList.toggle('chip-coral', !ok);
      const icon = el.querySelector('i');
      if (icon) { icon.className = ok ? 'ph ph-check' : 'ph ph-x'; }
    }

    function shake() {
      document.getElementById('pinGroup').animate([
        { transform: 'translateX(0)' }, { transform: 'translateX(-8px)' },
        { transform: 'translateX(8px)' }, { transform: 'translateX(-6px)' },
        { transform: 'translateX(6px)' }, { transform: 'translateX(0)' }
      ], { duration: 350, easing: 'ease-out' });
    }

    inputs.forEach(function (inp, idx) {
      inp.addEventListener('input', function () {
        inp.value = (inp.value || '').replace(/\D/g, '').slice(0, 1);
        if (inp.value && inputs[idx + 1]) inputs[idx + 1].focus();
        syncBtn();
        if (steps[step].strength) rateStrength(code());
      });
      inp.addEventListener('keydown', function (e) {
        if (e.key === 'Backspace' && !inp.value && idx > 0) inputs[idx - 1].focus();
      });
    });

    pad.forEach(function (b) {
      b.addEventListener('click', function () {
        const k = b.getAttribute('data-pin');
        if (k === 'back') {
          for (let i = inputs.length - 1; i >= 0; i--) {
            if (inputs[i].value) { inputs[i].value = ''; inputs[i].focus(); syncBtn(); if (steps[step].strength) rateStrength(code()); return; }
          }
          return;
        }
        for (let i = 0; i < inputs.length; i++) {
          if (!inputs[i].value) {
            inputs[i].value = k;
            if (inputs[i + 1]) inputs[i + 1].focus();
            syncBtn();
            if (steps[step].strength) rateStrength(code());
            return;
          }
        }
      });
    });

    nextBtn.addEventListener('click', function () {
      const p = code();
      if (p.length !== 4) return;

      if (step === 0) {
        if (p !== CURRENT_PIN) {
          hint.innerHTML = '<i class="ph-fill ph-warning-circle"></i> Wrong PIN — try again';
          hint.classList.remove('text-muted','text-mint-ink'); hint.classList.add('text-coral-ink');
          shake();
          return;
        }
        step = 1; paintStep();
      } else if (step === 1) {
        if (p === CURRENT_PIN) {
          hint.innerHTML = '<i class="ph-fill ph-warning-circle"></i> Pick a different PIN than your current one';
          hint.classList.remove('text-muted','text-mint-ink'); hint.classList.add('text-coral-ink');
          shake();
          return;
        }
        newPin = p;
        step = 2; paintStep();
      } else if (step === 2) {
        if (p !== newPin) {
          hint.innerHTML = '<i class="ph-fill ph-warning-circle"></i> PINs don\'t match — try step 2 again';
          hint.classList.remove('text-muted','text-mint-ink'); hint.classList.add('text-coral-ink');
          shake();
          return;
        }
        toast('PIN updated successfully ✓');
        try { sessionStorage.setItem('kidzio.parentVerified', '1'); } catch (e) {}
        setTimeout(function () { location.href = 'parent-controls.html'; }, 900);
      }
    });

    paintStep();
  })();
