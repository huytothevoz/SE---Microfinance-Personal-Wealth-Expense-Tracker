/** Wallet view backed exclusively by /api/wallets. */
import { loadView } from './view-loader.js';
import { safe, money } from './ui-core.js';

function walletCard(w) {
  const id = Number(w.id);
  const balance = Number(w.cached_balance ?? w.balance ?? 0);
  const active = String(w.status || 'ACTIVE') === 'ACTIVE';
  return `<article class="wallet-card">
    <div class="wallet-header"><div><strong class="wallet-name">${safe(w.name)}</strong><span class="wallet-sub">${safe(w.wallet_type ?? 'CASH')}</span></div>
    <span class="wallet-type">${safe(w.currency)}</span></div>
    <div class="wallet-balance">${safe(w.currency) === 'VND' ? money(balance) : `${balance.toLocaleString('vi-VN')} ${safe(w.currency)}`}</div>
    <div class="wallet-meta">${active ? 'Đang sử dụng' : 'Đã lưu trữ'} · ${safe(w.wallet_type)}</div>
    ${active ? `<button type="button" class="text-action" data-action="wallet-edit" data-wallet-id="${id}">Đổi tên / quản lý ví →</button>` : ''}
  </article>`;
}
export async function renderWalletPage({ state }) {
  const html = await loadView('wallets');
  const view = document.createElement('div');
  view.innerHTML = html;
  view.querySelector('[data-action="wallet-add"]')?.setAttribute('data-action','wallet-add-real');
  const refresh = view.querySelector('[data-action="rate-refresh"]');
  if (refresh) refresh.remove(); // No validated FX quote in the wallet API.
  const label = view.querySelector('#wallet-toggle-text');
  if (label) label.textContent = state.onlyActiveWallets ? 'Hiện ví lưu trữ' : 'Ẩn ví lưu trữ';
  const items = state.walletItems.filter(w => !state.onlyActiveWallets || String(w.status) !== 'ARCHIVED');
  const cards = view.querySelector('#wallet-card-list');
  if (cards) cards.innerHTML = state.walletLoading ? '<p>Đang tải ví từ máy chủ…</p>'
    : state.walletError ? `<p role="alert">${safe(state.walletError)} <button type="button" data-action="wallet-retry">Thử lại</button></p>`
    : items.length ? items.map(walletCard).join('') : '<div class="empty-state"><strong>Chưa có ví nào</strong><p>Chọn “Tạo ví mới” để bắt đầu quản lý tài chính.</p></div>';
  const total = view.querySelector('#wallets-total-balance');
  if (total) total.textContent = state.walletError ? 'Không xác định' : money(state.walletItems.filter(w=>w.status==='ACTIVE' && w.currency==='VND').reduce((sum,w)=>sum+Number(w.cached_balance??0),0));
  const totalTitle = total?.previousElementSibling;
  if (totalTitle) totalTitle.textContent = 'Tổng số dư VND (không quy đổi ngoại tệ)';
  const ac = view.querySelector('#wallets-active-count');
  if (ac) ac.textContent = state.walletError ? '—' : String(state.walletItems.filter(w=>w.status==='ACTIVE').length);
  const arc = view.querySelector('#wallets-archived-count');
  if (arc) arc.textContent = state.walletError ? '—' : String(state.walletItems.filter(w=>w.status==='ARCHIVED').length);
  const rate = view.querySelector('#wallets-usd-rate');
  if (rate) rate.textContent = 'Chưa có tỷ giá được xác minh';
  return view.innerHTML;
}
