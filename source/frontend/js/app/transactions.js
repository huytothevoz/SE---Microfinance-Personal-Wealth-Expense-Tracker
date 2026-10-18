/** WealthFlow transactions and personal/default category UI backed by REST APIs. */
import { loadView } from './view-loader.js';
import { safe } from './ui-core.js';
import { TransactionApi } from '../services/transaction-api.js?v=wallet-refactor-20261018-5';
import { WalletApi } from '../services/wallet-api.js?v=wallet-refactor-20261018-5';

const ACTIONS = new Set([
  'tx-add', 'transfer', 'transaction-retry', 'transaction-export', 'reversal',
  'category-add', 'category-edit', 'category-archive', 'category-retry'
]);
const FORMS = new Set([
  'transaction-create-form', 'transaction-transfer-form', 'transaction-reversal-form',
  'category-create-form', 'category-edit-form', 'category-archive-form'
]);
const TYPE_LABEL = { INCOME: 'Thu nhập', EXPENSE: 'Chi tiêu', TRANSFER: 'Chuyển ví' };
const TYPE_ICON = { INCOME: '↙', EXPENSE: '↗', TRANSFER: '⇄' };

function amount(value, currency = 'VND') {
  const digits = currency === 'VND' ? 0 : 2;
  const formatted = new Intl.NumberFormat('vi-VN', {
    minimumFractionDigits: digits,
    maximumFractionDigits: digits
  }).format(Number(value || 0));
  return `${formatted} ${currency === 'VND' ? 'đ' : currency}`;
}

function dateLabel(value) {
  const match = String(value || '').match(/^(\d{4})-(\d{2})-(\d{2})/);
  return match ? `${match[3]}/${match[2]}/${match[1]}` : String(value || '—');
}

function walletLabel(transaction) {
  const walletEntries = (transaction.entries || []).filter(entry => entry.wallet_id && entry.wallet_name);
  const wallets = String(transaction.transaction_type).toUpperCase() === 'TRANSFER'
    ? walletEntries.sort((left, right) => (left.entry_side === 'CREDIT' ? -1 : 1) - (right.entry_side === 'CREDIT' ? -1 : 1)).map(entry => entry.wallet_name)
    : walletEntries.map(entry => entry.wallet_name);
  return [...new Set(wallets)].join(' → ') || '—';
}

export function normalizeTransaction(transaction) {
  const type = String(transaction.transaction_type || transaction.type || '').toUpperCase();
  return {
    raw: transaction,
    id: Number(transaction.id),
    code: `TX-${String(transaction.id).padStart(6, '0')}`,
    name: transaction.description || TYPE_LABEL[type] || 'Giao dịch',
    type,
    category: transaction.category_name || (type === 'TRANSFER' ? 'Chuyển nội bộ' : '—'),
    date: transaction.occurred_on || '',
    wallet: walletLabel(transaction),
    amount: Number(transaction.amount || 0),
    amountBase: Number(transaction.entries?.[0]?.amount_base || transaction.amount || 0),
    currency: transaction.currency || 'VND',
    status: String(transaction.status || '').toUpperCase(),
    rate: transaction.fx_rate_snapshot || '1'
  };
}

function filteredTransactions(state) {
  const query = String(state.search || '').trim().toLocaleLowerCase('vi');
  return (state.transactionItems || []).filter(item => {
    const typeMatches = state.txFilter === 'all' || item.type.toLowerCase() === state.txFilter;
    const text = [item.name, item.code, item.category, item.wallet].join(' ').toLocaleLowerCase('vi');
    return typeMatches && text.includes(query);
  });
}

export function txTable(items = []) {
  if (!items.length) {
    return `<div class="empty-state"><span class="empty-icon">⌕</span>
      <strong>Không có giao dịch phù hợp</strong><p>Thử thay đổi từ khóa hoặc bộ lọc.</p></div>`;
  }
  return `<div class="data-table-wrap"><table class="data-table"><thead><tr>
    <th>Giao dịch</th><th>Danh mục</th><th>Ngày</th><th>Ví / nguồn tiền</th>
    <th>Giá trị</th><th>Trạng thái</th><th></th>
  </tr></thead><tbody>${items.map(item => {
    const sign = item.type === 'INCOME' ? '+' : item.type === 'EXPENSE' ? '−' : '⇄';
    const amountClass = item.type === 'INCOME' ? 'positive' : item.type === 'EXPENSE' ? 'negative' : '';
    const statusClass = item.status === 'POSTED' ? 'neutral' : 'warn';
    const statusLabel = item.status === 'POSTED' ? 'Đã ghi sổ' : item.status === 'REVERSED' ? 'Đã đảo' : 'Bản nháp';
    return `<tr><td><div class="table-name"><span class="table-icon">${TYPE_ICON[item.type] || '•'}</span>
      <span>${safe(item.name)}<small>${safe(item.code)}</small></span></div></td>
      <td>${safe(item.category)}</td><td>${safe(dateLabel(item.date))}</td><td>${safe(item.wallet)}</td>
      <td class="amount ${amountClass}">${sign} ${safe(amount(Math.abs(item.amount), item.currency))}</td>
      <td><span class="badge ${statusClass}">${statusLabel}</span></td>
      <td><button class="text-action" data-transaction-detail="${item.id}">Chi tiết</button></td></tr>`;
  }).join('')}</tbody></table></div>`;
}

export function updateTransactionTable(state) {
  const target = document.querySelector('#transaction-table');
  if (target) target.innerHTML = txTable(filteredTransactions(state));
}

function renderTransactionKpis(root, items) {
  const now = new Date();
  const prefix = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`;
  const current = items.filter(item => item.date.startsWith(prefix));
  const sum = type => current.filter(item => item.type === type && item.status === 'POSTED')
    .reduce((total, item) => total + item.amountBase, 0);
  root.querySelector('#tx-month-income').textContent = amount(sum('INCOME'));
  root.querySelector('#tx-month-expense').textContent = amount(sum('EXPENSE'));
  root.querySelector('#tx-month-transfer').textContent = amount(sum('TRANSFER'));
  root.querySelector('#tx-month-drafts').textContent = `${current.filter(item => item.status === 'DRAFT').length} bản nháp`;
}

export async function renderTransactionPage({ state }) {
  const rawHtml = await loadView('transactions');
  const root = document.createElement('div');
  root.innerHTML = rawHtml;
  try {
    const result = await TransactionApi.getTransactions({ limit: 100 });
    state.transactionItems = (result.transactions || []).map(normalizeTransaction);
    root.querySelectorAll('[data-tx-filter]').forEach(tab => {
      const active = tab.dataset.txFilter === state.txFilter;
      tab.classList.toggle('active', active);
      tab.setAttribute('aria-selected', String(active));
    });
    root.querySelector('#transaction-search').value = state.search || '';
    root.querySelector('#transaction-table').innerHTML = txTable(filteredTransactions(state));
    renderTransactionKpis(root, state.transactionItems);
  } catch (error) {
    state.transactionItems = [];
    root.querySelector('#transaction-table').innerHTML = `<div class="empty-state"><strong>Không thể tải giao dịch</strong>
      <p>${safe(error.message)}</p><button class="btn btn--outline btn-sm" data-action="transaction-retry">Thử lại</button></div>`;
    renderTransactionKpis(root, []);
  }
  return root.innerHTML;
}

function categoryCards(items) {
  if (!items.length) return '<div class="empty-state"><strong>Chưa có danh mục phù hợp</strong></div>';
  return items.map((category, index) => {
    const type = String(category.category_type).toUpperCase();
    return `<article class="mini-panel"><div class="status-row"><div class="goal-top">
      <span class="goal-emoji" style="height:35px;width:35px">${index % 2 ? '◈' : '◎'}</span>
      <div><h3>${safe(category.name)}</h3><p>Danh mục ${type === 'INCOME' ? 'thu nhập' : 'chi tiêu'}</p></div></div>
      <span class="badge ${type === 'INCOME' ? 'blue' : 'neutral'}">${TYPE_LABEL[type]}</span></div>
      <div style="margin-top:18px"><button class="text-action" data-action="category-edit" data-category-id="${category.id}">Đổi tên</button>
      <button class="text-action" data-action="category-archive" data-category-id="${category.id}">Lưu trữ</button></div></article>`;
  }).join('');
}

function defaultCategoryCards(items) {
  if (!items.length) return '<div class="empty-state"><strong>Chưa có danh mục mặc định hoạt động</strong></div>';
  return items.map(category => `<article class="mini-panel"><div class="status-row"><div>
    <h3>${safe(category.name)}</h3><p>Mẫu hệ thống cho người dùng mới</p></div>
    <span class="badge ${category.category_type === 'INCOME' ? 'blue' : 'neutral'}">${TYPE_LABEL[category.category_type]}</span>
  </div></article>`).join('');
}

function categoryTypeMatches(category, filter) {
  return filter === 'all' || String(category.category_type).toLowerCase() === filter;
}

export async function renderCategoryPage({ state }) {
  const rawHtml = await loadView('categories');
  const root = document.createElement('div');
  root.innerHTML = rawHtml;
  try {
    const result = await TransactionApi.getCategories();
    state.categoryItems = result.categories || [];
    state.defaultCategoryItems = result.defaultCategories || [];
    const filter = state.categoryFilter || 'all';
    root.querySelectorAll('[data-category-filter]').forEach(tab => {
      const active = tab.dataset.categoryFilter === filter;
      tab.classList.toggle('active', active);
      tab.setAttribute('aria-selected', String(active));
    });
    root.querySelector('#category-card-list').innerHTML = categoryCards(
      state.categoryItems.filter(item => categoryTypeMatches(item, filter))
    );
    root.querySelector('#default-category-card-list').innerHTML = defaultCategoryCards(
      state.defaultCategoryItems.filter(item => categoryTypeMatches(item, filter))
    );
  } catch (error) {
    state.categoryItems = [];
    state.defaultCategoryItems = [];
    root.querySelector('#category-card-list').innerHTML = `<div class="empty-state"><strong>Không thể tải danh mục</strong>
      <p>${safe(error.message)}</p><button class="btn btn--outline btn-sm" data-action="category-retry">Thử lại</button></div>`;
    root.querySelector('#default-category-card-list').innerHTML = '';
  }
  return root.innerHTML;
}

function showDialog(dialog, overline, title, html) {
  document.querySelector('#modal-overline').textContent = overline;
  document.querySelector('#modal-title').textContent = title;
  document.querySelector('#modal-content').innerHTML = html;
  dialog.showModal();
}

function footer(label) {
  return `<p class="form-feedback" role="status" aria-live="polite"></p><div class="modal-footer">
    <button type="button" class="btn btn--outline btn-sm" data-close-modal>Hủy</button>
    <button type="submit" class="btn btn--primary btn-sm">${label}</button></div>`;
}

function options(items, valueKey, labelKey) {
  return items.map(item => `<option value="${safe(item[valueKey])}">${safe(item[labelKey])}</option>`).join('');
}

async function ensureFormData(state) {
  const [walletResult, categoryResult] = await Promise.all([
    WalletApi.list('active'),
    TransactionApi.getCategories()
  ]);
  state.transactionWallets = walletResult.wallets || [];
  state.categoryItems = categoryResult.categories || [];
}

function updateCategoryOptions(type) {
  const select = document.querySelector('#tx-category');
  if (!select) return;
  let first = null;
  [...select.options].forEach(option => {
    const visible = option.dataset.type === type;
    option.hidden = !visible;
    option.disabled = !visible;
    if (visible && first === null) first = option;
  });
  if (first) first.selected = true;
}

export function isTransactionAction(action) {
  return ACTIONS.has(action);
}

export function isTransactionForm(form) {
  return FORMS.has(form.id);
}

export async function showTransactionDetails(id, { dialog, toast }) {
  try {
    const result = await TransactionApi.getTransaction(id);
    const item = normalizeTransaction(result.transaction);
    const entries = (result.transaction.entries || []).map(entry => `<tr><td>${safe(entry.entry_side)}</td>
      <td>${safe(entry.account_code)}</td><td>${safe(entry.wallet_name || 'Danh mục')}</td>
      <td>${safe(amount(entry.amount_original, entry.currency))}</td></tr>`).join('');
    showDialog(dialog, 'CHI TIẾT GIAO DỊCH', item.name, `<dl class="detail-grid">
      <div><dt>Mã giao dịch</dt><dd>${safe(item.code)}</dd></div><div><dt>Trạng thái</dt><dd>${safe(item.status)}</dd></div>
      <div><dt>Loại</dt><dd>${safe(TYPE_LABEL[item.type] || item.type)}</dd></div><div><dt>Danh mục</dt><dd>${safe(item.category)}</dd></div>
      <div><dt>Ngày</dt><dd>${safe(dateLabel(item.date))}</dd></div><div><dt>Ví liên quan</dt><dd>${safe(item.wallet)}</dd></div>
      <div><dt>Số tiền</dt><dd>${safe(amount(item.amount, item.currency))}</dd></div><div><dt>Tỷ giá snapshot</dt><dd>${safe(item.rate)}</dd></div>
    </dl><div class="data-table-wrap"><table class="data-table"><thead><tr><th>Nợ/Có</th><th>Tài khoản</th><th>Ví</th><th>Số tiền</th></tr></thead>
      <tbody>${entries}</tbody></table></div><div class="modal-footer"><button class="btn btn--outline btn-sm" data-close-modal>Đóng</button>
      ${item.status === 'POSTED' ? `<button class="btn btn--primary btn-sm" data-action="reversal" data-transaction-id="${item.id}">Đảo giao dịch</button>` : ''}</div>`);
  } catch (error) {
    toast(error.message);
  }
}

function exportTransactions(state, toast) {
  const rows = [['Mã', 'Nội dung', 'Loại', 'Danh mục', 'Ví', 'Ngày', 'Số tiền', 'Tiền tệ', 'Trạng thái'],
    ...filteredTransactions(state).map(item => [item.code, item.name, item.type, item.category, item.wallet, item.date, item.amount, item.currency, item.status])];
  const neutralize = value => /^[=+\-@]/.test(String(value).trimStart()) ? `'${value}` : String(value);
  const csv = rows.map(row => row.map(value => `"${neutralize(value).replaceAll('"', '""')}"`).join(',')).join('\r\n');
  const url = URL.createObjectURL(new Blob(['\ufeff' + csv], { type: 'text/csv;charset=utf-8;' }));
  const anchor = document.createElement('a');
  anchor.href = url;
  anchor.download = 'wealthflow_giao_dich.csv';
  anchor.click();
  setTimeout(() => URL.revokeObjectURL(url), 2000);
  toast('Đã xuất các giao dịch đang hiển thị.');
}

export async function handleTransactionAction(action, element, { state, dialog, toast, navigate }) {
  if (action === 'transaction-retry') return navigate('transactions');
  if (action === 'category-retry') return navigate('categories');
  if (action === 'transaction-export') return exportTransactions(state, toast);

  if (action === 'tx-add' || action === 'transfer') {
    try {
      await ensureFormData(state);
    } catch (error) {
      toast(error.message);
      return;
    }
    if (!state.transactionWallets.length) {
      toast('Cần ít nhất một ví đang hoạt động trước khi ghi giao dịch.');
      return;
    }
  }

  if (action === 'tx-add') {
    if (!state.categoryItems.length) {
      toast('Cần ít nhất một danh mục đang hoạt động trước khi ghi giao dịch.');
      return;
    }
    showDialog(dialog, 'GHI SỔ', 'Ghi giao dịch thu / chi', `<form id="transaction-create-form"><div class="form-grid">
      <div class="field"><label for="tx-type">Loại *</label><select id="tx-type" name="type" required><option value="EXPENSE">Chi tiêu</option><option value="INCOME">Thu nhập</option></select></div>
      <div class="field"><label for="tx-wallet">Ví *</label><select id="tx-wallet" name="walletId" required>${options(state.transactionWallets, 'id', 'name')}</select></div>
      <div class="field"><label for="tx-category">Danh mục *</label><select id="tx-category" name="categoryId" required>${state.categoryItems.map(item => `<option value="${item.id}" data-type="${safe(item.category_type)}">${safe(item.name)}</option>`).join('')}</select></div>
      <div class="field"><label for="tx-amount">Số tiền *</label><input id="tx-amount" name="amount" type="number" min="0.01" step="0.01" required></div>
      <div class="field"><label for="tx-date">Ngày *</label><input id="tx-date" name="occurredOn" type="date" value="${new Date().toISOString().slice(0, 10)}" required></div>
      <div class="field"><label for="tx-description">Nội dung *</label><input id="tx-description" name="description" maxlength="255" required></div>
      </div>${footer('Ghi giao dịch')}</form>`);
    updateCategoryOptions('EXPENSE');
    document.querySelector('#tx-type').addEventListener('change', event => updateCategoryOptions(event.target.value));
    return;
  }

  if (action === 'transfer') {
    if (state.transactionWallets.length < 2) {
      toast('Cần ít nhất hai ví đang hoạt động để chuyển tiền nội bộ.');
      return;
    }
    const walletOptions = options(state.transactionWallets, 'id', 'name');
    showDialog(dialog, 'CHUYỂN NỘI BỘ', 'Chuyển tiền giữa các ví', `<form id="transaction-transfer-form"><div class="form-grid">
      <div class="field"><label for="transfer-from">Ví nguồn *</label><select id="transfer-from" name="fromWalletId" required>${walletOptions}</select></div>
      <div class="field"><label for="transfer-to">Ví đích *</label><select id="transfer-to" name="toWalletId" required>${walletOptions}</select></div>
      <div class="field"><label for="transfer-amount">Số tiền nguồn *</label><input id="transfer-amount" name="amount" type="number" min="0.01" step="0.01" required></div>
      <div class="field"><label for="transfer-dest-amount">Số tiền nhận</label><input id="transfer-dest-amount" name="destAmount" type="number" min="0.01" step="0.01" placeholder="Tự tính nếu để trống"></div>
      <div class="field"><label for="transfer-date">Ngày *</label><input id="transfer-date" name="occurredOn" type="date" value="${new Date().toISOString().slice(0, 10)}" required></div>
      <div class="field"><label for="transfer-description">Nội dung *</label><input id="transfer-description" name="description" maxlength="255" value="Chuyển tiền nội bộ" required></div>
      </div>${footer('Chuyển tiền')}</form>`);
    return;
  }

  if (action === 'reversal') {
    dialog.close();
    const id = Number(element.dataset.transactionId);
    showDialog(dialog, 'ĐẢO GIAO DỊCH', `Giao dịch TX-${String(id).padStart(6, '0')}`, `<form id="transaction-reversal-form" data-transaction-id="${id}">
      <p class="form-hint">Giao dịch gốc không bị xóa. Hệ thống sẽ tạo bút toán đối ứng và giữ nguyên lịch sử.</p>
      <div class="field"><label for="reversal-reason">Lý do *</label><input id="reversal-reason" name="reason" maxlength="120" required></div>${footer('Xác nhận đảo')}</form>`);
    return;
  }

  if (action === 'category-add') {
    showDialog(dialog, 'DANH MỤC CÁ NHÂN', 'Tạo danh mục', `<form id="category-create-form"><div class="form-grid">
      <div class="field"><label for="category-name">Tên *</label><input id="category-name" name="name" maxlength="100" required></div>
      <div class="field"><label for="category-type">Loại *</label><select id="category-type" name="type" required><option value="EXPENSE">Chi tiêu</option><option value="INCOME">Thu nhập</option></select></div>
      </div>${footer('Tạo danh mục')}</form>`);
    return;
  }

  const categoryId = Number(element.dataset.categoryId);
  const category = (state.categoryItems || []).find(item => Number(item.id) === categoryId);
  if (!category) {
    toast('Không tìm thấy danh mục. Vui lòng tải lại trang.');
    return;
  }
  if (action === 'category-edit') {
    showDialog(dialog, 'DANH MỤC CÁ NHÂN', 'Đổi tên danh mục', `<form id="category-edit-form" data-category-id="${category.id}">
      <div class="field"><label for="category-edit-name">Tên *</label><input id="category-edit-name" name="name" maxlength="100" value="${safe(category.name)}" required></div>
      <p class="form-hint">Loại ${TYPE_LABEL[category.category_type]} được giữ nguyên để bảo toàn dữ liệu giao dịch.</p>${footer('Lưu thay đổi')}</form>`);
  } else if (action === 'category-archive') {
    showDialog(dialog, 'LƯU TRỮ DANH MỤC', category.name, `<form id="category-archive-form" data-category-id="${category.id}">
      <p class="form-hint">Danh mục sẽ ẩn khỏi lựa chọn mới. Các giao dịch đã dùng danh mục này không bị xóa hoặc thay đổi.</p>${footer('Lưu trữ')}</form>`);
  }
}

export async function handleTransactionSubmit(form, { dialog, toast, navigate }) {
  const submit = form.querySelector('[type="submit"]');
  const feedback = form.querySelector('.form-feedback');
  const data = Object.fromEntries(new FormData(form));
  submit.disabled = true;
  feedback.textContent = 'Đang xử lý…';
  try {
    let destination = 'transactions';
    if (form.id === 'transaction-create-form') {
      await TransactionApi.createTransaction(data);
      toast('Đã ghi sổ giao dịch.');
    } else if (form.id === 'transaction-transfer-form') {
      if (!data.destAmount) delete data.destAmount;
      await TransactionApi.transfer(data);
      toast('Đã chuyển tiền và ghi sổ hai bút toán đối ứng.');
    } else if (form.id === 'transaction-reversal-form') {
      await TransactionApi.reverseTransaction(form.dataset.transactionId, data.reason);
      toast('Đã tạo bút toán đảo và giữ nguyên giao dịch gốc.');
    } else if (form.id === 'category-create-form') {
      await TransactionApi.createCategory(data);
      destination = 'categories';
      toast('Đã tạo danh mục cá nhân.');
    } else if (form.id === 'category-edit-form') {
      await TransactionApi.updateCategory(form.dataset.categoryId, data);
      destination = 'categories';
      toast('Đã đổi tên danh mục.');
    } else if (form.id === 'category-archive-form') {
      await TransactionApi.archiveCategory(form.dataset.categoryId);
      destination = 'categories';
      toast('Đã lưu trữ danh mục; lịch sử giao dịch vẫn được giữ nguyên.');
    }
    dialog.close();
    await navigate(destination);
  } catch (error) {
    feedback.textContent = Object.values(error.fields || {}).join(' ') || error.message;
  } finally {
    submit.disabled = false;
  }
}
