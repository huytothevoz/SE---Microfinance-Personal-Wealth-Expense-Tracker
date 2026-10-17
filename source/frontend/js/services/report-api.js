/**
 * WealthFlow Report & Reconciliation API Service
 * Interacts with /api/reports endpoints
 */
import { httpClient } from './http-client.js';

export const ReportApi = {
  getReconciliation() {
    return httpClient('reports/reconcile');
  },

  getTrialBalance() {
    return httpClient('reports/trial-balance');
  },

  exportCsv(type = 'transactions') {
    return httpClient('reports/export/csv', {
      params: { type }
    });
  }
};
