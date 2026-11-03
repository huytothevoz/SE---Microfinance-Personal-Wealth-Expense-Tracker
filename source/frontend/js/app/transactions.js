/**
 * WealthFlow Transactions & Categories Controller
 * Handles transaction filtering, search query updates, detail modal data, CSV export, and category cards.
 */
import { loadView } from './view-loader.js';
import { transactions, categories } from '../data/live-data.js?v=finance-ui-1';
import { safe, money } from './ui-core.js?v=wealth-ui-2';

export function txTable(items = transactions) {
  if (!items.length) {
    return `
      <div class="empty-state">
        <span class="empty-icon">⌕</span>
        <strong>Không có giao dịch phù hợp</strong>
        Thử thay đổi từ khóa hoặc bộ lọc.
      </div>
    `;
  }

  return `
    <div class="data-table-wrap">
      <table class="data-table">
        <thead>
          <tr>
            <th>Giao dịch</th>
            <th>Danh mục</th>
            <th>Ngày</th>
            <th>Ví / nguồn tiền</th>
            <th>Giá trị</th>
            <th>Trạng thái</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          ${items.map(t => `
            <tr>
              <td>
                <div class="table-name">
                  <span class="table-icon">${safe(t.icon)}</span>
                  <span>${safe(t.name)}</span>
                </div>
              </td>
              <td>${safe(t.category)}</td>
              <td>${safe(t.date)}</td>
              <td>${safe(t.wallet)}</td>
              <td class="amount ${t.type === 'income' ? 'positive' : t.type === 'expense' ? 'negative' : ''}">
                ${t.type === 'income' ? '+' : t.type === 'expense' ? '−' : '⇄'} ${money(Math.abs(t.amount))}
              </td>
              <td>
                <span class="badge ${t.status === 'DRAFT' ? 'warn' : 'neutral'}">
                  ${t.status === 'DRAFT' ? 'Bản nháp' : 'Đã ghi sổ'}
                </span>
              </td>
              <td>
                <button class="text-action" data-detail="${safe(t.id)}">Chi tiết</button>
              </td>
            </tr>
          `).join('')}
        </tbody>
      </table>
    </div>
  `;
}

export async function renderTransactionPage({ state }) {
  const rawHtml = await loadView('transactions');
  const temp = document.createElement('div');
  temp.innerHTML = rawHtml;

  // Set active tab
  temp.querySelectorAll('[data-tx-filter]').forEach(tab => {
    const isActive = tab.dataset.txFilter === state.txFilter;
    tab.classList.toggle('active', isActive);
    tab.setAttribute('aria-selected', String(isActive));
  });

  // Set search input value
  const searchInput = temp.querySelector('#transaction-search');
  if (searchInput) {
    searchInput.value = safe(state.search);
  }

  // Filter transactions
  const filtered = transactions.filter(t =>
    (state.txFilter === 'all' || t.type === state.txFilter) &&
    [t.name, t.id, t.category, t.wallet].join(' ').toLocaleLowerCase('vi').includes(state.search.toLocaleLowerCase('vi'))
  );

  const tableContainer = temp.querySelector('#transaction-table');
  if (tableContainer) {
    tableContainer.innerHTML = txTable(filtered);
  }

  return temp.innerHTML;
}

export async function renderCategoryPage() {
  const rawHtml = await loadView('categories');
  const temp = document.createElement('div');
  temp.innerHTML = rawHtml;

  const cardList = temp.querySelector('#category-card-list');
  if (cardList) {
    cardList.innerHTML = categories.map((c, i) => `
      <div class="mini-panel">
        <div class="status-row">
          <div class="goal-top">
            <span class="goal-emoji" style="height: 35px; width: 35px">${i % 2 ? '◈' : '◎'}</span>
            <div>
              <h3>${c.name}</h3>
              <p>Danh mục ${c.type.toLowerCase()}</p>
            </div>
          </div>
          <span class="badge ${c.type === 'Thu nhập' ? 'blue' : 'neutral'}">${c.type}</span>
        </div>
        ${c.description ? `<p class="form-hint">${safe(c.description)}</p>` : ''}
        <div style="margin-top: 18px">
          <button class="text-action" data-action="live:detail:categories:${encodeURIComponent(String(c.id))}">Chi tiết</button>
        </div>
      </div>
    `).join('');
  }

  return temp.innerHTML;
}

export function showTransactionDetails(id, { dialog } = {}) {
  const t = transactions.find(row => String(row.id) === String(id));
  if (!t) return;
  const targetDialog = dialog || document.querySelector('#action-dialog');
  if (!targetDialog) return;

  const overline = document.querySelector('#modal-overline');
  if (overline) overline.textContent = '';
  const title = document.querySelector('#modal-title');
  if (title) title.textContent = 'Chi tiết giao dịch';
  const content = document.querySelector('#modal-content');
  if (content) {
    content.innerHTML = `
      <dl class="detail-grid">
        <div>
          <dt>Danh mục</dt>
          <dd>${safe(t.category)}</dd>
        </div>
        <div>
          <dt>Ngày giao dịch</dt>
          <dd>${safe(t.date)}</dd>
        </div>
        <div>
          <dt>Ví liên quan</dt>
          <dd>${safe(t.wallet)}</dd>
        </div>
        <div>
          <dt>Số tiền</dt>
          <dd>${money(Math.abs(t.amount))}</dd>
        </div>
        <div>
          <dt>Tiền tệ</dt>
          <dd>${safe(t.currency)}</dd>
        </div>
      </dl>
      <div class="modal-footer">
        <button class="btn btn--outline btn-sm" data-close-modal>Đóng</button>
      </div>
    `;
  }
  targetDialog.showModal();
}

export function exportTransactionsCSV({ toast = () => {} } = {}) {
  const rows = [
    [
      'Tên giao dịch',
      'Danh mục',
      'Ví',
      'Ngày',
      'Số tiền VND'
    ],
    ...transactions.map(t => [
      t.name,
      t.category,
      t.wallet,
      t.date,
      String(t.amount)
    ])
  ];
  const neutralize = v => {
    const text = String(v).replace(/^[\s\u0000-\u001f]+/, '');
    return /^[=+\-@]/.test(text) ? "'" + String(v) : String(v);
  };
  const csv = rows.map(row => row.map(col => '"' + neutralize(col).replaceAll('"', '""') + '"').join(',')).join('\r\n');
  const blob = new Blob(['\ufeff' + csv], { type: 'text/csv;charset=utf-8;' });
  const url = URL.createObjectURL(blob);
  const anchor = document.createElement('a');
  anchor.href = url;
  anchor.download = 'wealthflow_giao_dich.csv';
  anchor.click();
  setTimeout(() => URL.revokeObjectURL(url), 2000);
  toast('Đã xuất danh sách giao dịch hiện hiển thị.');
}

export function applyTransactionSearch({ state, txTableRenderer = txTable } = {}) {
  if (!state) return;
  const filters = (Array.isArray(state.txFilter) ? state.txFilter : [state.txFilter || 'ALL'])
    .map(value => String(value).toUpperCase())
    .filter(value => value !== 'ALL');
  const filtered = transactions.filter(t => (!filters.length || filters.includes(String(t.type).toUpperCase()))
    && [t.name, t.id, t.category, t.wallet].join(' ').toLocaleLowerCase('vi').includes(state.search.toLocaleLowerCase('vi')));
  const target = document.querySelector('#transaction-table');
  if (target) target.innerHTML = txTableRenderer(filtered);
}

export function openTransactionFilter({ state, dialog } = {}) {
  const options = [['ALL','Tất cả'],['INCOME','Thu nhập'],['EXPENSE','Chi tiêu'],['TRANSFER','Chuyển tiền'],['SAVINGS_GOAL','Mục tiêu tiết kiệm']];
  const selected = (Array.isArray(state.txFilter) ? state.txFilter : [state.txFilter || 'ALL'])
    .map(value => String(value).toUpperCase());
  const checked = selected.length ? selected : ['ALL'];
  const targetDialog = dialog || document.querySelector('#action-dialog');
  if (!targetDialog) return;

  const overline = document.querySelector('#modal-overline');
  if (overline) overline.textContent = '';
  const title = document.querySelector('#modal-title');
  if (title) title.textContent = 'Lọc theo loại giao dịch';
  const content = document.querySelector('#modal-content');
  if (content) {
    content.innerHTML = `<form id="transaction-filter-form">
      <div class="wallet-filter-options">${options.map(([value,label]) => `<label class="wallet-filter-option">
        <input type="checkbox" name="transactionTypeFilter" value="${value}" ${checked.includes(value) ? 'checked' : ''}>
        <span>${label}</span>
      </label>`).join('')}</div>
      <div class="modal-footer"><button type="button" class="btn btn--outline" data-close-modal>Hủy</button><button type="submit" class="btn btn--primary">Áp dụng</button></div>
    </form>`;
  }
  targetDialog.showModal();
}

export function handleTransactionFilterChange(target) {
  if (!target || target.name !== 'transactionTypeFilter') return false;
  const form = target.closest('#transaction-filter-form');
  if (!form) return false;
  const all = form.querySelector('input[value="ALL"]');
  const types = [...form.querySelectorAll('input[name="transactionTypeFilter"]:not([value="ALL"])')];
  if (target.value === 'ALL' && target.checked) types.forEach(input => { input.checked = false; });
  else if (target.value !== 'ALL' && target.checked) all.checked = false;
  if (!types.some(input => input.checked)) all.checked = true;
  return true;
}

export function handleTransactionFilterSubmit(event, { state, dialog, navigate } = {}) {
  if (event.target.id !== 'transaction-filter-form') return false;
  event.preventDefault();
  const selected = [...event.target.querySelectorAll('input[name="transactionTypeFilter"]:checked')]
    .map(input => input.value)
    .filter(value => value !== 'ALL');
  if (state) state.txFilter = selected.length ? selected : ['ALL'];
  if (dialog) dialog.close();
  if (navigate) navigate('transactions');
  return true;
}
