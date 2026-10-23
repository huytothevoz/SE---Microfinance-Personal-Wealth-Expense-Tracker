/**
 * WealthFlow Financial Reports & Reconciliation Controller
 * Handles 6-month cash flow charts, saving rates, ledger reconciliation, and CSV exports.
 */
import { loadView } from './view-loader.js';
import { incomeTrend, expenseTrend, wallets } from '../data/live-data.js?v=wealth-ui-2';
import { money } from './ui-core.js';
import { lineChart } from './analytics.js';

export function reportBarChart() { return '<p>Biểu đồ chỉ hiển thị khi có API báo cáo xác thực.</p>'; }

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
        <td>Chưa đối soát</td>
        <td><span class="badge neutral">Chưa xác minh</span></td>
      </tr>
    `).join('');
  }

  return temp.innerHTML;
}
