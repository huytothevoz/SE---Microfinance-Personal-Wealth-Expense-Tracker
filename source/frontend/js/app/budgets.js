/**
 * WealthFlow Budgets & Recurring Controller
 * Manages category budget limits, warning progress bars, and recurring schedules.
 */
import { loadView } from './view-loader.js';
import { budgets, recurring } from '../data/live-data.js?v=wealth-ui-2';
import { safe, money, percent } from './ui-core.js';

export function budgetsUI(limit = 5) {
  return `
    <div class="budget-stack">
      ${budgets.slice(0, limit).map(b => {
        const used = percent(b.spent, b.limit);
        return `
          <div class="budget-row">
            <div class="budget-info">
              <strong>${safe(b.name)}</strong>
              <span>${money(b.spent)} / ${money(b.limit)}</span>
            </div>
            <div class="bar-track">
              <div class="bar-fill ${used >= 100 ? 'danger' : used >= 80 ? 'warn' : ''}" style="width: ${Math.min(used, 100)}%"></div>
            </div>
            <div class="budget-foot">
              ${used}% hạn mức ${used >= 100 ? '· Đã vượt mức' : used >= 80 ? '· Cần chú ý' : ''}
            </div>
          </div>
        `;
      }).join('')}
    </div>
  `;
}

export async function renderBudgetPage() {
  const rawHtml = await loadView('budgets');
  const temp = document.createElement('div');
  temp.innerHTML = rawHtml;

  const budgetContainer = temp.querySelector('#budget-list-container');
  if (budgetContainer) {
    budgetContainer.innerHTML = budgetsUI();
  }

  return temp.innerHTML;
}

export async function renderRecurringPage() {
  const rawHtml = await loadView('recurring');
  const temp = document.createElement('div');
  temp.innerHTML = rawHtml;

  const tbody = temp.querySelector('#recurring-table-body');
  if (tbody) {
    tbody.innerHTML = recurring.map(r => `
      <tr>
        <td>
          <div class="table-name">
            <span class="table-icon">↻</span>
            <span>${r.name}<small>${r.category}</small></span>
          </div>
        </td>
        <td>${r.wallet}</td>
        <td>${r.date}</td>
        <td class="amount">${money(r.amount)}</td>
        <td>${r.mode}</td>
        <td>
          <span class="badge ${r.active ? '' : 'neutral'}">${r.active ? 'Hoạt động' : 'Tạm dừng'}</span>
        </td>
        <td>
          <button class="text-action" data-action="recurring-toggle">
            ${r.active ? 'Tạm dừng' : 'Kích hoạt'}
          </button> · 
          <button class="text-action" data-action="recurring-delete">Xóa</button>
        </td>
      </tr>
    `).join('');
  }

  return temp.innerHTML;
}
