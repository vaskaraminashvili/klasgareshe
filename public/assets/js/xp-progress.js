// Page script for xp-progress — reads live series from #line[data-points].
(function () {
  var el = document.getElementById('line');
  if (!el || typeof KCharts === 'undefined') return;
  var points = [];
  try {
    points = JSON.parse(el.getAttribute('data-points') || '[]');
  } catch (e) {
    points = [];
  }
  if (!points.length) {
    points = [
      { label: 'M', value: 0 },
      { label: 'T', value: 0 },
      { label: 'W', value: 0 },
      { label: 'T', value: 0 },
      { label: 'F', value: 0 },
      { label: 'S', value: 0 },
      { label: 'S', value: 0 }
    ];
  }
  KCharts.line(el, points);
})();
