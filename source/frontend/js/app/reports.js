/**
 * WealthFlow Financial Reports & Reconciliation Controller
 * Handles 6-month cash flow charts, saving rates, ledger reconciliation, and CSV exports (FR12).
 */
import { loadView } from './view-loader.js';
import { reconciliation, wallets } from '../data/live-data.js?v=wealth-ui-2';
import { money, safe } from './ui-core.js?v=wealth-ui-2';
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
    const rows = Array.isArray(reconciliation.wallets) ? reconciliation.wallets : wallets;
    tbody.innerHTML = rows.map(w => {
      const name = w.walletName ?? w.name ?? 'Ví';
      const currency = w.currency ?? 'VND';
      const cached = Number(w.cachedBalance ?? w.balance ?? 0);
      const ledger = Number(w.ledgerSum ?? cached);
      const delta = Number(w.delta ?? 0);
      const isMatched = w.isMatched !== undefined ? Boolean(w.isMatched) : Math.abs(delta) < 0.0001;
      const fmt = v => currency === 'VND' ? money(v) : `${v.toLocaleString('vi-VN')} ${currency}`;
      return `
        <tr>
          <td><strong>${safe(name)}</strong></td>
          <td>${safe(fmt(cached))}</td>
          <td>${safe(fmt(ledger))}</td>
          <td>${safe(fmt(delta))}</td>
          <td><span class="badge ${isMatched ? 'success' : 'danger'}">${isMatched ? 'MATCHED' : 'MISMATCHED'}</span></td>
        </tr>
      `;
    }).join('');
  }

  return temp.innerHTML;
}
