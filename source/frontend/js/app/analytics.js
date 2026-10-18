/**
 * WealthFlow Analytics & Dashboard Controller
 * Manages overview KPI metrics, Net Worth SVG trend chart, and asset distribution.
 */
import { loadView } from './view-loader.js';
import { trend, goals, transactions } from '../data/live-data.js';
import { money, percent } from './ui-core.js';
import { budgetsUI } from './budgets.js';
import { txTable } from './transactions.js';

export function lineChart() { return '<p>Chưa có chuỗi dữ liệu tài sản ròng được đồng bộ.</p>'; }

export async function renderOverview() {
  const rawHtml = await loadView('overview');
  const temp = document.createElement('div');
  temp.innerHTML = rawHtml;

  // Populate dynamic sub-components
  const budgetListEl = temp.querySelector('#overview-budget-list');
  if (budgetListEl) {
    budgetListEl.innerHTML = budgetsUI(3);
  }

  const goalListEl = temp.querySelector('#overview-goal-list');
  if (goalListEl) {
    goalListEl.innerHTML = goals.slice(0, 2).map(g => `
      <div class="budget-row" style="margin: 0 0 20px">
        <div class="budget-info">
          <strong>${g.name}</strong>
          <span>${percent(g.current, g.target)}%</span>
        </div>
        <div class="bar-track">
          <div class="bar-fill" style="width: ${percent(g.current, g.target)}%"></div>
        </div>
        <div class="budget-foot">
          ${money(g.current)} / ${money(g.target)}
        </div>
      </div>
    `).join('');
  }

  const recentTxEl = temp.querySelector('#overview-recent-transactions');
  if (recentTxEl) {
    recentTxEl.innerHTML = txTable(transactions.slice(0, 5));
  }

  return temp.innerHTML;
}
