/**
 * WealthFlow Interactive Savings Goals Controller
 * Backed by /api/goals REST endpoints and double-entry earmarked logic.
 */
import { loadView } from './view-loader.js';
import { goals, wallets, dataStatus, loadLiveData } from '../data/live-data.js?v=wealth-ui-2';
import { GoalApi } from '../services/goal-api.js';
import { money, safe, formatMoneyInput, moneyInputValue } from './ui-core.js?v=wealth-ui-2';

const statusText = {
  ACTIVE: 'Đang để dành',
  COMPLETED: 'Đã hoàn thành',
  CANCELLED: 'Đã dừng'
};

const priorityText = {
  LOW: 'Thoải mái',
  MEDIUM: 'Vừa phải',
  HIGH: 'Ưu tiên cao'
};

const priorityIcons = {
  LOW: '🌿',
  MEDIUM: '⚡',
  HIGH: '🔥'
};

const goalState = {
  filter: 'ALL', // 'ALL' | 'ACTIVE' | 'COMPLETED' | 'CANCELLED'
  search: '',
  sortBy: 'progress_desc' // 'progress_desc' | 'progress_asc' | 'deadline_asc' | 'priority_desc' | 'target_desc'
};

const byId = id => goals.find(goal => String(goal.id) === String(id));

const dateText = value => {
  if (!value) return 'Chưa đặt hạn';
  try {
    return new Intl.DateTimeFormat('vi-VN').format(new Date(`${value}T00:00:00`));
  } catch {
    return String(value);
  }
};

const localDate = (date = new Date()) => {
  const y = date.getFullYear();
  const m = String(date.getMonth() + 1).padStart(2, '0');
  const d = String(date.getDate()).padStart(2, '0');
  return `${y}-${m}-${d}`;
};

const tomorrow = () => {
  const d = new Date();
  d.setDate(d.getDate() + 1);
  return localDate(d);
};

function autoEmoji(name, fallback = '🎯') {
  if (!name) return fallback;
  const n = name.toLowerCase();
  if (/xe|ô tô|oto|moto|honda|vespa|yamaha|sh|vinfast/.test(n)) return '🛵';
  if (/nhà|căn hộ|chung cư|mua đất|phòng|sổ đỏ/.test(n)) return '🏠';
  if (/du lịch|travel|đà lạt|phú quốc|hàn quốc|nhật bản|thái lan|máy bay/.test(n)) return '✈️';
  if (/cưới|đám cưới|nhẫn|wedding/.test(n)) return '💍';
  if (/học|khóa học|ielts|toeic|đại học|thạc sĩ|sách|du học/.test(n)) return '🎓';
  if (/iphone|macbook|laptop|máy tính|ipad|pc|điện thoại/.test(n)) return '💻';
  if (/đầu tư|chứng khoán|quỹ|cổ phiếu|trái phiếu/.test(n)) return '📈';
  if (/khẩn cấp|sức khỏe|bảo hiểm|bệnh|y tế|phòng thân/.test(n)) return '🛡️';
  if (/sinh nhật|quà|kỷ niệm|tặng/.test(n)) return '🎁';
  if (/tiết kiệm|tích lũy|tiền|vàng|tài sản/.test(n)) return '💰';
  return fallback;
}

function calculateDaysLeft(deadlineStr) {
  if (!deadlineStr) return null;
  const target = new Date(`${deadlineStr}T00:00:00`);
  const now = new Date();
  const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
  const diffDays = Math.round((target - today) / (1000 * 60 * 60 * 24));
  if (diffDays < 0) return { days: diffDays, status: 'OVERDUE', label: `Quá hạn ${Math.abs(diffDays)} ngày` };
  if (diffDays === 0) return { days: 0, status: 'TODAY', label: 'Hạn là hôm nay' };
  return { days: diffDays, status: 'UPCOMING', label: `Còn ${diffDays} ngày` };
}

function getFilteredGoals() {
  return goals.filter(goal => {
    if (goalState.filter !== 'ALL' && goal.status !== goalState.filter) return false;
    if (goalState.search) {
      const q = goalState.search.toLowerCase().trim();
      const matchName = String(goal.name || '').toLowerCase().includes(q);
      const matchPriority = String(priorityText[goal.priority] || '').toLowerCase().includes(q);
      const matchStatus = String(statusText[goal.status] || '').toLowerCase().includes(q);
      if (!matchName && !matchPriority && !matchStatus) return false;
    }
    return true;
  }).sort((a, b) => {
    switch (goalState.sortBy) {
      case 'progress_asc':
        return Number(a.progress || 0) - Number(b.progress || 0);
      case 'deadline_asc':
        return String(a.deadline || '9999').localeCompare(String(b.deadline || '9999'));
      case 'priority_desc': {
        const weights = { HIGH: 3, MEDIUM: 2, LOW: 1 };
        return (weights[b.priority] || 0) - (weights[a.priority] || 0);
      }
      case 'target_desc':
        return Number(b.target || 0) - Number(a.target || 0);
      case 'progress_desc':
      default:
        return Number(b.progress || 0) - Number(a.progress || 0);
    }
  });
}

function goalCards() {
  if (dataStatus.goals?.state === 'loading' || dataStatus.goals?.state === 'idle') {
    return '<div class="empty-state"><strong>Đang tải danh sách mục tiêu…</strong><p>Hệ thống đang đồng bộ dữ liệu tài chính từ máy chủ.</p></div>';
  }
  if (dataStatus.goals?.state !== 'ready') {
    return `<div class="empty-state"><strong>Chưa tải được mục tiêu</strong><p>${safe(dataStatus.goals?.message || 'Vui lòng kiểm tra kết nối máy chủ.')}</p><button type="button" class="btn btn--outline btn-sm" data-action="goal:filter:ALL">Thử lại</button></div>`;
  }
  if (!goals.length) {
    return `<div class="empty-state goal-empty-box">
      <div class="goal-empty-icon">🎯</div>
      <strong>Bạn chưa có mục tiêu tiết kiệm nào</strong>
      <p>Hãy bắt đầu bằng một khoản bạn muốn để dành (mua xe, du lịch, quỹ dự phòng...) để theo dõi lộ trình tài chính.</p>
      <button type="button" class="btn btn--primary btn-sm" data-action="goal:create">
        <svg class="nav-icon" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 5v14M5 12h14"/></svg>
        Tạo mục tiêu đầu tiên
      </button>
    </div>`;
  }

  const items = getFilteredGoals();
  if (!items.length) {
    return `<div class="empty-state">
      <strong>Không tìm thấy mục tiêu phù hợp</strong>
      <p>Không có mục tiêu nào khớp với bộ lọc hoặc từ khóa "${safe(goalState.search)}".</p>
      <button type="button" class="btn btn--outline btn-sm" data-action="goal:clear-filter">Xóa bộ lọc</button>
    </div>`;
  }

  return items.map(goal => {
    const progress = Math.max(0, Number(goal.progress || 0));
    const barWidth = Math.min(100, progress);
    const active = goal.status === 'ACTIVE';
    const completed = goal.status === 'COMPLETED';
    const isOverTarget = active && goal.current >= goal.target && goal.target > 0;
    const emoji = (!goal.icon || goal.icon === '◎') ? autoEmoji(goal.name) : safe(goal.icon);
    const daysMeta = calculateDaysLeft(goal.deadline);

    let deadlineBadge = '';
    if (daysMeta && active) {
      const cls = daysMeta.status === 'OVERDUE' ? 'deadline-tag--danger' : daysMeta.status === 'TODAY' ? 'deadline-tag--warning' : '';
      deadlineBadge = `<span class="deadline-tag ${cls}" title="Hạn: ${safe(dateText(goal.deadline))}">🕒 ${safe(daysMeta.label)}</span>`;
    }

    const badgeClass = completed ? 'success' : active ? 'blue' : 'neutral';
    const cardStatusClass = completed ? 'goal-card--completed' : goal.status === 'CANCELLED' ? 'goal-card--cancelled' : '';

    const celebrationBanner = isOverTarget ? `
      <div class="goal-celebration-banner">
        <span>🎉 <strong>Đã đạt 100%!</strong> Bạn đã tích lũy đủ số tiền cho mục tiêu này.</span>
        <button type="button" class="btn btn--success btn-sm" data-action="goal:complete:${safe(goal.id)}">✨ Đánh dấu hoàn thành</button>
      </div>` : '';

    return `
      <article class="goal-card ${cardStatusClass}" data-goal-id="${safe(goal.id)}">
        <div class="goal-top">
          <div class="goal-emoji-avatar">${emoji}</div>
          <div class="goal-heading">
            <div class="goal-title-row">
              <h3>${safe(goal.name)}</h3>
              <span class="badge ${badgeClass}">${safe(statusText[goal.status] || goal.status)}</span>
            </div>
            <div class="goal-meta-tags">
              <span class="priority-tag priority-tag--${safe(goal.priority.toLowerCase())}">${priorityIcons[goal.priority] || '•'} ${safe(priorityText[goal.priority] || 'Vừa phải')}</span>
              ${deadlineBadge}
            </div>
          </div>
        </div>

        <div class="goal-amount-block">
          <div class="goal-amount-stat">
            <small>Đã để dành</small>
            <strong class="goal-current-amount">${money(goal.current)}</strong>
          </div>
          <div class="goal-amount-divider">/</div>
          <div class="goal-amount-stat text-right">
            <small>Mục tiêu</small>
            <strong class="goal-target-amount">${money(goal.target)}</strong>
          </div>
        </div>

        <div class="goal-progress-wrap">
          <div class="goal-progress-meta">
            <span class="goal-progress-pct"><strong>${safe(progress.toLocaleString('vi-VN', { maximumFractionDigits: 1 }))}%</strong></span>
            <span class="goal-remaining-text">
              ${goal.remaining > 0 ? `Còn thiếu <strong>${money(goal.remaining)}</strong>` : '<span class="positive">Đã đủ số tiền!</span>'}
            </span>
          </div>
          <div class="goal-progress-bar-track" aria-label="Đã tích lũy ${safe(progress.toLocaleString('vi-VN'))}%">
            <div class="goal-progress-bar-fill ${completed || isOverTarget ? 'goal-progress-bar-fill--complete' : ''}" style="width: ${barWidth}%"></div>
            <div class="goal-milestone-marker" style="left: 25%" title="Cột mốc 25%"></div>
            <div class="goal-milestone-marker" style="left: 50%" title="Cột mốc 50%"></div>
            <div class="goal-milestone-marker" style="left: 75%" title="Cột mốc 75%"></div>
          </div>
        </div>

        ${celebrationBanner}

        ${active && goal.remaining > 0 && goal.monthly > 0 ? `
          <div class="goal-plan">
            <span>💡 Khuyến nghị tích lũy: <strong>${money(goal.monthly)}/tháng</strong> để kịp hạn ${safe(dateText(goal.deadline))}.</span>
          </div>` : ''}

        <div class="goal-card-actions">
          ${active ? `
            <button type="button" class="btn btn--primary btn-sm" data-action="goal:deposit:${safe(goal.id)}">
              <svg class="nav-icon" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
              Góp tiền
            </button>` : ''}
          ${goal.current > 0 ? `
            <button type="button" class="btn btn--outline btn-sm" data-action="goal:withdraw:${safe(goal.id)}" title="Rút tiền khỏi mục tiêu về ví">
              Rút bớt
            </button>` : ''}
          ${isOverTarget ? `
            <button type="button" class="btn btn--success btn-sm" data-action="goal:complete:${safe(goal.id)}">
              Hoàn thành
            </button>` : ''}
          <button type="button" class="btn btn--outline btn-sm" data-action="goal:detail:${safe(goal.id)}">Lịch sử</button>
          <button type="button" class="btn btn--ghost btn-sm" data-action="goal:edit:${safe(goal.id)}" ${goal.status === 'CANCELLED' ? 'disabled' : ''}>Chỉnh sửa</button>
        </div>
      </article>
    `;
  }).join('');
}

export async function renderGoalsPage() {
  const rawHtml = await loadView('goals');
  const temp = document.createElement('div');
  temp.innerHTML = rawHtml;

  const active = goals.filter(goal => goal.status === 'ACTIVE');
  const completed = goals.filter(goal => goal.status === 'COMPLETED');
  const cancelled = goals.filter(goal => goal.status === 'CANCELLED');
  const tracked = goals.filter(goal => goal.status !== 'CANCELLED');

  const target = tracked.reduce((sum, goal) => sum + goal.target, 0);
  const current = tracked.reduce((sum, goal) => sum + goal.current, 0);
  const progress = target > 0 ? (current / target) * 100 : 0;

  temp.querySelector('#goal-active-count').textContent = `${active.length} mục tiêu`;
  temp.querySelector('#goal-target-total').textContent = money(target);
  temp.querySelector('#goal-allocated-total').textContent = money(current);
  temp.querySelector('#goal-progress-percent').textContent = `${progress.toLocaleString('vi-VN', { maximumFractionDigits: 1 })}%`;

  const pillAll = temp.querySelector('#goal-pill-all-count');
  const pillActive = temp.querySelector('#goal-pill-active-count');
  const pillCompleted = temp.querySelector('#goal-pill-completed-count');
  const pillCancelled = temp.querySelector('#goal-pill-cancelled-count');
  if (pillAll) pillAll.textContent = String(goals.length);
  if (pillActive) pillActive.textContent = String(active.length);
  if (pillCompleted) pillCompleted.textContent = String(completed.length);
  if (pillCancelled) pillCancelled.textContent = String(cancelled.length);

  temp.querySelectorAll('.goal-filter-pill').forEach(pill => {
    const action = pill.dataset.action || '';
    const [, , status] = action.split(':');
    const isActive = (status || 'ALL') === goalState.filter;
    pill.classList.toggle('is-active', isActive);
    pill.setAttribute('aria-selected', String(isActive));
  });

  const searchBox = temp.querySelector('#goal-search-box');
  if (searchBox) searchBox.value = goalState.search;

  const sortSelect = temp.querySelector('#goal-sort-dropdown');
  if (sortSelect) sortSelect.value = goalState.sortBy;

  temp.querySelector('#goal-card-list').innerHTML = goalCards();
  return temp.innerHTML;
}

function updateCardsView() {
  const list = document.querySelector('#goal-card-list');
  if (list) list.innerHTML = goalCards();

  document.querySelectorAll('.goal-filter-pill').forEach(pill => {
    const action = pill.dataset.action || '';
    const [, , status] = action.split(':');
    const isActive = (status || 'ALL') === goalState.filter;
    pill.classList.toggle('is-active', isActive);
    pill.setAttribute('aria-selected', String(isActive));
  });
}

function setModal(dialog, title, content, overline = 'MỤC TIÊU TIẾT KIỆM') {
  const overlineEl = document.querySelector('#modal-overline');
  if (overlineEl) overlineEl.textContent = overline;
  const titleEl = document.querySelector('#modal-title');
  if (titleEl) titleEl.textContent = title;
  const contentEl = document.querySelector('#modal-content');
  if (contentEl) contentEl.innerHTML = content;
  dialog.showModal();
}

function priorityOptions(selected = 'MEDIUM') {
  return [
    ['LOW', '🌿 Thoải mái'],
    ['MEDIUM', '⚡ Vừa phải'],
    ['HIGH', '🔥 Ưu tiên cao']
  ].map(([value, label]) => `<option value="${value}" ${value === selected ? 'selected' : ''}>${label}</option>`).join('');
}

function getActiveVndWallets() {
  return wallets.filter(w =>
    String(w.status || 'ACTIVE').toUpperCase() === 'ACTIVE' &&
    String(w.currency || '').toUpperCase() === 'VND' &&
    String(w.type || '').toUpperCase() !== 'CREDIT'
  );
}

function goalForm(goal = null) {
  const editing = Boolean(goal);
  return `<form id="goal-form">
    <div class="form-grid goal-form-grid">
      <div class="field">
        <label for="goal-name">Tên mục tiêu</label>
        <input id="goal-name" name="name" maxlength="150" placeholder="Ví dụ: Mua xe máy, Du lịch Nhật Bản..." value="${safe(goal?.name || '')}" required>
      </div>
      <div class="field">
        <label for="goal-target">Số tiền muốn đạt (VND)</label>
        <input id="goal-target" name="targetAmount" type="text" inputmode="numeric" data-money-input maxlength="24" placeholder="0" value="${editing ? safe(formatMoneyInput(goal.target)) : ''}" required>
      </div>
      <div class="field">
        <label for="goal-deadline">Ngày dự kiến hoàn thành</label>
        <input id="goal-deadline" name="deadline" type="date" min="${tomorrow()}" value="${safe(goal?.deadline || '')}" required>
      </div>
      <div class="field">
        <label for="goal-priority">Mức ưu tiên</label>
        <select id="goal-priority" name="priority">${priorityOptions(goal?.priority)}</select>
      </div>
    </div>
    <p id="goal-form-feedback" role="status" aria-live="polite" class="form-feedback"></p>
    <div class="modal-footer">
      ${editing ? '<button type="button" class="btn btn--danger" data-goal-stop>Dừng mục tiêu</button>' : ''}
      <button type="button" class="btn btn--outline" data-close-modal>Hủy</button>
      <button type="submit" class="btn btn--primary">${editing ? 'Lưu thay đổi' : 'Tạo mục tiêu'}</button>
    </div>
  </form>`;
}

function bindForm(form, request, { dialog, toast, reload, success }) {
  form.addEventListener('submit', async event => {
    event.preventDefault();
    if (!form.reportValidity()) return;
    const submit = form.querySelector('[type="submit"]');
    const feedback = form.querySelector('#goal-form-feedback');
    submit.disabled = true;
    feedback.textContent = 'Đang lưu…';
    try {
      const data = Object.fromEntries(new FormData(form));
      if (data.targetAmount) data.targetAmount = moneyInputValue(data.targetAmount);
      await request(data);
      dialog.close();
      await loadLiveData();
      if (reload) await reload();
      if (toast) toast(success);
    } catch (error) {
      feedback.textContent = error.message;
      submit.disabled = false;
    }
  });
}

function openCreate(context) {
  setModal(context.dialog, 'Thêm mục tiêu tiết kiệm', goalForm(), 'TẠO MỤC TIÊU MỚI');
  bindForm(document.querySelector('#goal-form'), data => GoalApi.createGoal(data), {
    ...context,
    success: 'Đã tạo mục tiêu mới thành công! 🎯'
  });
}

function openEdit(goal, context) {
  setModal(context.dialog, 'Chỉnh sửa mục tiêu', goalForm(goal), 'CẬP NHẬT KẾ HOẠCH');
  document.querySelector('[data-goal-stop]')?.addEventListener('click', () => confirmCancel(goal, context));
  bindForm(document.querySelector('#goal-form'), data => GoalApi.updateGoal(goal.id, data), {
    ...context,
    success: 'Đã cập nhật mục tiêu thành công.'
  });
}

function openDeposit(goal, context) {
  const vndWallets = getActiveVndWallets();
  const remaining = Math.max(0, goal.target - goal.current);

  const walletSelectOptions = vndWallets.length
    ? vndWallets.map(w => `<option value="${safe(w.id)}">${safe(w.name)} (Khả dụng: ${money(w.balance)})</option>`).join('')
    : '<option value="">Chưa có ví VND đang hoạt động</option>';

  const content = `
    <div class="goal-modal-context">
      <div class="goal-modal-summary">
        <div><small>Mục tiêu</small><strong>${safe(goal.name)}</strong></div>
        <div><small>Đã để dành</small><strong>${money(goal.current)}</strong></div>
        <div><small>Còn thiếu</small><strong class="highlight-warn">${money(remaining)}</strong></div>
      </div>
    </div>

    <form id="goal-deposit-form">
      <div class="form-grid">
        <div class="field">
          <label for="deposit-wallet">Chọn ví trích tiền (Earmark)</label>
          <select id="deposit-wallet" name="walletId" required ${!vndWallets.length ? 'disabled' : ''}>
            ${walletSelectOptions}
          </select>
        </div>

        <div class="field">
          <label for="deposit-amount">Số tiền muốn góp thêm (VND)</label>
          <input id="deposit-amount" name="amount" type="text" inputmode="numeric" data-money-input maxlength="20" placeholder="0" required>
          
          <div class="goal-chips-group" aria-label="Gợi ý số tiền nhanh">
            <button type="button" class="goal-chip" data-chip-amount="200000">+200k</button>
            <button type="button" class="goal-chip" data-chip-amount="500000">+500k</button>
            <button type="button" class="goal-chip" data-chip-amount="1000000">+1 triệu</button>
            <button type="button" class="goal-chip" data-chip-amount="2000000">+2 triệu</button>
            ${remaining > 0 ? `<button type="button" class="goal-chip goal-chip--fill" data-chip-amount="${remaining}">🎯 Góp đủ số còn thiếu (${money(remaining)})</button>` : ''}
          </div>
        </div>

        <div class="field">
          <label for="deposit-date">Ngày trích tiền</label>
          <input id="deposit-date" name="allocatedOn" type="date" value="${localDate()}" required>
        </div>

        <div class="field">
          <label for="deposit-note">Ghi chú (tùy chọn)</label>
          <input id="deposit-note" name="note" maxlength="250" placeholder="Ví dụ: Trích lương tháng này, thưởng doanh số...">
        </div>
      </div>

      <div class="goal-calc-preview" id="deposit-preview" style="display:none">
        <span>Dự kiến sau khi góp:</span>
        <strong id="deposit-preview-text">—</strong>
      </div>

      <p id="goal-form-feedback" role="status" aria-live="polite" class="form-feedback"></p>
      <div class="modal-footer">
        <button type="button" class="btn btn--outline" data-close-modal>Hủy</button>
        <button type="submit" class="btn btn--primary" ${!vndWallets.length ? 'disabled' : ''}>Xác nhận góp tiền</button>
      </div>
    </form>
  `;

  setModal(context.dialog, 'Góp tiền vào mục tiêu', content, 'TÍCH LŨY MỤC TIÊU');

  const form = document.querySelector('#goal-deposit-form');
  const amountInput = form.querySelector('#deposit-amount');
  const previewBox = form.querySelector('#deposit-preview');
  const previewText = form.querySelector('#deposit-preview-text');

  function updatePreview() {
    const rawVal = moneyInputValue(amountInput.value);
    const amt = Number(rawVal || 0);
    if (amt > 0) {
      const newTotal = goal.current + amt;
      const newPct = goal.target > 0 ? Math.min(100, Math.round((newTotal / goal.target) * 1000) / 10) : 100;
      previewBox.style.display = 'flex';
      previewText.textContent = `${money(newTotal)} (${newPct.toLocaleString('vi-VN')}%)`;
    } else {
      previewBox.style.display = 'none';
    }
  }

  amountInput.addEventListener('input', updatePreview);

  form.querySelectorAll('[data-chip-amount]').forEach(btn => {
    btn.addEventListener('click', () => {
      const val = Number(btn.dataset.chipAmount || 0);
      amountInput.value = formatMoneyInput(val);
      updatePreview();
      amountInput.focus();
    });
  });

  form.addEventListener('submit', async event => {
    event.preventDefault();
    if (!form.reportValidity()) return;
    const submit = form.querySelector('[type="submit"]');
    const feedback = form.querySelector('#goal-form-feedback');
    submit.disabled = true;
    feedback.textContent = 'Đang phân bổ vào mục tiêu…';
    try {
      const rawData = Object.fromEntries(new FormData(form));
      const amount = Number(moneyInputValue(rawData.amount));
      if (!amount || amount <= 0) throw new Error('Vui lòng nhập số tiền lớn hơn 0.');

      const payload = {
        walletId: Number(rawData.walletId),
        amount,
        allocatedOn: rawData.allocatedOn || localDate(),
        note: (rawData.note || '').trim()
      };

      await GoalApi.allocateFunds(goal.id, payload);
      context.dialog.close();
      await loadLiveData();
      if (context.reload) await context.reload();
      if (context.toast) context.toast(`Đã góp ${money(amount)} vào "${goal.name}" thành công! 🎉`);
    } catch (err) {
      feedback.textContent = err.message || 'Lỗi khi góp tiền.';
      submit.disabled = false;
    }
  });
}

async function openWithdraw(goal, context) {
  setModal(context.dialog, 'Rút tiền khỏi mục tiêu', '<div class="empty-state">Đang tải thông tin phân bổ ví…</div>', 'HOÀN TIỀN VỀ VÍ');

  try {
    const res = await GoalApi.getGoal(goal.id);
    const detail = res.goal || res;
    const allocations = detail.allocations || [];

    const walletTotals = {};
    for (const item of allocations) {
      const wId = item.walletId || item.wallet_id;
      const wName = item.walletName || item.wallet_name || `Ví #${wId}`;
      const amt = Number(item.amount || 0);
      if (!walletTotals[wId]) walletTotals[wId] = { id: wId, name: wName, net: 0 };
      if (item.type === 'DEPOSIT' || item.allocation_type === 'DEPOSIT') {
        walletTotals[wId].net += amt;
      } else {
        walletTotals[wId].net -= amt;
      }
    }

    const availableWallets = Object.values(walletTotals).filter(w => w.net > 0);
    const fallbackWallets = availableWallets.length ? availableWallets : getActiveVndWallets().map(w => ({ id: w.id, name: w.name, net: goal.current }));

    const walletOptionsHtml = fallbackWallets.map(w =>
      `<option value="${safe(w.id)}" data-max="${w.net}">${safe(w.name)} (Đang để dành: ${money(w.net)})</option>`
    ).join('');

    const maxVal = fallbackWallets[0]?.net || goal.current;

    const content = `
      <div class="goal-modal-context">
        <div class="goal-modal-summary">
          <div><small>Mục tiêu</small><strong>${safe(goal.name)}</strong></div>
          <div><small>Đang để dành</small><strong class="highlight-positive">${money(goal.current)}</strong></div>
          <div><small>Mục tiêu</small><strong>${money(goal.target)}</strong></div>
        </div>
      </div>

      <form id="goal-withdraw-form">
        <div class="form-grid">
          <div class="field">
            <label for="withdraw-wallet">Chọn ví nhận lại tiền</label>
            <select id="withdraw-wallet" name="walletId" required>
              ${walletOptionsHtml}
            </select>
          </div>

          <div class="field">
            <label for="withdraw-amount">Số tiền muốn rút (VND)</label>
            <input id="withdraw-amount" name="amount" type="text" inputmode="numeric" data-money-input maxlength="20" placeholder="0" required>
            
            <div class="goal-chips-group">
              <button type="button" class="goal-chip" data-chip-amount="100000">100k</button>
              <button type="button" class="goal-chip" data-chip-amount="500000">500k</button>
              <button type="button" class="goal-chip" data-chip-amount="1000000">1 triệu</button>
              <button type="button" class="goal-chip goal-chip--fill" data-chip-max="1">Toàn bộ (${money(maxVal)})</button>
            </div>
          </div>

          <div class="field">
            <label for="withdraw-date">Ngày nhận tiền</label>
            <input id="withdraw-date" name="allocatedOn" type="date" value="${localDate()}" required>
          </div>

          <div class="field">
            <label for="withdraw-note">Lý do rút (tùy chọn)</label>
            <input id="withdraw-note" name="note" maxlength="250" placeholder="Ví dụ: Dùng việc gấp, chuyển mục đích...">
          </div>
        </div>

        <div class="goal-calc-preview" id="withdraw-preview" style="display:none">
          <span>Dự kiến còn lại sau khi rút:</span>
          <strong id="withdraw-preview-text">—</strong>
        </div>

        <p id="goal-form-feedback" role="status" aria-live="polite" class="form-feedback"></p>
        <div class="modal-footer">
          <button type="button" class="btn btn--outline" data-close-modal>Hủy</button>
          <button type="submit" class="btn btn--danger">Xác nhận rút tiền về ví</button>
        </div>
      </form>
    `;

    setModal(context.dialog, 'Rút tiền khỏi mục tiêu', content, 'HOÀN TIỀN VỀ VÍ');

    const form = document.querySelector('#goal-withdraw-form');
    const amountInput = form.querySelector('#withdraw-amount');
    const walletSelect = form.querySelector('#withdraw-wallet');
    const previewBox = form.querySelector('#withdraw-preview');
    const previewText = form.querySelector('#withdraw-preview-text');

    function updatePreview() {
      const rawVal = moneyInputValue(amountInput.value);
      const amt = Number(rawVal || 0);
      if (amt > 0) {
        const remaining = Math.max(0, goal.current - amt);
        const newPct = goal.target > 0 ? Math.round((remaining / goal.target) * 1000) / 10 : 0;
        previewBox.style.display = 'flex';
        previewText.textContent = `${money(remaining)} (${newPct.toLocaleString('vi-VN')}%)`;
      } else {
        previewBox.style.display = 'none';
      }
    }

    amountInput.addEventListener('input', updatePreview);

    walletSelect.addEventListener('change', () => {
      const selectedOption = walletSelect.selectedOptions[0];
      const maxAvailable = Number(selectedOption?.dataset?.max || goal.current);
      const maxBtn = form.querySelector('[data-chip-max]');
      if (maxBtn) {
        maxBtn.textContent = `Toàn bộ (${money(maxAvailable)})`;
      }
    });

    form.querySelectorAll('[data-chip-amount]').forEach(btn => {
      btn.addEventListener('click', () => {
        amountInput.value = formatMoneyInput(btn.dataset.chipAmount);
        updatePreview();
        amountInput.focus();
      });
    });

    form.querySelector('[data-chip-max]')?.addEventListener('click', () => {
      const selectedOption = walletSelect.selectedOptions[0];
      const maxAvailable = Number(selectedOption?.dataset?.max || goal.current);
      amountInput.value = formatMoneyInput(maxAvailable);
      updatePreview();
      amountInput.focus();
    });

    form.addEventListener('submit', async event => {
      event.preventDefault();
      if (!form.reportValidity()) return;
      const submit = form.querySelector('[type="submit"]');
      const feedback = form.querySelector('#goal-form-feedback');
      submit.disabled = true;
      feedback.textContent = 'Đang xử lý rút tiền…';
      try {
        const rawData = Object.fromEntries(new FormData(form));
        const amount = Number(moneyInputValue(rawData.amount));
        if (!amount || amount <= 0) throw new Error('Vui lòng nhập số tiền hợp lệ.');

        const payload = {
          walletId: Number(rawData.walletId),
          amount,
          allocatedOn: rawData.allocatedOn || localDate(),
          note: (rawData.note || '').trim()
        };

        await GoalApi.withdrawFunds(goal.id, payload);
        context.dialog.close();
        await loadLiveData();
        if (context.reload) await context.reload();
        if (context.toast) context.toast(`Đã rút ${money(amount)} từ "${goal.name}" về ví.`);
      } catch (err) {
        feedback.textContent = err.message || 'Lỗi khi rút tiền.';
        submit.disabled = false;
      }
    });
  } catch (err) {
    document.querySelector('#modal-content').innerHTML = `
      <p class="form-feedback">${safe(err.message || 'Không thể tải thông tin mục tiêu.')}</p>
      <div class="modal-footer"><button type="button" class="btn btn--outline" data-close-modal>Đóng</button></div>
    `;
  }
}

async function openDetail(goal, context) {
  setModal(context.dialog, `Chi tiết mục tiêu: ${safe(goal.name)}`, '<div class="empty-state">Đang tải lịch sử phân bổ…</div>', 'TIẾN ĐỘ & LỊCH SỬ');

  try {
    const result = await GoalApi.getGoal(goal.id);
    const detail = result.goal || result;
    const rows = detail.allocations || [];
    const progress = Number(detail.progressPercent || goal.progress || 0);

    const active = goal.status === 'ACTIVE';

    document.querySelector('#modal-content').innerHTML = `
      <div class="goal-detail-summary">
        <div>
          <small>Đã để dành</small>
          <strong class="positive">${money(detail.currentAmount || goal.current)}</strong>
        </div>
        <div>
          <small>Mục tiêu</small>
          <strong>${money(detail.targetAmount || goal.target)}</strong>
        </div>
        <div>
          <small>Còn thiếu</small>
          <strong>${money(detail.remainingAmount ?? goal.remaining)}</strong>
        </div>
        <div>
          <small>Tiến độ</small>
          <strong>${progress.toLocaleString('vi-VN', { maximumFractionDigits: 1 })}%</strong>
        </div>
      </div>

      <div class="goal-history">
        <div class="goal-history-heading">
          <h3>Lịch sử dòng tiền phân bổ (${rows.length} giao dịch)</h3>
        </div>
        <div class="goal-history-list">
          ${rows.length ? rows.map(row => {
            const isDeposit = (row.type === 'DEPOSIT' || row.allocation_type === 'DEPOSIT');
            const wName = row.walletName || row.wallet_name || 'Ví';
            const dateStr = dateText(row.allocatedOn || row.allocated_on);
            return `
              <div class="goal-history-row">
                <div class="goal-history-meta">
                  <span class="badge ${isDeposit ? 'success' : 'warn'}">${isDeposit ? 'Góp vào' : 'Rút ra'}</span>
                  <strong>${safe(wName)}</strong>
                  <small>${safe(dateStr)}${row.note ? ` · ${safe(row.note)}` : ''}</small>
                </div>
                <div class="goal-history-amount ${isDeposit ? 'positive' : 'negative'}">
                  ${isDeposit ? '+' : '−'}${money(row.amount)}
                </div>
              </div>
            `;
          }).join('') : '<p class="form-hint">Mục tiêu này chưa có lịch sử góp hoặc rút tiền nào.</p>'}
        </div>
      </div>

      <div class="modal-footer">
        ${active ? `
          <button type="button" class="btn btn--primary btn-sm" data-detail-action="deposit">
            + Góp tiền
          </button>` : ''}
        ${goal.current > 0 ? `
          <button type="button" class="btn btn--outline btn-sm" data-detail-action="withdraw">
            − Rút tiền
          </button>` : ''}
        <button type="button" class="btn btn--outline btn-sm" data-close-modal>Đóng</button>
      </div>
    `;

    document.querySelector('[data-detail-action="deposit"]')?.addEventListener('click', () => openDeposit(goal, context));
    document.querySelector('[data-detail-action="withdraw"]')?.addEventListener('click', () => openWithdraw(goal, context));
  } catch (error) {
    document.querySelector('#modal-content').innerHTML = `
      <p class="form-feedback">${safe(error.message)}</p>
      <div class="modal-footer"><button type="button" class="btn btn--outline" data-close-modal>Đóng</button></div>
    `;
  }
}

function openComplete(goal, context) {
  const content = `
    <div class="goal-complete-confirm">
      <div class="goal-complete-icon">🎉</div>
      <h3>Chúc mừng bạn đã hoàn thành mục tiêu!</h3>
      <p>Bạn đã tích lũy đủ <strong>${money(goal.current)}</strong> cho kế hoạch <strong>${safe(goal.name)}</strong>.</p>
      <p class="form-hint">Đánh dấu hoàn thành sẽ chốt thành tích này và chuyển mục tiêu vào danh sách Đã hoàn thành.</p>
    </div>
    <form id="goal-complete-form">
      <p id="goal-form-feedback" role="status" aria-live="polite" class="form-feedback"></p>
      <div class="modal-footer">
        <button type="button" class="btn btn--outline" data-close-modal>Để sau</button>
        <button type="submit" class="btn btn--success">Xác nhận Hoàn thành</button>
      </div>
    </form>
  `;

  setModal(context.dialog, 'Hoàn thành mục tiêu', content, 'VINH DANH MỤC TIÊU');

  const form = document.querySelector('#goal-complete-form');
  form.addEventListener('submit', async event => {
    event.preventDefault();
    const submit = form.querySelector('[type="submit"]');
    const feedback = form.querySelector('#goal-form-feedback');
    submit.disabled = true;
    feedback.textContent = 'Đang cập nhật trạng thái…';
    try {
      await GoalApi.completeGoal(goal.id);
      context.dialog.close();
      await loadLiveData();
      if (context.reload) await context.reload();
      if (context.toast) context.toast(`Chúc mừng bạn đã hoàn thành mục tiêu "${goal.name}"! 🏆`);
    } catch (err) {
      feedback.textContent = err.message || 'Lỗi khi hoàn thành mục tiêu.';
      submit.disabled = false;
    }
  });
}

function confirmCancel(goal, context) {
  setModal(context.dialog, 'Dừng mục tiêu', `
    <p class="form-hint">Bạn có muốn dừng mục tiêu <strong>${safe(goal.name)}</strong>?</p>
    <div class="hint-box goal-note" style="margin: 12px 0">
      Tiền đang earmark trong mục tiêu (${money(goal.current)}) sẽ được gỡ phong tỏa và hoàn lại quyền sử dụng đầy đủ trong các ví tương ứng. Lịch sử phân bổ vẫn được lưu giữ.
    </div>
    <form id="goal-cancel-form">
      <p id="goal-form-feedback" role="status" aria-live="polite" class="form-feedback"></p>
      <div class="modal-footer">
        <button type="button" class="btn btn--outline" data-close-modal>Giữ mục tiêu</button>
        <button type="submit" class="btn btn--danger">Xác nhận dừng mục tiêu</button>
      </div>
    </form>
  `, 'DỪNG KẾ HOẠCH');

  const form = document.querySelector('#goal-cancel-form');
  form.addEventListener('submit', async event => {
    event.preventDefault();
    const button = form.querySelector('[type=submit]');
    const feedback = form.querySelector('#goal-form-feedback');
    button.disabled = true;
    feedback.textContent = 'Đang xử lý…';
    try {
      await GoalApi.cancelGoal(goal.id);
      context.dialog.close();
      await loadLiveData();
      if (context.reload) await context.reload();
      if (context.toast) context.toast('Đã dừng mục tiêu và hoàn tiền về ví.');
    } catch (error) {
      feedback.textContent = error.message;
      button.disabled = false;
    }
  });
}

export function handleGoalAction(action, context) {
  if (!action.startsWith('goal:')) return false;
  const parts = action.split(':');
  const verb = parts[1];
  const id = parts[2];

  if (verb === 'create') {
    openCreate(context);
    return true;
  }

  if (verb === 'filter') {
    goalState.filter = id || 'ALL';
    updateCardsView();
    return true;
  }

  if (verb === 'clear-filter') {
    goalState.filter = 'ALL';
    goalState.search = '';
    const searchBox = document.querySelector('#goal-search-box');
    if (searchBox) searchBox.value = '';
    updateCardsView();
    return true;
  }

  const goal = byId(id);
  if (!goal) {
    if (context.toast) context.toast('Không tìm thấy mục tiêu này.');
    return true;
  }

  if (verb === 'deposit') {
    openDeposit(goal, context);
  } else if (verb === 'withdraw') {
    openWithdraw(goal, context);
  } else if (verb === 'complete') {
    openComplete(goal, context);
  } else if (verb === 'detail') {
    void openDetail(goal, context);
  } else if (verb === 'edit') {
    openEdit(goal, context);
  } else {
    if (context.toast) context.toast('Thao tác mục tiêu không hợp lệ.');
  }

  return true;
}

// Global live interactive listeners for search and sort
if (typeof document !== 'undefined' && document.addEventListener) {
  document.addEventListener('input', event => {
    if (event.target.id === 'goal-search-box') {
      goalState.search = event.target.value;
      updateCardsView();
    }
  });

  document.addEventListener('change', event => {
    if (event.target.id === 'goal-sort-dropdown') {
      goalState.sortBy = event.target.value;
      updateCardsView();
    }
  });
}
