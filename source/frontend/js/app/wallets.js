/**
 * WealthFlow Wallets Controller
 * Manages user wallet accounts, archive toggle, and currency conversions.
 */
import { loadView } from './view-loader.js';
import { wallets } from '../data/fixtures.js';
import { money } from './ui-core.js';

export function renderWalletCards(walletItems) {
  return walletItems.map(w => `
    <article class="wallet-card">
      <div class="wallet-header">
        <div>
          <span class="wallet-name">${w.name}</span>
          <span class="wallet-sub">${w.number}</span>
        </div>
        <span class="wallet-type">${w.type}</span>
      </div>
      <div>
        <div class="wallet-balance">${w.currency === 'USD' ? w.balance + ' USD' : money(w.balance)}</div>
        <div class="wallet-meta">
          ${w.currency === 'USD' ? `≈ ${money(w.baseBalance)}` : `Tiền tệ ${w.currency}`} ${!w.active ? '· Đã lưu trữ' : ''}
        </div>
        ${w.active && w.balance === 0
          ? `<button class="text-action" style="margin-top: 9px" data-action="wallet-archive">Lưu trữ ví →</button>`
          : w.active
            ? `<button class="text-action" style="margin-top: 9px" data-action="wallet-archive">Quản lý / lưu trữ →</button>`
            : `<button class="text-action" style="margin-top: 9px" data-action="wallet-archive">Xem lịch sử ví →</button>`}
      </div>
    </article>
  `).join('');
}

export async function renderWalletPage({ state }) {
  const rawHtml = await loadView('wallets');
  const temp = document.createElement('div');
  temp.innerHTML = rawHtml;

  // Toggle button label
  const toggleBtnText = temp.querySelector('#wallet-toggle-text');
  if (toggleBtnText) {
    toggleBtnText.textContent = state.onlyActiveWallets ? 'Hiện ví lưu trữ' : 'Ẩn ví lưu trữ';
  }

  // Populate wallet cards
  const cardList = temp.querySelector('#wallet-card-list');
  if (cardList) {
    const visibleWallets = wallets.filter(w => w.active || !state.onlyActiveWallets);
    cardList.innerHTML = renderWalletCards(visibleWallets);
  }

  return temp.innerHTML;
}
