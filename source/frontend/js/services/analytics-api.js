/**
 * WealthFlow Analytics & Dashboard API Service
 * Interacts with /api/analytics endpoints
 */
import { httpClient } from './http-client.js';

export const AnalyticsApi = {
  getNetWorthSummary() {
    return httpClient('analytics/net-worth');
  },

  getNetWorthHistory(days = 90) {
    return httpClient('analytics/net-worth/history', {
      params: { days }
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
