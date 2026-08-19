// Page script for edit-profile.html — extracted from inline <script>.
// Loaded via <script src="./assets/js/edit-profile.js"></script>.

/* ═══════════════════════════════════════════════════════════════════
 *  Page script: edit-profile.html
 * 
 *  TABLE OF CONTENTS
 *  ─────────────────
 *    Name feedback .................. line   80
 *    Age feedback ................... line   87
 *    Live hero preview .............. line  109
 *    Age ↔ Birthday two-way sync .... line  113
 *    avatar sheet sync .............. line  171
 *    Dirty tracking ................. line  191
 *    Danger zone with confirm ....... line  235
 *    Esc close avatar sheet ......... line  248
 *    Initial paint .................. line  255
 * ═══════════════════════════════════════════════════════════════════ */
  (function () {
    const name = document.getElementById('kidName');
    const nickname = document.getElementById('nickname');
    const age = document.getElementById('age');
    const birthday = document.getElementById('birthday');
    const grade = document.getElementById('grade');
    const parentEmail = document.getElementById('parentEmail');
    const emailHint = document.getElementById('emailHint');
    const nameHint = document.getElementById('nameHint');
    const saveBtn = document.getElementById('saveBtn');
    const cancelBtn = document.getElementById('cancelBtn');
    const backLink = document.getElementById('backLink');
    const resetBtn = document.getElementById('resetBtn');
    const deleteBtn = document.getElementById('deleteBtn');
    const hero = {
      avatar: document.getElementById('heroAvatar'),
      name: document.getElementById('heroName'),
      nickname: document.getElementById('heroNickname'),
      meta: document.getElementById('heroMeta')
    };

    const initial = {
      name: name.value,
      nickname: nickname.value,
      age: age.value,
      birthday: birthday.value,
      grade: grade.value,
      email: parentEmail.value,
      avatar: '🐻',
      pronoun: 'prefer',
      subject: 'Math',
      goal: '10'
    };
    let currentAvatar = initial.avatar;
    let currentPronoun = initial.pronoun;
    let currentSubject = initial.subject;
    let currentGoal = initial.goal;

    const emailRe = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    function refreshMeta() {
      const ageVal = age.value || '—';
      const gradeVal = grade.options[grade.selectedIndex]?.text || '';
      hero.meta.textContent = 'Age ' + ageVal + ' · ' + gradeVal + ' · Loves ' + currentSubject;
    }
    function refreshHeroName() {
      hero.name.textContent = (name.value || '').trim() || 'Your kid';
    }
    function refreshHeroNickname() {
      const v = (nickname.value || '').trim();
      hero.nickname.textContent = v ? '@' + v : '';
      hero.nickname.classList.toggle('hidden', !v);
    }

    function validate() {
      const nameOk = (name.value || '').trim().length >= 2;
      const ageNum = parseInt(age.value, 10);
      const ageOk = !isNaN(ageNum) && ageNum >= 3 && ageNum <= 14;
      const emailOk = emailRe.test((parentEmail.value || '').trim());

      // Name feedback
      nameHint.innerHTML = nameOk
        ? '<i class="ph ph-info"></i> Shown on badges &amp; reports only — never public.'
        : '<i class="ph-fill ph-warning-circle text-coral-ink"></i> Name must be at least 2 letters.';
      nameHint.classList.toggle('text-coral-ink', !nameOk);
      nameHint.classList.toggle('text-muted', nameOk);

      // Age feedback
      if (!isNaN(ageNum)) {
        if (ageNum < 3) age.value = 3;
        if (ageNum > 14) age.value = 14;
      }

      // Email feedback (and re-verify state)
      const emailChanged = parentEmail.value !== initial.email;
      if (!emailOk) {
        emailHint.innerHTML = '<i class="ph-fill ph-warning-circle text-coral-ink"></i> <span>Enter a valid email like name@example.com</span>';
      } else if (emailChanged) {
        emailHint.innerHTML = '<i class="ph-fill ph-info text-sun-ink"></i> <span>New email — we\u2019ll send a verification link after save.</span>';
      } else {
        emailHint.innerHTML = '<i class="ph-fill ph-check-circle text-mint-ink"></i> <span>Verified · used only for weekly reports.</span>';
      }

      const valid = nameOk && ageOk && emailOk;
      saveBtn.disabled = !valid;
      saveBtn.classList.toggle('opacity-50', !valid);
      return valid;
    }

    // Live hero preview
    name.addEventListener('input', function () { refreshHeroName(); validate(); });
    nickname.addEventListener('input', refreshHeroNickname);

    // Age ↔ Birthday two-way sync
    function ageFromBirthday(d) {
      if (!d) return NaN;
      const bd = new Date(d);
      if (isNaN(bd)) return NaN;
      const now = new Date();
      let a = now.getFullYear() - bd.getFullYear();
      const m = now.getMonth() - bd.getMonth();
      if (m < 0 || (m === 0 && now.getDate() < bd.getDate())) a--;
      return a;
    }
    function birthdayFromAge(a) {
      const now = new Date();
      const y = now.getFullYear() - a;
      return y + '-' + String(now.getMonth() + 1).padStart(2, '0') + '-' + String(now.getDate()).padStart(2, '0');
    }
    birthday.addEventListener('change', function () {
      const a = ageFromBirthday(birthday.value);
      if (!isNaN(a) && a >= 0 && a <= 18) {
        age.value = Math.max(3, Math.min(14, a));
        refreshMeta(); validate();
      }
    });
    age.addEventListener('input', function () {
      const n = parseInt(age.value, 10);
      if (!isNaN(n) && n >= 3 && n <= 14) {
        birthday.value = birthdayFromAge(n);
      }
      refreshMeta(); validate();
    });
    grade.addEventListener('change', refreshMeta);
    parentEmail.addEventListener('input', validate);

    function wireSingleSelect(containerId, attr, onPick) {
      const container = document.getElementById(containerId);
      if (!container) return;
      const items = container.querySelectorAll('[' + attr + ']');
      items.forEach(function (it) {
        it.addEventListener('click', function () {
          items.forEach(function (o) { o.classList.remove('is-selected'); });
          it.classList.add('is-selected');
          if (onPick) onPick(it.getAttribute(attr));
        });
      });
    }

    wireSingleSelect('avatarPicker', 'data-avatar', function (v) {
      currentAvatar = v;
      hero.avatar.textContent = v;
      syncSheetPick(v);
    });
    wireSingleSelect('pronounPicker', 'data-pronoun', function (v) { currentPronoun = v; });
    wireSingleSelect('subjectPicker', 'data-subject', function (v) {
      currentSubject = v;
      refreshMeta();
    });
    wireSingleSelect('goalPicker', 'data-goal', function (v) { currentGoal = v; });

    // avatar sheet sync
    const sheetItems = document.querySelectorAll('#avatarAll [data-avatar-all]');
    function syncSheetPick(v) {
      sheetItems.forEach(function (o) {
        o.classList.toggle('is-selected', o.getAttribute('data-avatar-all') === v);
      });
    }
    syncSheetPick(currentAvatar);
    sheetItems.forEach(function (it) {
      it.addEventListener('click', function () {
        const v = it.getAttribute('data-avatar-all');
        currentAvatar = v;
        hero.avatar.textContent = v;
        syncSheetPick(v);
        document.querySelectorAll('#avatarPicker [data-avatar]').forEach(function (o) {
          o.classList.toggle('is-selected', o.getAttribute('data-avatar') === v);
        });
      });
    });

    // Dirty tracking
    function isDirty() {
      return name.value !== initial.name
        || nickname.value !== initial.nickname
        || String(age.value) !== String(initial.age)
        || birthday.value !== initial.birthday
        || grade.value !== initial.grade
        || parentEmail.value !== initial.email
        || currentAvatar !== initial.avatar
        || currentPronoun !== initial.pronoun
        || currentSubject !== initial.subject
        || currentGoal !== initial.goal;
    }
    let saving = false;
    function leaveGuard(nextUrl) {
      if (!saving && isDirty()) {
        if (!confirm('You have unsaved changes. Leave without saving?')) return false;
      }
      location.href = nextUrl;
      return true;
    }
    cancelBtn.addEventListener('click', function () { leaveGuard('profile.html'); });
    backLink.addEventListener('click', function (e) {
      e.preventDefault();
      leaveGuard('profile.html');
    });
    window.addEventListener('beforeunload', function (e) {
      if (saving || !isDirty()) return;
      e.preventDefault();
      e.returnValue = '';
    });

    // Save
    saveBtn.addEventListener('click', function () {
      if (!validate()) {
        toast('Please fix the highlighted fields');
        return;
      }
      saving = true;
      saveBtn.disabled = true;
      toast('Profile saved!');
      setTimeout(function () { location.href = 'profile.html'; }, 800);
    });

    // Danger zone with confirm
    resetBtn.addEventListener('click', function () {
      if (confirm('Reset XP, streak and all learning progress? Avatar and name stay.')) {
        toast('Progress reset scheduled');
      }
    });
    deleteBtn.addEventListener('click', function () {
      if (confirm('Delete this kid profile? This removes all data within 24h and cannot be undone.')) {
        toast('Deletion request sent');
        setTimeout(function () { location.href = 'profile.html'; }, 900);
      }
    });

    // Esc close avatar sheet
    document.addEventListener('keydown', function (e) {
      if (e.key !== 'Escape') return;
      const sheet = document.getElementById('avatarSheet');
      if (sheet && !sheet.classList.contains('hidden')) sheet.classList.add('hidden');
    });

    // Initial paint
    refreshHeroName();
    refreshHeroNickname();
    refreshMeta();
    validate();
  })();
