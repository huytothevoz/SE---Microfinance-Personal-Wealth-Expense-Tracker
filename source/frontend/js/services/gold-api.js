/**
 * WealthFlow Gold & Precious Metals API Service
 * Interacts with /api/gold endpoints
 */
import { httpClient } from './http-client.js';

export const GoldApi = {
  getGoldAssets() {
    return httpClient('gold');
  },

  getGoldAsset(id) {
    return httpClient(`gold/${id}`);
  },

  createGoldTransaction(data) {
    return httpClient('gold/transactions', {
      method: 'POST',
      data
    });
  },

  revalueGoldAsset(id, data) {
    return httpClient(`gold/${id}/revalue`, {
      method: 'POST',
      data
    });
  },

  liquidateGoldAsset(id, data = {}) {
    return httpClient(`gold/${id}/liquidate`, {
      method: 'POST',
      data
    });
  }
};
