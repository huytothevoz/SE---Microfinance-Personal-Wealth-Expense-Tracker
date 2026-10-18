import { httpClient } from './http-client.js';

export const WalletApi = {
  list(status = 'all') {
    return httpClient('wallets', { params: { status } });
  },

  get(id) {
    return httpClient(`wallets/${encodeURIComponent(id)}`);
  },

  create(data) {
    return httpClient('wallets', { method: 'POST', data });
  },

  update(id, data) {
    return httpClient(`wallets/${encodeURIComponent(id)}`, { method: 'PUT', data });
  },

  adjust(id, data) {
    return httpClient(`wallets/${encodeURIComponent(id)}/adjustments`, { method: 'POST', data });
  },

  archive(id) {
    return httpClient(`wallets/${encodeURIComponent(id)}/archive`, { method: 'POST', data: {} });
  },

  listRates() {
    return httpClient('exchange-rates');
  },

  syncRates() {
    return httpClient('exchange-rates/sync', { method: 'POST', data: {} });
  }
};
