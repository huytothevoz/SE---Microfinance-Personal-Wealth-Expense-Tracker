/**
 * WealthFlow Transactions & Categories Controller
 * Handles transaction filtering, search query updates, detail modal data, and category cards.
 */
import { loadView } from './view-loader.js';
import { transactions, categories } from '../data/live-data.js';
import { safe, money } from './ui-core.js';

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
                  <span>${safe(t.name)}<small>${safe(t.id)}</small></span>
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
        <div style="margin-top: 18px">
          <button class="text-action" data-action="category-edit">Xem / chỉnh sửa →</button>
        </div>
      </div>
    `).join('');
  }

  return temp.innerHTML;
}
