/**
 * WealthFlow Analytics & Dashboard API Service
 * Interacts with /api/analytics endpoints
 */
import { httpClient } from './http-client.js';

export const AnalyticsApi = {
  getNetWorthSummary() {
    return httpClient('analytics/net-worth');
  },

  getNetWorthHistory(period = '7m') {
    return httpClient('analytics/net-worth/history', {
      params: { period }
    });
  },

  getCashFlowSummary(period = '6m') {
    return httpClient('analytics/cash-flow', {
      params: { period }
    });
  },

  getFinancialHealth() {
    return httpClient('analytics/financial-health');
  }
};
