/* Kidzio — lightweight pure-SVG charts (no external libs)
 * Loaded as a plain <script src> where needed. Exposes window.KCharts.
 *
 * ═══════════════════════════════════════════════════════════════════
 *  TABLE OF CONTENTS
 * ═══════════════════════════════════════════════════════════════════
 *   SVG helpers
 *     · svg(w, h)              ....................... line  24
 *     · el(tag, attrs)         ....................... line  33
 *
 *   Chart renderers (all mutate `host.innerHTML`)
 *     · bars(host, data, opts)    — weekly progress     line  40
 *     · line(host, data, opts)    — XP/points trend     line  64
 *     · donut(host, pct, opts)    — goal progress       line 100
 *     · streak(host, days)        — week activity       line 127
 *
 *   Export:
 *     window.KCharts = { bars, line, donut, streak }  line 148
 * ═══════════════════════════════════════════════════════════════════ */

(function () {
  const ns = 'http://www.w3.org/2000/svg';

  function svg(w, h) {
    const s = document.createElementNS(ns, 'svg');
    s.setAttribute('viewBox', `0 0 ${w} ${h}`);
    s.setAttribute('width', '100%');
    s.setAttribute('height', h);
    s.setAttribute('role', 'img');
    return s;
  }

  function el(tag, attrs = {}) {
    const e = document.createElementNS(ns, tag);
    Object.entries(attrs).forEach(([k, v]) => e.setAttribute(k, v));
    return e;
  }

  // Bars — weekly learning progress
  function bars(host, data, opts = {}) {
    const w = 320, h = 160, pad = 20, gap = 10;
    const max = Math.max(...data.map((d) => d.value), 1);
    const bw = (w - pad * 2 - gap * (data.length - 1)) / data.length;
    const s = svg(w, h);
    data.forEach((d, i) => {
      const bh = ((h - pad * 2 - 16) * d.value) / max;
      const x = pad + i * (bw + gap);
      const y = h - pad - bh - 16;
      const grad = `grad-${i}-${Math.random().toString(36).slice(2, 6)}`;
      const defs = el('defs');
      const lg = el('linearGradient', { id: grad, x1: '0', y1: '0', x2: '0', y2: '1' });
      lg.appendChild(el('stop', { offset: '0%', 'stop-color': opts.color1 || '#8E72FF' }));
      lg.appendChild(el('stop', { offset: '100%', 'stop-color': opts.color2 || '#49B8FF' }));
      defs.appendChild(lg); s.appendChild(defs);
      s.appendChild(el('rect', { x, y, width: bw, height: bh, rx: 8, fill: `url(#${grad})` }));
      const lbl = el('text', { x: x + bw / 2, y: h - 4, 'text-anchor': 'middle', 'font-size': 10, fill: 'currentColor', 'font-weight': 700 });
      lbl.textContent = d.label;
      s.appendChild(lbl);
    });
    host.innerHTML = ''; host.appendChild(s);
  }

  // Line — XP/points trend
  function line(host, data, opts = {}) {
    const w = 320, h = 150, pad = 24;
    const max = Math.max(...data.map((d) => d.value), 1);
    const step = (w - pad * 2) / (data.length - 1);
    const s = svg(w, h);

    // grid
    for (let i = 0; i < 4; i++) {
      const y = pad + ((h - pad * 2) / 3) * i;
      s.appendChild(el('line', { x1: pad, y1: y, x2: w - pad, y2: y, stroke: 'currentColor', 'stroke-opacity': 0.08 }));
    }

    const points = data.map((d, i) => [pad + step * i, h - pad - ((h - pad * 2) * d.value) / max]);
    const path = points.map(([x, y], i) => (i ? `L${x},${y}` : `M${x},${y}`)).join(' ');
    const area = `${path} L${points[points.length - 1][0]},${h - pad} L${points[0][0]},${h - pad} Z`;

    const gid = 'ag-' + Math.random().toString(36).slice(2, 6);
    const defs = el('defs');
    const lg = el('linearGradient', { id: gid, x1: '0', y1: '0', x2: '0', y2: '1' });
    lg.appendChild(el('stop', { offset: '0%', 'stop-color': opts.color || '#7C5CFF', 'stop-opacity': '0.35' }));
    lg.appendChild(el('stop', { offset: '100%', 'stop-color': opts.color || '#7C5CFF', 'stop-opacity': '0' }));
    defs.appendChild(lg); s.appendChild(defs);

    s.appendChild(el('path', { d: area, fill: `url(#${gid})` }));
    s.appendChild(el('path', { d: path, fill: 'none', stroke: opts.color || '#7C5CFF', 'stroke-width': 3, 'stroke-linecap': 'round', 'stroke-linejoin': 'round' }));

    points.forEach(([x, y], i) => {
      s.appendChild(el('circle', { cx: x, cy: y, r: 4, fill: '#fff', stroke: opts.color || '#7C5CFF', 'stroke-width': 2 }));
      const lbl = el('text', { x, y: h - 6, 'text-anchor': 'middle', 'font-size': 10, fill: 'currentColor', 'font-weight': 700 });
      lbl.textContent = data[i].label;
      s.appendChild(lbl);
    });
    host.innerHTML = ''; host.appendChild(s);
  }

  // Donut — monthly goal progress
  function donut(host, pct, opts = {}) {
    const size = 160, r = 62, cx = size / 2, cy = size / 2, c = 2 * Math.PI * r;
    const s = svg(size, size);
    s.appendChild(el('circle', { cx, cy, r, fill: 'none', stroke: 'currentColor', 'stroke-opacity': 0.12, 'stroke-width': 14 }));
    const gid = 'dg-' + Math.random().toString(36).slice(2, 6);
    const defs = el('defs');
    const lg = el('linearGradient', { id: gid, x1: '0', y1: '0', x2: '1', y2: '1' });
    lg.appendChild(el('stop', { offset: '0%', 'stop-color': opts.color1 || '#4ED6A8' }));
    lg.appendChild(el('stop', { offset: '100%', 'stop-color': opts.color2 || '#49B8FF' }));
    defs.appendChild(lg); s.appendChild(defs);
    const arc = el('circle', {
      cx, cy, r, fill: 'none', stroke: `url(#${gid})`,
      'stroke-width': 14, 'stroke-linecap': 'round',
      'stroke-dasharray': `${(c * pct) / 100} ${c}`,
      transform: `rotate(-90 ${cx} ${cy})`,
    });
    s.appendChild(arc);
    const pctEl = el('text', { x: cx, y: cy + 2, 'text-anchor': 'middle', 'dominant-baseline': 'middle', 'font-size': 28, fill: 'currentColor', 'font-weight': 800 });
    pctEl.textContent = `${pct}%`;
    s.appendChild(pctEl);
    const lbl = el('text', { x: cx, y: cy + 24, 'text-anchor': 'middle', 'font-size': 11, fill: 'currentColor', 'font-weight': 600, 'fill-opacity': '0.6' });
    lbl.textContent = opts.label || 'Complete';
    s.appendChild(lbl);
    host.innerHTML = ''; host.appendChild(s);
  }

  // Streak dots — activity calendar for a week
  function streak(host, days) {
    const w = 320, h = 60, r = 16, gap = (w - r * 2 * days.length) / (days.length + 1);
    const s = svg(w, h);
    days.forEach((d, i) => {
      const cx = gap + r + i * (r * 2 + gap);
      const cy = h / 2;
      const color = d.done ? '#FFB547' : 'currentColor';
      const opacity = d.done ? 1 : 0.15;
      s.appendChild(el('circle', { cx, cy, r, fill: color, 'fill-opacity': opacity }));
      if (d.done) {
        const flame = el('text', { x: cx, y: cy + 5, 'text-anchor': 'middle', 'font-size': 18 });
        flame.textContent = '🔥';
        s.appendChild(flame);
      }
      const lbl = el('text', { x: cx, y: cy + 34, 'text-anchor': 'middle', 'font-size': 10, fill: 'currentColor', 'font-weight': 700, 'fill-opacity': 0.7 });
      lbl.textContent = d.label;
      s.appendChild(lbl);
    });
    host.innerHTML = ''; host.appendChild(s);
  }

  window.KCharts = { bars, line, donut, streak };
})();
