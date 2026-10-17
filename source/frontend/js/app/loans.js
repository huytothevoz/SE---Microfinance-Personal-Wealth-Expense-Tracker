/**
 * WealthFlow Loans & Debt Controller
 * Manages micro-loans, peer borrowing, and repayment schedule tracking.
 */
import { loadView } from './view-loader.js';
import { loans } from '../data/fixtures.js';
import { money, percent } from './ui-core.js';

export async function renderLoanPage() {
  const rawHtml = await loadView('loans');
  const temp = document.createElement('div');
  temp.innerHTML = rawHtml;

  const cardList = temp.querySelector('#loan-card-list');
  if (cardList) {
    cardList.innerHTML = loans.map(l => `
      <article class="loan-card">
        <div class="status-row">
          <span class="badge ${l.kind === 'Đi vay' ? 'warn' : 'blue'}">${l.kind}</span>
          <span class="description">${l.id.toUpperCase()}</span>
        </div>
        <div>
          <h3>${l.name}</h3>
          <p>Đối tác: ${l.partner}</p>
        </div>
        <div>
          <span class="description">Dư gốc còn lại</span>
          <div class="loan-amount">${money(l.remaining)}</div>
          <div class="budget-foot" style="margin: 7px 0">
            Đã thanh toán ${money(l.paid)} / ${money(l.principal)}
          </div>
          <div class="bar-track">
            <div class="bar-fill" style="width: ${percent(l.paid, l.principal)}%"></div>
          </div>
        </div>
        <div class="status-row">
          <span class="description">Hạn tiếp: ${l.due}</span>
          <button class="text-action" data-action="loan-view">Xem lịch trả →</button>
        </div>
      </article>
    `).join('');
  }

  return temp.innerHTML;
}
