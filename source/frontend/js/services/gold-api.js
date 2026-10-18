/**
 * WealthFlow Gold & Precious Metals API Service
 * Interacts with /api/gold endpoints
 */
import { httpClient } from './http-client.js';

export const GoldApi = {
  getGoldAssets() {
    return httpClient('gold');
  },

  getGoldPrices() {
    return httpClient('gold/prices');
  },

  createGoldTransaction(data) {
    return httpClient('gold/transactions', {
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
