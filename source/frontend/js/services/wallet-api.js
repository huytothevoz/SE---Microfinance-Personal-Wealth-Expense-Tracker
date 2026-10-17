/**
 * WealthFlow Wallet API Service
 * Interacts with /api/wallets endpoints
 */
import { httpClient } from './http-client.js';

export const WalletApi = {
  getWallets(includeArchived = false) {
    return httpClient('wallets', {
      params: { include_archived: includeArchived ? '1' : '0' }
    });
  },

  getWallet(id) {
    return httpClient(`wallets/${id}`);
  },

  createWallet(data) {
    return httpClient('wallets', {
      method: 'POST',
      data
    });
  },

  archiveWallet(id) {
    return httpClient(`wallets/${id}/archive`, {
      method: 'POST'
    });
  },

  refreshRates() {
    return httpClient('exchange-rates/refresh', {
      method: 'POST'
    });
  }
};
