/**
 * WealthFlow Gold & Precious Metals Controller
 * Manages gold assets, weight tracking in chi/cay, and market valuation.
 */
import { loadView } from './view-loader.js';
import { gold } from '../data/fixtures.js';
import { money } from './ui-core.js';

export async function renderGoldPage() {
  const rawHtml = await loadView('gold');
  const temp = document.createElement('div');
  temp.innerHTML = rawHtml;

  const cardList = temp.querySelector('#gold-card-list');
  if (cardList) {
    cardList.innerHTML = gold.map(g => `
      <article class="gold-card">
        <div class="status-row">
          <span class="goal-emoji">◇</span>
          <span class="badge blue">Đang sở hữu</span>
        </div>
        <div>
          <h3>${g.name}</h3>
          <p>Số lượng: ${g.weight}</p>
        </div>
        <div>
          <span class="description">Giá trị định giá</span>
          <div class="loan-amount">${money(g.value)}</div>
          <p>${money(g.price)} / chỉ · Cập nhật ${g.date}</p>
        </div>
        <button class="text-action" style="text-align: left" data-action="gold-liquidate">
          Thanh lý / xóa tài sản →
        </button>
      </article>
    `).join('');
  }

  return temp.innerHTML;
}
