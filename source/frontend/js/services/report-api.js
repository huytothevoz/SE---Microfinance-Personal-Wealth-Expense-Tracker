/**
 * WealthFlow Report, Reconciliation & CSV Export API Client (FR12, UC11, SQ07).
 */
import { httpClient } from './http-client.js';

export const ReportApi = {
  /**
   * Reconcile all wallets or a specific wallet against double-entry ledger (FR12.02).
   */
  reconcile(params = {}) {
    return httpClient('reports/reconcile', {
      method: 'GET',
      params
    });
  },

  /**
   * Trigger an explicit reconciliation run via POST (FR12.02).
   */
  runReconciliation(data = {}) {
    return httpClient('reports/reconcile', {
      method: 'POST',
      data
    });
  },

  /**
   * Fetch double-entry Trial Balance across all ledger accounts (FR12.02).
   */
  getTrialBalance(params = {}) {
    return httpClient('reports/trial-balance', {
      method: 'GET',
      params
    });
  },

  /**
   * Request sanitized UTF-8 BOM CSV export payload and SHA-256 audit hash (FR12.03, FR12.04).
   */
  exportTransactionsCsv(params = {}) {
    return httpClient('reports/export/csv', {
      method: 'GET',
      params: { ...params, format: 'json' }
    });
  }
};

export const reportApi = ReportApi;
