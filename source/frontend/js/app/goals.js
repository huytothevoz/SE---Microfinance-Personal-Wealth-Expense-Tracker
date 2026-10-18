/**
 * WealthFlow Financial Goals Controller
 * Manages savings targets, earmarking allocations, and goal progress.
 */
import { loadView } from './view-loader.js';
import { goals } from '../data/live-data.js';
import { icon, money, percent } from './ui-core.js';

export function goalsUI() {
  return `
    <div class="card-grid">
      ${goals.map(g => `
        <article class="goal-card">
          <div class="goal-top">
            <span class="goal-emoji">${g.icon}</span>
            <div>
              <h3>${g.name}</h3>
              <p>Thời hạn ${g.deadline}</p>
            </div>
          </div>
          <div class="goal-amount">
            <strong>${money(g.current)}</strong><small>/ ${money(g.target)}</small>
          </div>
          <div class="bar-track">
            <div class="bar-fill" style="width: ${percent(g.current, g.target)}%"></div>
          </div>
          <div class="goal-footer">
            <span>${percent(g.current, g.target)}% hoàn thành</span>
            <strong>Cần thêm ${money(g.monthly)}/tháng</strong>
          </div>
          <button class="btn btn--outline btn-sm" data-action="goal-allocate">
            ${icon('plus')} Phân bổ tích lũy
          </button>
          <div class="status-row">
            <button class="text-action" data-action="goal-complete">Hoàn thành</button>
            <button class="text-action" data-action="goal-cancel">Hủy mục tiêu</button>
          </div>
        </article>
      `).join('')}
    </div>
  `;
}

export async function renderGoalsPage() {
  const rawHtml = await loadView('goals');
  const temp = document.createElement('div');
  temp.innerHTML = rawHtml;

  const cardList = temp.querySelector('#goal-card-list');
  if (cardList) {
    cardList.innerHTML = goalsUI();
  }

  return temp.innerHTML;
}
