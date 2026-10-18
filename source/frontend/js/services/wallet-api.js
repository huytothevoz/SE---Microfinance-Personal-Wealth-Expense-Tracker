import { httpClient } from './http-client.js';
export const walletApi = {
  async list(scope='active') { return httpClient('wallets',{params:{include_archived:scope==='all'?'1':'0'}}); },
  create(data) { return httpClient('wallets',{method:'POST',data}); },
  update(id,data) { return httpClient(`wallets/${encodeURIComponent(id)}`,{method:'PUT',data}); },
  archive(id) { return httpClient(`wallets/${encodeURIComponent(id)}/archive`,{method:'POST',data:{}}); }
};
export const WalletApi = {
  getWallets: (all=false)=>walletApi.list(all?'all':'active'),
  getWallet: id=>httpClient(`wallets/${encodeURIComponent(id)}`),
  createWallet: data=>walletApi.create(data),
  archiveWallet: id=>walletApi.archive(id),
  updateWallet: (id,data)=>walletApi.update(id,data)
};
