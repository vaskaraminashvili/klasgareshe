// Page script for streak — reads live week dots from #streakChart[data-days].
(function () {
  var el = document.getElementById('streakChart');
  if (!el || typeof KCharts === 'undefined') return;
  var days = [];
  try {
    days = JSON.parse(el.getAttribute('data-days') || '[]');
  } catch (e) {
    days = [];
  }
  if (!days.length) {
    days = [
      { label: 'M', done: false },
      { label: 'T', done: false },
      { label: 'W', done: false },
      { label: 'T', done: false },
      { label: 'F', done: false },
      { label: 'S', done: false },
      { label: 'S', done: false }
    ];
  }
  KCharts.streak(el, days);
})();
