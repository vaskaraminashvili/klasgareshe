// Page script for lesson-locked.html — extracted from inline <script>.
// Loaded via <script src="./assets/js/lesson-locked.js"></script>.

/* ═══════════════════════════════════════════════════════════════════
 *  Page script: lesson-locked.html
 * 
 *  TABLE OF CONTENTS
 *  ─────────────────
 *    Counter + progress animation ........................ line   19
 *    Sheet helpers ....................................... line   53
 *    PREVIEW SHEET ....................................... line   74
 *    XP SHEET ............................................ line   93
 *    NOTIFY CHIP ......................................... line  107
 *    DEMO UNLOCK (keyboard shortcut for QA / designe… .... line  120
 *    Esc close ........................................... line  146
 * ═══════════════════════════════════════════════════════════════════ */
  (function () {
    // ---------- Counter + progress animation ----------
    function animate(el, target, duration) {
      const start = performance.now();
      const from = parseInt((el.textContent || '').toString().replace(/\D/g, ''), 10) || 0;
      (function step(now) {
        const t = Math.min(1, (now - start) / duration);
        const ease = 1 - Math.pow(1 - t, 3);
        el.textContent = Math.round(from + (target - from) * ease);
        if (t < 1) requestAnimationFrame(step);
      })(start);
    }
    animate(document.getElementById('doneVal'), 2, 700);
    animate(document.getElementById('xpVal'), 320, 1000);
    animate(document.getElementById('unlockPct'), 55, 1000);
    const fill = document.getElementById('unlockFill');
    setTimeout(function () {
      fill.classList.remove('w-0');
      fill.classList.add('w-55');
    }, 250);

    // Row cascade
    document.querySelectorAll('[data-req]').forEach(function (r, i) {
      r.classList.add('opacity-0', 'translate-y-1', 'transition-all', 'duration-300');
      setTimeout(function () { r.classList.remove('opacity-0', 'translate-y-1'); }, 300 + i * 90);
    });

    // Lock-icon subtle pulse
    const lockBadge = document.querySelector('.ph-fill.ph-lock-simple');
    if (lockBadge && lockBadge.parentElement) {
      lockBadge.parentElement.animate([
        { transform: 'scale(1)' }, { transform: 'scale(1.08)' }, { transform: 'scale(1)' }
      ], { duration: 1400, iterations: Infinity });
    }

    // ---------- Sheet helpers ----------
    function openSheet(overlay, panel, backdrop, onOpen) {
      overlay.classList.remove('hidden');
      document.body.style.overflow = 'hidden';
      requestAnimationFrame(function () {
        backdrop.classList.remove('opacity-0');
        backdrop.classList.add('opacity-100');
        panel.classList.remove('translate-y-full');
      });
      if (onOpen) onOpen();
    }
    function closeSheet(overlay, panel, backdrop) {
      backdrop.classList.remove('opacity-100');
      backdrop.classList.add('opacity-0');
      panel.classList.add('translate-y-full');
      setTimeout(function () {
        overlay.classList.add('hidden');
        document.body.style.overflow = '';
      }, 280);
    }

    // ---------- PREVIEW SHEET ----------
    const previewSheet = document.getElementById('previewSheet');
    const previewPanel = document.getElementById('previewPanel');
    const previewBackdrop = document.getElementById('previewBackdrop');
    const previewClose = document.getElementById('previewClose');
    const previewBtn = document.getElementById('previewBtn');
    const previewBtn2 = document.getElementById('previewBtn2');
    const previewToCurrent = document.getElementById('previewToCurrent');
    function openPreview() { openSheet(previewSheet, previewPanel, previewBackdrop); }
    function closePreview() { closeSheet(previewSheet, previewPanel, previewBackdrop); }
    previewBtn.addEventListener('click', openPreview);
    previewBtn2.addEventListener('click', openPreview);
    previewClose.addEventListener('click', closePreview);
    previewBackdrop.addEventListener('click', closePreview);
    previewToCurrent.addEventListener('click', function () {
      closePreview();
      setTimeout(function () { location.href = 'lesson-continue.html'; }, 300);
    });

    // ---------- XP SHEET ----------
    const xpSheet = document.getElementById('xpSheet');
    const xpPanel = document.getElementById('xpPanel');
    const xpBackdrop = document.getElementById('xpBackdrop');
    const xpClose = document.getElementById('xpClose');
    const xpCancel = document.getElementById('xpCancel');
    const xpReqBtn = document.getElementById('xpReqBtn');
    function openXp() { openSheet(xpSheet, xpPanel, xpBackdrop); }
    function closeXp() { closeSheet(xpSheet, xpPanel, xpBackdrop); }
    xpReqBtn.addEventListener('click', openXp);
    xpClose.addEventListener('click', closeXp);
    xpBackdrop.addEventListener('click', closeXp);
    xpCancel.addEventListener('click', closeXp);

    // ---------- NOTIFY CHIP ----------
    const notifyBtn = document.getElementById('notifyBtn');
    let notifyOn = false;
    notifyBtn.addEventListener('click', function () {
      notifyOn = !notifyOn;
      notifyBtn.classList.toggle('chip-primary', !notifyOn);
      notifyBtn.classList.toggle('chip-mint', notifyOn);
      notifyBtn.innerHTML = notifyOn
        ? '<i class="ph-fill ph-bell-ringing"></i> Notifying'
        : '<i class="ph ph-bell"></i> Notify';
      toast(notifyOn ? 'We\u2019ll ping when it unlocks' : 'Notifications off');
    });

    // ---------- DEMO UNLOCK (keyboard shortcut for QA / designers) ----------
    // Press "u" to preview the unlock celebration.
    const unlockOverlay = document.getElementById('unlockOverlay');
    const unlockBg = document.getElementById('unlockBg');
    const unlockCard = document.getElementById('unlockCard');
    function triggerUnlock() {
      unlockOverlay.classList.remove('hidden');
      requestAnimationFrame(function () {
        unlockBg.classList.remove('opacity-0');
        unlockBg.classList.add('opacity-100');
        unlockCard.classList.remove('opacity-0', 'scale-90');
        unlockCard.classList.add('opacity-100', 'scale-100');
      });
      unlockCard.animate([
        { transform: 'scale(0.9) rotate(-4deg)' },
        { transform: 'scale(1.06) rotate(2deg)' },
        { transform: 'scale(1) rotate(0)' }
      ], { duration: 550, easing: 'cubic-bezier(.34,1.56,.64,1)' });
    }
    function closeUnlock() {
      unlockBg.classList.add('opacity-0');
      unlockCard.classList.add('opacity-0', 'scale-90');
      setTimeout(function () { unlockOverlay.classList.add('hidden'); }, 280);
    }
    unlockBg.addEventListener('click', closeUnlock);

    // ---------- Esc close ----------
    document.addEventListener('keydown', function (e) {
      if (e.key === 'u' && e.target === document.body) triggerUnlock();
      if (e.key !== 'Escape') return;
      if (!previewSheet.classList.contains('hidden')) closePreview();
      else if (!xpSheet.classList.contains('hidden')) closeXp();
      else if (!unlockOverlay.classList.contains('hidden')) closeUnlock();
    });
  })();
