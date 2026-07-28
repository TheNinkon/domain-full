/**
 * Dashboard Analytics — distribución de dominios por estado y categoría
 */

'use strict';

(function () {
  const colorMap = config.colors;
  const palette = [colorMap.primary, colorMap.info, colorMap.success, colorMap.warning, colorMap.danger, colorMap.secondary];

  function renderDonut(el, labels, series, colors) {
    if (!el || series.length === 0) return;

    new ApexCharts(el, {
      chart: { type: 'donut', height: 300 },
      labels: labels,
      series: series,
      colors: colors,
      legend: { position: 'bottom', labels: { colors: colorMap.headingColor } },
      stroke: { colors: [colorMap.cardColor] },
      dataLabels: { enabled: true }
    }).render();
  }

  const statusEl = document.querySelector('#statusDistributionChart');
  if (statusEl) {
    const colors = JSON.parse(statusEl.dataset.colors).map((name) => colorMap[name] || colorMap.secondary);
    renderDonut(statusEl, JSON.parse(statusEl.dataset.labels), JSON.parse(statusEl.dataset.series), colors);
  }

  const categoryEl = document.querySelector('#categoryDistributionChart');
  if (categoryEl) {
    const series = JSON.parse(categoryEl.dataset.series);
    const colors = series.map((_, index) => palette[index % palette.length]);
    renderDonut(categoryEl, JSON.parse(categoryEl.dataset.labels), series, colors);
  }
})();
