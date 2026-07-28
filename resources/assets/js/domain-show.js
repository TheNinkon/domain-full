/**
 * Domain detail page — visits chart (last 30 days)
 */

'use strict';

(function () {
  const el = document.querySelector('#domainVisitsChart');
  if (!el) return;

  const labels = JSON.parse(el.dataset.labels);
  const series = JSON.parse(el.dataset.series);
  const colorMap = config.colors;

  new ApexCharts(el, {
    chart: { type: 'area', height: 260, toolbar: { show: false } },
    series: [{ name: 'Visitas', data: series }],
    xaxis: { categories: labels, labels: { rotate: 0 } },
    colors: [colorMap.primary],
    dataLabels: { enabled: false },
    stroke: { curve: 'smooth', width: 2 },
    fill: {
      type: 'gradient',
      gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.1, stops: [0, 100] }
    },
    grid: { borderColor: colorMap.borderColor }
  }).render();
})();
