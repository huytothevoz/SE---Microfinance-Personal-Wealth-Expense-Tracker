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

function decimalValue(value) {
  const match = String(value ?? '0').trim().match(/^(-?)(\d+)(?:\.(\d+))?$/);
  if (!match) return null;
  const fraction = match[3] || '';
  return {
    value: BigInt(`${match[1]}${match[2]}${fraction}`),
    scale: 10n ** BigInt(fraction.length)
  };
}

function roundedProduct(left, right) {
  const a = decimalValue(left);
  const b = decimalValue(right);
  if (!a || !b) return null;
  const product = a.value * b.value;
  const divisor = a.scale * b.scale;
  const sign = product < 0n ? -1n : 1n;
  return sign * ((product * sign + divisor / 2n) / divisor);
}

function convertedVnd(wallet, rates) {
  const currency = String(wallet.currency ?? 'VND').toUpperCase();
  const balance = wallet.cached_balance ?? wallet.balance ?? '0';
  if (currency === 'VND') return roundedProduct(balance, '1');
  const rate = latestRate(rates, currency, 'VND');
  return rate ? roundedProduct(balance, rate.rate) : null;
}

function walletCard(wallet, rates) {
  const id = Number(wallet.id);
  const type = String(wallet.wallet_type ?? wallet.type ?? 'CASH');
  const visual = TYPE_VISUALS[type] || TYPE_VISUALS.CASH;
  const currency = String(wallet.currency ?? 'VND');
  const balance = wallet.cached_balance ?? wallet.balance ?? '0';
  const active = String(wallet.status || 'ACTIVE') === 'ACTIVE';
  const equivalent = currency === 'VND' ? null : convertedVnd(wallet, rates);
  return `<article class="wallet-card wallet-card--${visual.tone}${active ? '' : ' wallet-card--archived'}">
    <div class="wallet-header"><div class="wallet-identity"><span class="wallet-icon" title="${safe(TYPE_LABELS[type] || type)}">${icon(visual.icon)}</span><div><strong class="wallet-name">${safe(wallet.name)}</strong><span class="wallet-sub">${safe(TYPE_LABELS[type] || type)} · ${safe(currency)}</span></div></div>
    <span class="wallet-type">${active ? safe(TYPE_LABELS[type] || type) : 'Đã lưu trữ'}</span></div>
    <div><div class="wallet-balance">${amount(balance, currency)}</div>
    <div class="wallet-meta">${currency === 'VND'
      ? (active ? 'Đang sử dụng' : 'Không còn sử dụng')
      : (equivalent === null ? 'Chưa có tỷ giá quy đổi sang VND' : `Tương đương ${amount(equivalent, 'VND')}`)}</div>
    <div class="wallet-actions">${active
      ? `<button type="button" class="btn btn--outline btn-sm wallet-manage-button" data-action="wallet-manage" data-wallet-id="${id}">${icon('gear')} Chi tiết</button>`
      : `<button type="button" class="btn btn--outline btn-sm wallet-manage-button" data-action="wallet-manage" data-wallet-id="${id}">${icon('file')} Chi tiết</button>
         <button type="button" class="btn btn--outline btn-sm wallet-delete-button" data-action="wallet-delete" data-wallet-id="${id}">${icon('trash')} Xóa vĩnh viễn</button>`}
    </div></div>
  </article>`;
}

function latestRate(rates, from, to) {
  return rates.find(rate => String(rate.from_currency).toUpperCase() === from && String(rate.to_currency).toUpperCase() === to);
}

function totalVndBalance(wallets, rates) {
  let total = 0n;
  let missingRate = false;
  for (const wallet of wallets) {
    const converted = convertedVnd(wallet, rates);
    if (converted === null) missingRate = true;
    else total += converted;
  }
  return { total: total.toString(), missingRate };
}

function formatDateTime(value) {
  const match = String(value || '').match(/^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2})(?::(\d{2}))?/);
  return match ? `${match[3]}/${match[2]}/${match[1]} ${match[4]}:${match[5]}:${match[6] || '00'}` : String(value || '');
}

export async function renderWalletPage({ state }) {
  const html = await loadView('wallets');
  const view = document.createElement('div');
  view.innerHTML = html;
  view.querySelector('[data-action="wallet-add"]')?.setAttribute('data-action', 'wallet-add-real');

  const label = view.querySelector('#wallet-toggle-text');
  if (label) label.textContent = state.onlyActiveWallets ? 'Hiện ví lưu trữ' : 'Ẩn ví lưu trữ';
  const savedFilters = Array.isArray(state.walletTypeFilter)
    ? state.walletTypeFilter
    : [state.walletTypeFilter || 'ALL'];
  const typeFilters = savedFilters.length ? savedFilters : ['ALL'];
  const filterText = view.querySelector('#wallet-filter-text');
  if (filterText) {
    const selectedTypes = typeFilters.filter(type => type !== 'ALL');
    filterText.textContent = !selectedTypes.length
      ? 'Lọc theo loại ví · Tất cả'
      : selectedTypes.length === 1
        ? `Lọc theo loại ví · ${TYPE_LABELS[selectedTypes[0]] || selectedTypes[0]}`
        : `Lọc theo loại ví · ${selectedTypes.length} loại`;
  }
  const items = state.walletItems.filter(wallet =>
    (!state.onlyActiveWallets || String(wallet.status) !== 'ARCHIVED')
    && (typeFilters.includes('ALL') || typeFilters.includes(String(wallet.wallet_type ?? wallet.type)))
  );
  const cards = view.querySelector('#wallet-card-list');
  if (cards) cards.innerHTML = state.walletLoading ? '<p>Đang tải ví từ máy chủ…</p>'
    : state.walletError ? `<p role="alert">${safe(state.walletError)} <button type="button" data-action="wallet-retry">Thử lại</button></p>`
    : items.length ? items.map(wallet => walletCard(wallet, state.walletRates || [])).join('') : '<div class="empty-state"><strong>Không có ví phù hợp</strong><p>Hãy đổi bộ lọc hoặc chọn “Tạo ví mới”.</p></div>';

  const activeWallets = state.walletItems.filter(wallet => String(wallet.status) === 'ACTIVE');
  const rates = state.walletRates || [];
  const totalVnd = totalVndBalance(activeWallets, rates);
  const total = view.querySelector('#wallets-total-balance');
  if (total) total.textContent = state.walletError ? 'Không xác định' : `${amount(totalVnd.total, 'VND')}${totalVnd.missingRate ? ' *' : ''}`;
  const totalNote = view.querySelector('#wallets-total-note');
  if (totalNote) totalNote.textContent = totalVnd.missingRate
    ? '* Chưa cộng ví ngoại tệ đang thiếu tỷ giá.'
    : 'Đã quy đổi các ví ngoại tệ theo tỷ giá mới nhất.';
  const activeCount = view.querySelector('#wallets-active-count');
  if (activeCount) activeCount.textContent = state.walletError ? '—' : String(activeWallets.length);
  const archivedCount = view.querySelector('#wallets-archived-count');
  if (archivedCount) archivedCount.textContent = state.walletError ? '—' : String(state.walletItems.filter(wallet => String(wallet.status) === 'ARCHIVED').length);

  const usd = latestRate(rates, 'USD', 'VND');
  const eur = latestRate(rates, 'EUR', 'VND');
  const usdTarget = view.querySelector('#wallets-usd-rate');
  if (usdTarget) usdTarget.textContent = usd ? `${decimal(usd.rate, 0)} VND / USD` : 'Chưa có tỷ giá';
  const eurTarget = view.querySelector('#wallets-eur-rate');
  if (eurTarget) eurTarget.textContent = eur ? `${decimal(eur.rate, 0)} VND / EUR` : 'Chưa có tỷ giá';
  const source = view.querySelector('#wallets-rate-source');
  const newest = rates[0];
  if (source) source.textContent = newest
    ? `${newest.provider || 'Không rõ nguồn'} · đồng bộ ${formatDateTime(state.walletRateSyncedAt || newest.effective_at)}`
    : (state.walletRateWarning || 'Chưa đồng bộ Vietcombank');

  return view.innerHTML;
}
