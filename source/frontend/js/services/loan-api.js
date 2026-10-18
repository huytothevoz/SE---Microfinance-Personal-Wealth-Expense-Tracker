/**
 * WealthFlow Loan API Service
 * Interacts with /api/loans endpoints
 */
import { httpClient } from './http-client.js';

export const LoanApi = {
  getLoans() {
    return httpClient('loans');
  },

  getLoan(id) {
    return httpClient(`loans/${id}`);
  },

  createLoan(data) {
    return httpClient('loans', {
      method: 'POST',
      data
    });
  },

  recordPayment(loanId, data) {
    return httpClient(`loans/${loanId}/payments`, {
      method: 'POST',
      data
    });
  },

  settleLoan(loanId) {
    return httpClient(`loans/${loanId}/settle`, {
      method: 'POST'
    });
  }
};
