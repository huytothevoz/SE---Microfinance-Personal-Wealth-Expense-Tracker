import { httpClient } from './http-client.js';
export const walletApi = {
  async list(scope='active') { return httpClient('wallets',{params:{include_archived:scope==='all'?'1':'0'}}); },
  create(data) { return httpClient('wallets',{method:'POST',data}); },
  update(id,data) { return httpClient(`wallets/${encodeURIComponent(id)}`,{method:'PUT',data}); },
  history(id) { return httpClient(`wallets/${encodeURIComponent(id)}/history`); },
  adjust(id,data) { return httpClient(`wallets/${encodeURIComponent(id)}/adjustments`,{method:'POST',data}); },
  archive(id) { return httpClient(`wallets/${encodeURIComponent(id)}/archive`,{method:'POST',data:{}}); },
  remove(id) { return httpClient(`wallets/${encodeURIComponent(id)}`,{method:'DELETE'}); },
  listRates() { return httpClient('exchange-rates'); },
  syncRates() { return httpClient('exchange-rates/sync',{method:'POST',data:{}}); }
};
export const WalletApi = {
  getWallets: (all=false)=>walletApi.list(all?'all':'active'),
  getWallet: id=>httpClient(`wallets/${encodeURIComponent(id)}`),
  createWallet: data=>walletApi.create(data),
  archiveWallet: id=>walletApi.archive(id),
  deleteWallet: id=>walletApi.remove(id),
  updateWallet: (id,data)=>walletApi.update(id,data),
  adjustWallet: (id,data)=>walletApi.adjust(id,data)
};
