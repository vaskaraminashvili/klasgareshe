// Page script for about.html — extracted from inline <script>.
// Loaded via <script src="./assets/js/about.js"></script>.

  (function () {
    const stars = document.querySelectorAll('#rateSheet .rate-star');
    const label = document.getElementById('rateLabel');
    const submit = document.getElementById('rateSubmit');
    const sheet = document.getElementById('rateSheet');
    const tags = document.querySelectorAll('#rateTags [data-tag]');
    const names = ['', 'Could be better', 'Not great', 'It\u2019s okay', 'Pretty good!', 'We love it! \ud83d\udc9c'];
    let rating = 0;

    function paint(n) {
      stars.forEach(function (s, i) {
        const active = i < n;
        s.textContent = active ? '\u2605' : '\u2606';
        s.classList.toggle('text-amber-400', active);
        s.classList.toggle('text-muted', !active);
        s.setAttribute('aria-checked', active && i === n - 1 ? 'true' : 'false');
      });
      label.textContent = n ? names[n] : 'Tap a star to rate';
      submit.disabled = n === 0;
    }

    stars.forEach(function (s) {
      s.addEventListener('click', function () {
        rating = parseInt(s.getAttribute('data-star'), 10);
        paint(rating);
      });
    });

    tags.forEach(function (t) {
      t.addEventListener('click', function () {
        t.classList.toggle('chip-primary');
      });
    });

    submit.addEventListener('click', function () {
      if (!rating) return;
      sheet.classList.add('hidden');
      if (rating >= 4) {
        toast('Thanks! Opening App Store \u2b50');
      } else {
        toast('Thanks \u2014 we\u2019ll read every word');
      }
      setTimeout(function () {
        rating = 0;
        paint(0);
        tags.forEach(function (t) { t.classList.remove('chip-primary'); });
      }, 400);
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && !sheet.classList.contains('hidden')) {
        sheet.classList.add('hidden');
      }
    });
  })();

  (function () {
    const shareSheet = document.getElementById('shareSheet');
    const targets = document.querySelectorAll('#shareTargets [data-share]');
    const copyBtn = document.getElementById('copyLink');
    const link = document.getElementById('inviteLink');
    const shareText = 'Check out Kidzio — learning that feels like play! ';
    const shareUrl = 'https://kidzio.app/invite/luna-24';

    const urls = {
      whatsapp: 'https://wa.me/?text=' + encodeURIComponent(shareText + shareUrl),
      messages: 'sms:?body=' + encodeURIComponent(shareText + shareUrl),
      email: 'mailto:?subject=' + encodeURIComponent('Kidzio for kids') + '&body=' + encodeURIComponent(shareText + shareUrl),
      facebook: 'https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(shareUrl),
      x: 'https://x.com/intent/tweet?text=' + encodeURIComponent(shareText) + '&url=' + encodeURIComponent(shareUrl),
      telegram: 'https://t.me/share/url?url=' + encodeURIComponent(shareUrl) + '&text=' + encodeURIComponent(shareText)
    };

    targets.forEach(function (t) {
      t.addEventListener('click', function () {
        const key = t.getAttribute('data-share');
        if (key === 'more' && navigator.share) {
          navigator.share({ title: 'Kidzio', text: shareText, url: shareUrl }).catch(function () {});
          shareSheet.classList.add('hidden');
          return;
        }
        if (key === 'instagram') {
          navigator.clipboard && navigator.clipboard.writeText(shareUrl);
          toast('Link copied \u2014 paste in Instagram');
          shareSheet.classList.add('hidden');
          return;
        }
        if (urls[key]) {
          window.open(urls[key], '_blank', 'noopener');
          shareSheet.classList.add('hidden');
          return;
        }
        toast('Opening share\u2026');
        shareSheet.classList.add('hidden');
      });
    });

    copyBtn.addEventListener('click', function () {
      if (navigator.clipboard) {
        navigator.clipboard.writeText(link.value).then(function () {
          toast('Link copied');
        });
      } else {
        link.select();
        document.execCommand('copy');
        toast('Link copied');
      }
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && !shareSheet.classList.contains('hidden')) {
        shareSheet.classList.add('hidden');
      }
    });
  })();
