/**
 * WealthFlow Financial Reports & Reconciliation Controller
 * Handles 6-month cash flow charts, saving rates, ledger reconciliation, and CSV exports.
 */
import { loadView } from './view-loader.js';
import { money } from './ui-core.js';
import { lineChart } from './analytics.js';
import { ReportApi } from '../services/report-api.js';

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

  try {
    const data = await ReportApi.getReconciliation();
    updateReconciliationDOM(temp, data);
  } catch (err) {
    console.warn('Cannot fetch initial reconciliation data:', err.message);
  }

  return temp.innerHTML;
}

function updateReconciliationDOM(container, data) {
  if (!data || !container) return;

  const trackedWallets = container.querySelector('#recon-tracked-wallets');
  if (trackedWallets) trackedWallets.textContent = `${data.totalWallets ?? 0} ví`;

  const matchedWallets = container.querySelector('#recon-matched-wallets');
  if (matchedWallets) {
    matchedWallets.textContent = `${data.matchedWallets ?? 0} / ${data.totalWallets ?? 0}`;
    matchedWallets.className = 'kpi-number ' + (data.isAllMatched ? 'green' : 'red');
  }

  const postedCount = container.querySelector('#recon-posted-count');
  if (postedCount) postedCount.textContent = `${data.postedCount ?? 0} bản ghi`;

  const tbody = container.querySelector('#recon-table-body');
  if (tbody && Array.isArray(data.wallets)) {
    tbody.innerHTML = data.wallets.map(w => {
      const isMatched = w.status === 'MATCHED';
      const isUsd = w.currency === 'USD';
      const cachedFmt = isUsd ? parseFloat(w.cachedBalance).toLocaleString() + ' USD' : money(parseFloat(w.cachedBalance));
      const ledgerFmt = isUsd ? parseFloat(w.ledgerSum).toLocaleString() + ' USD' : money(parseFloat(w.ledgerSum));
      const deltaFmt = isUsd ? parseFloat(w.delta).toLocaleString() + ' USD' : money(parseFloat(w.delta));

      return `
        <tr>
          <td><strong>${w.walletName}</strong> <span style="font-size:11px;color:#888">(${w.currency})</span></td>
          <td>${cachedFmt}</td>
          <td>${ledgerFmt}</td>
          <td>${isMatched ? '<span style="color:#10b981">0</span>' : `<span style="color:#ef4444;font-weight:600">${deltaFmt}</span>`}</td>
          <td>
            <span class="badge ${isMatched ? 'green' : 'red'}">
              ${isMatched ? 'Khớp sổ 100%' : 'Lệch số dư'}
            </span>
          </td>
        </tr>
      `;
    }).join('');
  }

  const hint = container.querySelector('.hint-box');
  if (hint) {
    if (data.isAllMatched) {
      hint.className = 'hint-box green';
      hint.innerHTML = `<strong>Sổ sách toàn vẹn:</strong> 100% ví khớp đúng số dư với sổ cái kép tại thời điểm ${new Date().toLocaleTimeString('vi-VN')}.`;
    } else {
      hint.className = 'hint-box red';
      hint.innerHTML = `<strong>Cảnh báo an ninh dữ liệu:</strong> Phát hiện ${data.mismatchedWallets} ví có sai lệch số dư. Hệ thống đã ghi nhận nhật ký kiểm toán bảo mật.`;
    }
  }
}

export async function runReconciliationAudit() {
  const data = await ReportApi.getReconciliation();
  updateReconciliationDOM(document, data);
  return data;
}
