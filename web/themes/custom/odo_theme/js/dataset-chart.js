/**
 * @file
 * Renders a dataset's yearly series as an accessible SVG line chart.
 *
 * Reads JSON ({"1990": 123, ...}) from the data-series attribute. The same
 * values are always available in the <table> rendered by Twig, so the chart
 * is a progressive enhancement and is hidden from screen readers in favour
 * of the table (aria-hidden on the plot, with a text summary as the label).
 */
((Drupal, once) => {
  const W = 640;
  const H = 280;
  const PAD = { top: 16, right: 16, bottom: 32, left: 64 };
  const NS = 'http://www.w3.org/2000/svg';

  const format = new Intl.NumberFormat(undefined, { maximumFractionDigits: 1, notation: 'compact' });

  function el(name, attrs, text) {
    const node = document.createElementNS(NS, name);
    Object.entries(attrs).forEach(([k, v]) => node.setAttribute(k, v));
    if (text !== undefined) {
      node.textContent = text;
    }
    return node;
  }

  function render(container) {
    let series;
    try {
      series = JSON.parse(container.dataset.series || '{}');
    } catch (e) {
      return;
    }
    const points = Object.entries(series)
      .map(([year, value]) => [Number(year), Number(value)])
      .filter(([year, value]) => Number.isFinite(year) && Number.isFinite(value))
      .sort((a, b) => a[0] - b[0]);
    if (points.length < 2) {
      return;
    }

    const years = points.map((p) => p[0]);
    const values = points.map((p) => p[1]);
    const [x0, x1] = [Math.min(...years), Math.max(...years)];
    const [vMin, vMax] = [Math.min(...values), Math.max(...values)];
    const y0 = vMin >= 0 && vMin / (vMax || 1) < 0.5 ? 0 : vMin;
    const y1 = vMax === y0 ? y0 + 1 : vMax;
    const x = (year) => PAD.left + ((year - x0) / (x1 - x0)) * (W - PAD.left - PAD.right);
    const y = (v) => H - PAD.bottom - ((v - y0) / (y1 - y0)) * (H - PAD.top - PAD.bottom);

    const first = points[0];
    const last = points[points.length - 1];
    const summary = Drupal.t('Line chart from @y0 to @y1. Value went from @v0 to @v1. Full figures are in the data table below.', {
      '@y0': first[0], '@y1': last[0], '@v0': format.format(first[1]), '@v1': format.format(last[1]),
    });

    const svg = el('svg', { viewBox: `0 0 ${W} ${H}`, role: 'img', 'aria-label': summary, focusable: 'false' });
    svg.append(el('line', { class: 'odo-chart__axis', x1: PAD.left, y1: H - PAD.bottom, x2: W - PAD.right, y2: H - PAD.bottom }));
    svg.append(el('line', { class: 'odo-chart__axis', x1: PAD.left, y1: PAD.top, x2: PAD.left, y2: H - PAD.bottom }));

    [y0, (y0 + y1) / 2, y1].forEach((v) => {
      svg.append(el('text', { class: 'odo-chart__label', x: PAD.left - 8, y: y(v) + 4, 'text-anchor': 'end' }, format.format(v)));
    });
    [x0, Math.round((x0 + x1) / 2), x1].forEach((yr) => {
      svg.append(el('text', { class: 'odo-chart__label', x: x(yr), y: H - 10, 'text-anchor': 'middle' }, String(yr)));
    });

    const d = points.map(([yr, v], i) => `${i ? 'L' : 'M'}${x(yr).toFixed(1)},${y(v).toFixed(1)}`).join(' ');
    svg.append(el('path', { class: 'odo-chart__line', d }));
    svg.append(el('circle', { class: 'odo-chart__point', cx: x(last[0]), cy: y(last[1]), r: 4 }));

    container.querySelector('.odo-chart__plot').append(svg);
  }

  Drupal.behaviors.odoDatasetChart = {
    attach(context) {
      once('odo-chart', '.odo-chart[data-series]', context).forEach(render);
    },
  };
})(Drupal, once);
