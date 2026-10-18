/**
 * WealthFlow Transaction API Service
 * Interacts with /api/transactions and /api/categories endpoints
 */
import { httpClient } from './http-client.js';

export const TransactionApi = {
  getTransactions(params = {}) {
    return httpClient('transactions', { params });
  },

  getTransaction(id) {
    return httpClient(`transactions/${encodeURIComponent(id)}`);
  },

  createTransaction(data) {
    return httpClient('transactions', {
      method: 'POST',
      data
    });
  },

  transfer(data) {
    return httpClient('transactions/transfer', {
      method: 'POST',
      data
    });
  },

  reverseTransaction(id, reason = '') {
    return httpClient(`transactions/${id}/reverse`, {
      method: 'POST',
      data: { reason }
    });
  },

  getCategories(params = {}) {
    return httpClient('categories', { params });
  },

  createCategory(data) {
    return httpClient('categories', {
      method: 'POST',
      data
    });
  },

  updateCategory(id, data) {
    return httpClient(`categories/${encodeURIComponent(id)}`, {
      method: 'PUT',
      data
    });
  },

  archiveCategory(id) {
    return httpClient(`categories/${encodeURIComponent(id)}`, {
      method: 'DELETE'
    });
  }
};
