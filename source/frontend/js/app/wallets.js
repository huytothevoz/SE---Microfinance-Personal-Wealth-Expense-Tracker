import { loadView } from './view-loader.js';
import { icon, safe } from './ui-core.js';
import { WalletApi } from '../services/wallet-api.js?v=wallet-refactor-20261018-5';

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

const WALLET_ACTIONS = new Set([
  'wallet-add',
  'wallet-manage',
  'wallet-edit',
  'wallet-adjust',
  'wallet-archive',
  'wallet-retry',
  'rate-refresh'
]);

const WALLET_FORMS = new Set([
  'wallet-create-form',
  'wallet-edit-form',
  'wallet-adjust-form',
  'wallet-archive-form'
]);

function decimal(value, digits) {
  const match = String(value ?? '0').trim().match(/^(-?)(\d+)(?:\.(\d+))?$/);
  if (!match) return digits > 0 ? `0,${'0'.repeat(digits)}` : '0';
  const whole = match[2].replace(/^0+(?=\d)/, '');
  const fraction = (match[3] || '').padEnd(digits, '0').slice(0, digits);
  const isZero = /^0+$/.test(whole) && (fraction === '' || /^0+$/.test(fraction));
  const sign = match[1] === '-' && !isZero ? '-' : '';
  const grouped = whole.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
  return `${sign}${grouped}${digits > 0 ? `,${fraction}` : ''}`;
}

function amount(value, currency) {
  const digits = currency === 'VND' ? 0 : 2;
  return `${decimal(value, digits)} ${currency === 'VND' ? 'đ' : currency}`;
}

function renderWalletCards(items) {
  if (items.length === 0) {
    return `<div class="empty-state"><strong>Chưa có ví phù hợp</strong><p>Tạo ví đầu tiên để bắt đầu theo dõi số dư.</p></div>`;
  }
  return items.map(wallet => {
    const archived = wallet.status === 'ARCHIVED';
    const visual = TYPE_VISUALS[wallet.type] || TYPE_VISUALS.CASH;
    const converted = wallet.baseBalance === null
      ? `Chưa có tỷ giá ${safe(wallet.currency)}/${safe(wallet.baseCurrency)}`
      : wallet.currency === wallet.baseCurrency
        ? `Tiền cơ sở ${safe(wallet.baseCurrency)}`
        : `≈ ${amount(wallet.baseBalance, wallet.baseCurrency)}`;
    const actions = archived ? '' : `<div class="wallet-actions">
      <button class="btn btn--outline btn-sm wallet-manage-button" data-action="wallet-manage" data-wallet-id="${wallet.id}">${icon('gear')} Chỉnh sửa</button>
    </div>`;
    return `<article class="wallet-card wallet-card--${visual.tone}${archived ? ' wallet-card--archived' : ''}">
      <div class="wallet-header">
        <div class="wallet-identity"><span class="wallet-icon wallet-icon--${visual.tone}" title="${safe(TYPE_LABELS[wallet.type] || wallet.type)}">${icon(visual.icon)}</span><div>
          <span class="wallet-name">${safe(wallet.name)}</span>
          <span class="wallet-sub">${safe(TYPE_LABELS[wallet.type] || wallet.type)} · ${safe(wallet.currency)}</span>
        </div></div>
        <span class="wallet-type">${archived ? 'Đã lưu trữ' : safe(TYPE_LABELS[wallet.type] || wallet.type)}</span>
      </div>
      <div>
        <div class="wallet-balance">${amount(wallet.balance, wallet.currency)}</div>
        <div class="wallet-meta">${converted}</div>
        ${actions}
      </div>
    </article>`;
  }).join('');
}

function latestRate(rates, from, to) {
  return rates.find(rate => rate.from_currency === from && rate.to_currency === to);
}

export async function renderWalletPage({ state }) {
  const rawHtml = await loadView('wallets');
  const temp = document.createElement('div');
  temp.innerHTML = rawHtml;

  try {
    const status = state.onlyActiveWallets ? 'active' : 'all';
    const result = await WalletApi.list(status);
    state.walletItems = result.wallets;
    state.walletSummary = result.summary;
    try {
      state.walletRates = (await WalletApi.listRates()).rates || [];
    } catch {
      state.walletRates = [];
    }

    temp.querySelector('#wallet-toggle-text').textContent = state.onlyActiveWallets
      ? 'Hiện ví lưu trữ'
      : 'Ẩn ví lưu trữ';
    temp.querySelector('#wallet-card-list').innerHTML = renderWalletCards(result.wallets);
    temp.querySelector('#wallets-total-label').textContent = `Tổng số dư quy đổi (${result.summary.baseCurrency})`;
    temp.querySelector('#wallets-total-balance').textContent = result.summary.hasUnconvertedBalances
      ? `${amount(result.summary.totalBalanceBase, result.summary.baseCurrency)} + số dư chưa quy đổi`
      : amount(result.summary.totalBalanceBase, result.summary.baseCurrency);
    temp.querySelector('#wallets-active-count').textContent = String(result.summary.activeCount);
    temp.querySelector('#wallets-archived-count').textContent = String(result.summary.archivedCount);
    temp.querySelector('#wallets-base-currency').textContent = `${result.summary.baseCurrency} (tiền cơ sở)`;

    const usd = latestRate(state.walletRates, 'USD', result.summary.baseCurrency);
    const eur = latestRate(state.walletRates, 'EUR', result.summary.baseCurrency);
    temp.querySelector('#wallets-usd-rate').textContent = usd
      ? `${decimal(usd.rate, result.summary.baseCurrency === 'VND' ? 0 : 2)} ${result.summary.baseCurrency} / USD`
      : 'Chưa có tỷ giá';
    temp.querySelector('#wallets-eur-rate').textContent = eur
      ? `${decimal(eur.rate, result.summary.baseCurrency === 'VND' ? 0 : 2)} ${result.summary.baseCurrency} / EUR`
      : 'Chưa có tỷ giá';
    const newest = state.walletRates[0];
    temp.querySelector('#wallets-rate-source').textContent = newest
      ? `${newest.provider || 'Không rõ nguồn'} · ${newest.effective_at}`
      : 'Chưa đồng bộ Vietcombank';
  } catch (error) {
    state.walletItems = [];
    temp.querySelector('#wallet-card-list').innerHTML = `<div class="empty-state">
      <strong>Không thể tải dữ liệu ví</strong>
      <p>${safe(error.message)}</p>
      <button class="btn btn--outline btn-sm" data-action="wallet-retry">Thử lại</button>
    </div>`;
  }

  return temp.innerHTML;
}

function showDialog(dialog, overline, title, html) {
  document.querySelector('#modal-overline').textContent = overline;
  document.querySelector('#modal-title').textContent = title;
  document.querySelector('#modal-content').innerHTML = html;
  if (!dialog.open) dialog.showModal();
}

function footer(label) {
  return `<p class="form-feedback" role="status" aria-live="polite"></p>
    <div class="modal-footer">
      <button type="button" class="btn btn--outline btn-sm" data-close-modal>Hủy</button>
      <button type="submit" class="btn btn--primary btn-sm">${label}</button>
    </div>`;
}

export function isWalletAction(action) {
  return WALLET_ACTIONS.has(action);
}

export function isWalletForm(form) {
  return WALLET_FORMS.has(form.id);
}

export async function handleWalletAction(action, element, { state, dialog, toast, navigate }) {
  if (action === 'wallet-retry') {
    await navigate('wallets');
    return;
  }
  if (action === 'rate-refresh') {
    element.disabled = true;
    try {
      const result = await WalletApi.syncRates();
      toast(`Đã cập nhật tỷ giá Vietcombank lúc ${result.effectiveAt}.`);
      await navigate('wallets');
    } catch (error) {
      toast(error.message);
    } finally {
      element.disabled = false;
    }
    return;
  }
  if (action === 'wallet-add') {
    showDialog(dialog, 'TẠO VÍ', 'Tạo ví / tài khoản', `<form id="wallet-create-form">
      <div class="form-grid">
        <div class="field"><label for="wallet-name">Tên ví *</label><input id="wallet-name" name="name" maxlength="120" required autocomplete="off"></div>
        <div class="field"><label for="wallet-type">Loại ví *</label><select id="wallet-type" name="type" required>
          <option value="CASH">Tiền mặt</option><option value="BANK">Ngân hàng</option>
          <option value="E_WALLET">Ví điện tử</option><option value="SAVINGS">Tiết kiệm</option>
          <option value="CREDIT">Thẻ tín dụng</option>
        </select></div>
        <div class="field"><label for="wallet-currency">Tiền tệ *</label><select id="wallet-currency" name="currency" required><option>VND</option><option>USD</option><option>EUR</option></select></div>
        <div class="field"><label for="wallet-opening-balance">Số dư đầu kỳ *</label><input id="wallet-opening-balance" name="openingBalance" type="number" min="0" step="0.01" value="0" required></div>
      </div>${footer('Tạo ví')}</form>`);
    return;
  }

  const walletId = Number(element.dataset.walletId);
  const wallet = (state.walletItems || []).find(item => item.id === walletId);
  if (!wallet) {
    toast('Không tìm thấy ví. Vui lòng tải lại trang.');
    return;
  }
  if (action === 'wallet-manage') {
    showDialog(dialog, 'QUẢN LÝ VÍ', wallet.name, `<div class="wallet-edit-menu">
      <button type="button" class="wallet-edit-option" data-action="wallet-edit" data-wallet-id="${wallet.id}">
        <span class="wallet-edit-option__icon">${icon('file')}</span><span><strong>Đổi tên ví</strong><small>Cập nhật tên hiển thị của ví hoặc tài khoản</small></span>
      </button>
      <button type="button" class="wallet-edit-option" data-action="wallet-adjust" data-wallet-id="${wallet.id}">
        <span class="wallet-edit-option__icon">${icon('arrows')}</span><span><strong>Điều chỉnh số dư</strong><small>Ghi nhận tăng hoặc giảm bằng bút toán tài chính</small></span>
      </button>
      <button type="button" class="wallet-edit-option wallet-edit-option--danger" data-action="wallet-archive" data-wallet-id="${wallet.id}">
        <span class="wallet-edit-option__icon">${icon('layers')}</span><span><strong>Lưu trữ ví</strong><small>Chỉ áp dụng khi số dư và ràng buộc đang hoạt động bằng 0</small></span>
      </button>
      <div class="modal-footer"><button type="button" class="btn btn--outline btn-sm" data-close-modal>Đóng</button></div>
    </div>`);
  } else if (action === 'wallet-edit') {
    showDialog(dialog, 'CHỈNH SỬA VÍ', 'Đổi tên ví', `<form id="wallet-edit-form" data-wallet-id="${wallet.id}">
      <div class="field"><label for="wallet-edit-name">Tên ví *</label><input id="wallet-edit-name" name="name" maxlength="120" value="${safe(wallet.name)}" required autocomplete="off"></div>
      ${footer('Lưu thay đổi')}</form>`);
  } else if (action === 'wallet-adjust') {
    const step = wallet.currency === 'VND' ? '1' : '0.01';
    showDialog(dialog, 'ĐIỀU CHỈNH SỐ DƯ', wallet.name, `<form id="wallet-adjust-form" data-wallet-id="${wallet.id}">
      <div class="form-grid">
        <div class="field"><label for="wallet-adjust-direction">Thao tác *</label><select id="wallet-adjust-direction" name="direction" required><option value="INCREASE">Thêm tiền</option><option value="DECREASE">Bớt tiền</option></select></div>
        <div class="field"><label for="wallet-adjust-amount">Số tiền (${safe(wallet.currency)}) *</label><input id="wallet-adjust-amount" name="amount" type="number" min="${step}" step="${step}" required></div>
        <div class="field form-grid-span"><label for="wallet-adjust-note">Nội dung</label><input id="wallet-adjust-note" name="note" maxlength="200" autocomplete="off"></div>
      </div>${footer('Ghi nhận điều chỉnh')}</form>`);
  } else if (action === 'wallet-archive') {
    showDialog(dialog, 'LƯU TRỮ VÍ', wallet.name, `<form id="wallet-archive-form" data-wallet-id="${wallet.id}">
      <p class="form-hint">Chỉ có thể lưu trữ ví có số dư và số tiền phân bổ bằng 0, đồng thời không còn lịch định kỳ hoạt động.</p>
      ${footer('Lưu trữ ví')}</form>`);
  }
}

export async function handleWalletSubmit(form, { dialog, toast, navigate }) {
  const submit = form.querySelector('[type="submit"]');
  const feedback = form.querySelector('.form-feedback');
  submit.disabled = true;
  feedback.textContent = 'Đang xử lý…';
  try {
    const data = Object.fromEntries(new FormData(form));
    if (form.id === 'wallet-create-form') {
      await WalletApi.create(data);
      toast('Đã tạo ví và ghi nhận số dư đầu kỳ.');
    } else if (form.id === 'wallet-edit-form') {
      await WalletApi.update(form.dataset.walletId, data);
      toast('Đã cập nhật tên ví.');
    } else if (form.id === 'wallet-adjust-form') {
      await WalletApi.adjust(form.dataset.walletId, data);
      toast('Đã điều chỉnh số dư và ghi sổ tài chính.');
    } else if (form.id === 'wallet-archive-form') {
      await WalletApi.archive(form.dataset.walletId);
      toast('Đã lưu trữ ví.');
    }
    dialog.close();
    await navigate('wallets');
  } catch (error) {
    const fields = Object.values(error.fields || {});
    feedback.textContent = fields.length ? fields.join(' ') : error.message;
  } finally {
    submit.disabled = false;
  }
}
