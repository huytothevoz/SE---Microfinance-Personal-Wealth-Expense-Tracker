/**
 * WealthFlow Financial Reports & Reconciliation Controller
 * Handles 6-month cash flow charts, saving rates, ledger reconciliation, and CSV exports.
 */
import { loadView } from './view-loader.js';
import { incomeTrend, expenseTrend, wallets } from '../data/fixtures.js';
import { money } from './ui-core.js';
import { lineChart } from './analytics.js';

export function reportBarChart() {
  const max = 20;
  return `
    <div class="chart-bars">
      ${incomeTrend.map((v, i) => `
        <div class="chart-bar-group">
          <div class="chart-bar" style="height: ${(v / max) * 100}%" title="Thu nhập: ${v} triệu"></div>
          <div class="chart-bar out" style="height: ${(expenseTrend[i] / max) * 100}%" title="Chi tiêu: ${expenseTrend[i]} triệu"></div>
        </div>
      `).join('')}
    </div>
    <div class="chart-bars-labels">
      ${['Thg 5', 'Thg 6', 'Thg 7', 'Thg 8', 'Thg 9', 'Thg 10'].map(m => `<span>${m}</span>`).join('')}
    </div>
    <div class="chart-summary">
      <span class="legend">Thu nhập</span>
      <span class="legend secondary">Chi tiêu</span>
      <span>Đơn vị: triệu VND</span>
    </div>
  `;
}

export async function renderReportPage() {
  const rawHtml = await loadView('reports');
  const temp = document.createElement('div');
  temp.innerHTML = rawHtml;

  const barChartContainer = temp.querySelector('#report-cashflow-bars');
  if (barChartContainer) {
    barChartContainer.innerHTML = reportBarChart();
  }

  const networthChartContainer = temp.querySelector('#report-networth-chart');
  if (networthChartContainer) {
    networthChartContainer.innerHTML = lineChart();
  }

  return temp.innerHTML;
}

export async function renderReconciliationPage() {
  const rawHtml = await loadView('reconciliation');
  const temp = document.createElement('div');
  temp.innerHTML = rawHtml;

  const tbody = temp.querySelector('#recon-table-body');
  if (tbody) {
    tbody.innerHTML = wallets.filter(w => w.active).map(w => `
      <tr>
        <td><strong>${w.name}</strong></td>
        <td>${w.currency === 'USD' ? w.balance + ' USD' : money(w.balance)}</td>
        <td>${w.currency === 'USD' ? w.balance + ' USD' : money(w.balance)}</td>
        <td>0 ${w.currency}</td>
        <td><span class="badge">Mô phỏng khớp</span></td>
      </tr>
    `).join('');
  }

  return temp.innerHTML;
}
