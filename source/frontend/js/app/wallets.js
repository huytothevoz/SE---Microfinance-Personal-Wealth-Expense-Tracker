/** Wallet view backed exclusively by /api/wallets. */
import { loadView } from './view-loader.js';
import { icon, safe } from './ui-core.js';

const TYPE_LABELS = {
  CASH: 'Tiền mặt',
  BANK: 'Ngân hàng',
  E_WALLET: 'Ví điện tử',
  SAVINGS: 'Tiết kiệm',
  CREDIT: 'Thẻ tín dụng'
};

const TYPE_VISUALS = {
  CASH: { icon: 'cash', tone: 'cash' },
  BANK: { icon: 'bank', tone: 'bank' },
  E_WALLET: { icon: 'wallet', tone: 'ewallet' },
  SAVINGS: { icon: 'savings', tone: 'savings' },
  CREDIT: { icon: 'card', tone: 'credit' }
};

function decimal(value, digits) {
  const match = String(value ?? '0').trim().match(/^(-?)(\d+)(?:\.(\d+))?$/);
  if (!match) return digits > 0 ? `0,${'0'.repeat(digits)}` : '0';
  const whole = match[2].replace(/^0+(?=\d)/, '');
  const fraction = (match[3] || '').padEnd(digits, '0').slice(0, digits);
  const isZero = /^0+$/.test(whole) && (fraction === '' || /^0+$/.test(fraction));
  const sign = match[1] === '-' && !isZero ? '-' : '';
  return `${sign}${whole.replace(/\B(?=(\d{3})+(?!\d))/g, '.')}${digits > 0 ? `,${fraction}` : ''}`;
}

function amount(value, currency) {
  return `${decimal(value, currency === 'VND' ? 0 : 2)} ${currency === 'VND' ? 'đ' : safe(currency)}`;
}

function walletCard(wallet) {
  const id = Number(wallet.id);
  const type = String(wallet.wallet_type ?? wallet.type ?? 'CASH');
  const visual = TYPE_VISUALS[type] || TYPE_VISUALS.CASH;
  const currency = String(wallet.currency ?? 'VND');
  const balance = wallet.cached_balance ?? wallet.balance ?? '0';
  const active = String(wallet.status || 'ACTIVE') === 'ACTIVE';
  return `<article class="wallet-card wallet-card--${visual.tone}${active ? '' : ' wallet-card--archived'}">
    <div class="wallet-header"><div class="wallet-identity"><span class="wallet-icon" title="${safe(TYPE_LABELS[type] || type)}">${icon(visual.icon)}</span><div><strong class="wallet-name">${safe(wallet.name)}</strong><span class="wallet-sub">${safe(TYPE_LABELS[type] || type)} · ${safe(currency)}</span></div></div>
    <span class="wallet-type">${active ? safe(TYPE_LABELS[type] || type) : 'Đã lưu trữ'}</span></div>
    <div><div class="wallet-balance">${amount(balance, currency)}</div>
    <div class="wallet-meta">${active ? 'Đang sử dụng' : 'Không còn sử dụng'}</div>
    <div class="wallet-actions">${active
      ? `<button type="button" class="btn btn--outline btn-sm wallet-manage-button" data-action="wallet-manage" data-wallet-id="${id}">${icon('gear')} Chỉnh sửa</button>`
      : `<button type="button" class="btn btn--outline btn-sm wallet-delete-button" data-action="wallet-delete" data-wallet-id="${id}">${icon('trash')} Xóa vĩnh viễn</button>`}
    </div></div>
  </article>`;
}

function latestRate(rates, from, to) {
  return rates.find(rate => String(rate.from_currency).toUpperCase() === from && String(rate.to_currency).toUpperCase() === to);
}

function totalVndBalance(wallets) {
  return wallets.reduce((total, wallet) => {
    const raw = String(wallet.cached_balance ?? wallet.balance ?? '0').trim();
    const whole = raw.split('.', 1)[0];
    return /^-?\d+$/.test(whole) ? total + BigInt(whole) : total;
  }, 0n).toString();
}

export async function renderWalletPage({ state }) {
  const html = await loadView('wallets');
  const view = document.createElement('div');
  view.innerHTML = html;
  view.querySelector('[data-action="wallet-add"]')?.setAttribute('data-action', 'wallet-add-real');

  const label = view.querySelector('#wallet-toggle-text');
  if (label) label.textContent = state.onlyActiveWallets ? 'Hiện ví lưu trữ' : 'Ẩn ví lưu trữ';
  const items = state.walletItems.filter(wallet => !state.onlyActiveWallets || String(wallet.status) !== 'ARCHIVED');
  const cards = view.querySelector('#wallet-card-list');
  if (cards) cards.innerHTML = state.walletLoading ? '<p>Đang tải ví từ máy chủ…</p>'
    : state.walletError ? `<p role="alert">${safe(state.walletError)} <button type="button" data-action="wallet-retry">Thử lại</button></p>`
    : items.length ? items.map(walletCard).join('') : '<div class="empty-state"><strong>Chưa có ví nào</strong><p>Chọn “Tạo ví mới” để bắt đầu quản lý tài chính.</p></div>';

  const activeWallets = state.walletItems.filter(wallet => String(wallet.status) === 'ACTIVE');
  const totalVnd = totalVndBalance(activeWallets.filter(wallet => String(wallet.currency) === 'VND'));
  const total = view.querySelector('#wallets-total-balance');
  if (total) total.textContent = state.walletError ? 'Không xác định' : amount(totalVnd, 'VND');
  const activeCount = view.querySelector('#wallets-active-count');
  if (activeCount) activeCount.textContent = state.walletError ? '—' : String(activeWallets.length);
  const archivedCount = view.querySelector('#wallets-archived-count');
  if (archivedCount) archivedCount.textContent = state.walletError ? '—' : String(state.walletItems.filter(wallet => String(wallet.status) === 'ARCHIVED').length);

  const rates = state.walletRates || [];
  const usd = latestRate(rates, 'USD', 'VND');
  const eur = latestRate(rates, 'EUR', 'VND');
  const usdTarget = view.querySelector('#wallets-usd-rate');
  if (usdTarget) usdTarget.textContent = usd ? `${decimal(usd.rate, 0)} VND / USD` : 'Chưa có tỷ giá';
  const eurTarget = view.querySelector('#wallets-eur-rate');
  if (eurTarget) eurTarget.textContent = eur ? `${decimal(eur.rate, 0)} VND / EUR` : 'Chưa có tỷ giá';
  const source = view.querySelector('#wallets-rate-source');
  const newest = rates[0];
  if (source) source.textContent = newest
    ? `${newest.provider || 'Không rõ nguồn'} · ${newest.effective_at || ''}`
    : (state.walletRateWarning || 'Chưa đồng bộ Vietcombank');

  return view.innerHTML;
}
