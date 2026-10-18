/**
 * WealthFlow Budget & Recurring API Service
 * Interacts with /api/budgets and /api/recurring endpoints
 */
import { httpClient } from './http-client.js';

export const BudgetApi = {
  getBudgets(month) {
    return httpClient('budgets', {
      params: month ? { month } : {}
    });
  },

  createBudget(data) {
    return httpClient('budgets', {
      method: 'POST',
      data
    });
  },

  updateBudget(id, data) {
    return httpClient(`budgets/${id}`, {
      method: 'PUT',
      data
    });
  },

  deleteBudget(id) {
    return httpClient(`budgets/${id}`, {
      method: 'DELETE'
    });
  },

  getRecurringSchedules() {
    return httpClient('recurring');
  },

  createRecurringSchedule(data) {
    return httpClient('recurring', {
      method: 'POST',
      data
    });
  },

  toggleRecurringSchedule(id, active) {
    return httpClient(`recurring/${id}/${active ? 'resume' : 'pause'}`, {
      method: 'POST'
    });
  },

  deleteRecurringSchedule(id) {
    return httpClient(`recurring/${id}`, {
      method: 'DELETE'
    });
  }
};
