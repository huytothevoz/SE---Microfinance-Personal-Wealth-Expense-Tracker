/**
 * WealthFlow Analytics & Dashboard Controller
 * Manages overview KPI metrics, Net Worth SVG trend chart, and asset distribution.
 */
import { loadView } from './view-loader.js';
import { trend, goals, transactions } from '../data/fixtures.js';
import { money, percent } from './ui-core.js';
import { budgetsUI } from './budgets.js';
import { txTable } from './transactions.js';

export function lineChart() {
  const max = 145, min = 65, coords = trend.map((v, i) => `${30 + i * 78},${190 - ((v - min) / (max - min)) * 155}`);
  const area = `M${coords[0]} L${coords.slice(1).join(' L')} L498,190 L30,190 Z`;
  return `
    <svg class="chart-svg" viewBox="0 0 525 220" role="img" aria-label="Tài sản ròng tăng từ 76 lên 128,45 triệu đồng qua 7 kỳ">
      <defs>
        <linearGradient id="netFill" x1="0" y1="0" x2="0" y2="1">
          <stop offset="0" stop-color="#39a5b4" stop-opacity=".2"/>
          <stop offset="1" stop-color="#39a5b4" stop-opacity="0"/>
        </linearGradient>
      </defs>
      ${[36, 85, 135, 185].map(y => `<path class="chart-gridline" d="M30 ${y}H510"/>`).join('')}
      <path d="${area}" fill="url(#netFill)"/>
      <path d="M${coords.join(' L')}" fill="none" stroke="#17899d" stroke-width="3.4" stroke-linecap="round" stroke-linejoin="round"/>
      <circle cx="498" cy="${190 - ((128.45 - min) / (max - min)) * 155}" r="5" fill="#17899d" stroke="white" stroke-width="3"/>
      <text x="5" y="40" class="chart-label">140tr</text>
      <text x="5" y="90" class="chart-label">115tr</text>
      <text x="5" y="140" class="chart-label">90tr</text>
      <text x="5" y="190" class="chart-label">65tr</text>
    </svg>
    <div class="chart-months">
      <span>Thg 4</span>
      <span>Thg 5</span>
      <span>Thg 6</span>
      <span>Thg 7</span>
      <span>Thg 8</span>
      <span>Thg 9</span>
      <span>Thg 10</span>
    </div>
  `;
}

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
