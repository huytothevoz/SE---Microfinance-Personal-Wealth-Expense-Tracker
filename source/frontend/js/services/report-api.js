/**
 * WealthFlow Report & Reconciliation API Service
 * Interacts with /api/reports endpoints
 */
import { httpClient } from './http-client.js';

export const ReportApi = {
  getReconciliation(walletId = null) {
    const params = {};
    if (walletId !== null && walletId !== undefined) {
      params.walletId = walletId;
    }
    return httpClient('reports/reconcile', { params });
  },

  getTrialBalance(asOfDate = null) {
    const params = {};
    if (asOfDate) {
      params.asOfDate = asOfDate;
    }
    return httpClient('reports/trial-balance', { params });
  },

  async downloadCsv(filters = {}) {
    const query = new URLSearchParams();
    for (const [key, value] of Object.entries(filters)) {
      if (value !== undefined && value !== null && value !== '') {
        query.append(key, String(value));
      }
    }
    const queryString = query.toString();
    const url = `api/reports/export/csv${queryString ? '?' + queryString : ''}`;

    const response = await fetch(url, {
      method: 'GET',
      credentials: 'same-origin',
      headers: {
        'X-WealthFlow': '1'
      },
      cache: 'no-store'
    });

    if (!response.ok) {
      throw new Error('Lỗi tải tệp CSV từ máy chủ. Mã trạng thái: ' + response.status);
    }

    const blob = await response.blob();
    const disposition = response.headers.get('Content-Disposition') || '';
    const match = disposition.match(/filename="?([^";]+)"?/);
    const filename = match ? match[1] : `wealthflow_transactions_${Date.now()}.csv`;

    const downloadUrl = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = downloadUrl;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(downloadUrl);

    return { filename, size: blob.size };
  }
};
