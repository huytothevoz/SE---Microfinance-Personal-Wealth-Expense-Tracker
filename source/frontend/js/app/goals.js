/** WealthFlow savings-goal page and actions backed by /api/goals. */
import { loadView } from './view-loader.js';
import { goals, dataStatus } from '../data/live-data.js?v=wealth-ui-2';
import { GoalApi } from '../services/goal-api.js';
import { money, safe, formatMoneyInput, moneyInputValue } from './ui-core.js?v=wealth-ui-2';

const statusText = { ACTIVE: 'Đang để dành', COMPLETED: 'Đã đạt', CANCELLED: 'Đã dừng' };
const priorityText = { LOW: 'Thoải mái', MEDIUM: 'Vừa phải', HIGH: 'Ưu tiên cao' };
const byId = id => goals.find(goal => String(goal.id) === String(id));
const dateText = value => value ? new Intl.DateTimeFormat('vi-VN').format(new Date(`${value}T00:00:00`)) : 'Chưa đặt ngày';
const localDate = date => `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
const tomorrow = () => { const date = new Date(); date.setDate(date.getDate() + 1); return localDate(date); };

function goalCards() {
  if (dataStatus.goals?.state === 'loading' || dataStatus.goals?.state === 'idle') return '<div class="empty-state"><strong>Đang tải mục tiêu…</strong></div>';
  if (dataStatus.goals?.state !== 'ready') return `<div class="empty-state"><strong>Chưa tải được mục tiêu</strong><p>${safe(dataStatus.goals?.message || 'Vui lòng kiểm tra kết nối máy chủ.')}</p></div>`;
  if (!goals.length) return '<div class="empty-state"><strong>Bạn chưa có mục tiêu nào</strong><p>Hãy bắt đầu bằng một khoản bạn thật sự muốn để dành.</p></div>';
  return goals.map(goal => {
    const progress = Math.max(0, Number(goal.progress || 0));
    const barWidth = Math.min(100, progress);
    const active = goal.status === 'ACTIVE';
    const completed = goal.status === 'COMPLETED';
    return `<article class="goal-card goal-card--${safe(goal.status.toLowerCase())}">
      <div class="goal-top"><span class="goal-emoji" aria-hidden="true">${safe(goal.icon)}</span><div class="goal-heading"><h3>${safe(goal.name)}</h3><p>${safe(priorityText[goal.priority] || 'Vừa phải')}</p></div><span class="badge ${completed ? 'success' : active ? 'blue' : 'neutral'}">${safe(statusText[goal.status] || goal.status)}</span></div>
      <div class="goal-amount"><strong>${money(goal.current)}</strong><small>trên ${money(goal.target)}</small></div>
      <div class="bar-track" aria-label="Đã đạt ${safe(progress.toLocaleString('vi-VN'))}%"><div class="bar-fill" style="width:${barWidth}%"></div></div>
      <div class="goal-footer"><span>${safe(progress.toLocaleString('vi-VN'))}%</span><span>Hẹn đến ${safe(dateText(goal.deadline))}</span></div>
      <div class="goal-plan"><span>Còn thiếu <strong>${money(goal.remaining)}</strong></span>${active && goal.remaining > 0 ? `<span>Nên để dành <strong>${money(goal.monthly)}/tháng</strong></span>` : ''}</div>
      <div class="goal-card-actions"><button type="button" class="btn btn--outline btn-sm" data-action="goal:detail:${safe(goal.id)}">Lịch sử</button><button type="button" class="btn btn--primary btn-sm" data-action="goal:edit:${safe(goal.id)}" ${goal.status === 'CANCELLED' ? 'disabled' : ''}>Chỉnh sửa</button></div>
    </article>`;
  }).join('');
}

export async function renderGoalsPage() {
  const rawHtml = await loadView('goals');
  const temp = document.createElement('div'); temp.innerHTML = rawHtml;
  const active = goals.filter(goal => goal.status === 'ACTIVE');
  const tracked = goals.filter(goal => goal.status !== 'CANCELLED');
  const target = tracked.reduce((sum, goal) => sum + goal.target, 0);
  const current = tracked.reduce((sum, goal) => sum + goal.current, 0);
  const progress = target > 0 ? current / target * 100 : 0;
  temp.querySelector('#goal-active-count').textContent = `${active.length} mục tiêu`;
  temp.querySelector('#goal-target-total').textContent = money(target);
  temp.querySelector('#goal-allocated-total').textContent = money(current);
  temp.querySelector('#goal-progress-percent').textContent = `${progress.toLocaleString('vi-VN', { maximumFractionDigits: 1 })}%`;
  temp.querySelector('#goal-card-list').innerHTML = goalCards();
  return temp.innerHTML;
}

function setModal(dialog, title, content) {
  document.querySelector('#modal-overline').textContent = '';
  document.querySelector('#modal-title').textContent = title;
  document.querySelector('#modal-content').innerHTML = content;
  dialog.showModal();
}
function priorityOptions(selected = 'MEDIUM') {
  return [['LOW','Thoải mái'],['MEDIUM','Vừa phải'],['HIGH','Ưu tiên cao']].map(([value, label]) => `<option value="${value}" ${value === selected ? 'selected' : ''}>${label}</option>`).join('');
}
function goalForm(goal = null) {
  const editing = Boolean(goal);
  return `<form id="goal-form"><div class="form-grid goal-form-grid">
    <div class="field"><label for="goal-name">Bạn muốn để dành cho điều gì?</label><input id="goal-name" name="name" maxlength="150" value="${safe(goal?.name || '')}" required></div>
    <div class="field"><label for="goal-target">Số tiền muốn đạt</label><input id="goal-target" name="targetAmount" type="text" inputmode="numeric" data-money-input maxlength="24" value="${editing ? safe(formatMoneyInput(goal.target)) : ''}" required></div>
    <div class="field"><label for="goal-deadline">Muốn hoàn thành trước ngày</label><input id="goal-deadline" name="deadline" type="date" min="${tomorrow()}" value="${safe(goal?.deadline || '')}" required></div>
    <div class="field"><label for="goal-priority">Mức ưu tiên</label><select id="goal-priority" name="priority">${priorityOptions(goal?.priority)}</select></div>
  </div><p id="goal-form-feedback" role="status" aria-live="polite"></p>
  <div class="modal-footer">${editing ? '<button type="button" class="btn btn--danger" data-goal-stop>Dừng mục tiêu</button>' : ''}<button type="button" class="btn btn--outline" data-close-modal>Hủy</button><button type="submit" class="btn btn--primary">${editing ? 'Lưu thay đổi' : 'Tạo mục tiêu'}</button></div></form>`;
}
function bindForm(form, request, { dialog, toast, reload, success }) {
  form.addEventListener('submit', async event => {
    event.preventDefault(); if (!form.reportValidity()) return;
    const submit = form.querySelector('[type=submit]'); const feedback = form.querySelector('#goal-form-feedback');
    submit.disabled = true; feedback.textContent = 'Đang lưu…';
    try { const data = Object.fromEntries(new FormData(form)); data.targetAmount = moneyInputValue(data.targetAmount); await request(data); dialog.close(); await reload(); toast(success); }
    catch (error) { feedback.textContent = error.message; submit.disabled = false; }
  });
}
function openCreate(context) {
  setModal(context.dialog, 'Thêm mục tiêu', goalForm());
  bindForm(document.querySelector('#goal-form'), data => GoalApi.createGoal(data), { ...context, success: 'Đã tạo mục tiêu mới.' });
}
function openEdit(goal, context) {
  setModal(context.dialog, 'Chỉnh sửa mục tiêu', goalForm(goal));
  document.querySelector('[data-goal-stop]')?.addEventListener('click', () => confirmCancel(goal, context));
  bindForm(document.querySelector('#goal-form'), data => GoalApi.updateGoal(goal.id, data), { ...context, success: 'Đã cập nhật mục tiêu.' });
}
async function openDetail(goal, context) {
  setModal(context.dialog, 'Lịch sử', '<div class="empty-state">Đang tải lịch sử…</div>');
  try {
    const result = await GoalApi.getGoal(goal.id); const detail = result.goal || result; const rows = detail.allocations || [];
    document.querySelector('#modal-content').innerHTML = `<div class="goal-detail-summary"><div><small>Đã để dành</small><strong>${money(detail.currentAmount)}</strong></div><div><small>Muốn đạt</small><strong>${money(detail.targetAmount)}</strong></div><div><small>Còn thiếu</small><strong>${money(detail.remainingAmount)}</strong></div></div>
    <div class="goal-history">${rows.length ? rows.map(row => `<div class="goal-history-row"><span><strong>${row.type === 'DEPOSIT' ? 'Chuyển vào mục tiêu' : 'Lấy khỏi mục tiêu'}</strong><small>${safe(row.walletName)} · ${safe(dateText(row.allocatedOn))}${row.note ? ` · ${safe(row.note)}` : ''}</small></span><b class="${row.type === 'DEPOSIT' ? 'positive' : ''}">${row.type === 'DEPOSIT' ? '+' : '−'}${money(row.amount)}</b></div>`).join('') : '<p class="form-hint">Chưa có lần để dành nào.</p>'}</div><div class="modal-footer"><button type="button" class="btn btn--outline" data-close-modal>Đóng</button></div>`;
  } catch (error) { document.querySelector('#modal-content').innerHTML = `<p class="form-hint">${safe(error.message)}</p><div class="modal-footer"><button type="button" class="btn btn--outline" data-close-modal>Đóng</button></div>`; }
}
function confirmCancel(goal, context) {
  setModal(context.dialog, 'Dừng mục tiêu', `<p class="form-hint">Bạn có muốn dừng <strong>${safe(goal.name)}</strong>? Tiền đang để dành sẽ được gỡ khỏi mục tiêu và dùng lại trong đúng các ví đã chuyển vào. Lịch sử vẫn được giữ.</p><form id="goal-cancel-form"><p id="goal-form-feedback" role="status" aria-live="polite"></p><div class="modal-footer"><button type="button" class="btn btn--outline" data-close-modal>Giữ mục tiêu</button><button type="submit" class="btn btn--danger">Dừng mục tiêu</button></div></form>`);
  const form = document.querySelector('#goal-cancel-form');
  form.addEventListener('submit', async event => { event.preventDefault(); const button = form.querySelector('[type=submit]'); const feedback = form.querySelector('#goal-form-feedback'); button.disabled = true; feedback.textContent = 'Đang lưu…'; try { await GoalApi.cancelGoal(goal.id); context.dialog.close(); await context.reload(); context.toast('Đã dừng mục tiêu.'); } catch (error) { feedback.textContent = error.message; button.disabled = false; } });
}
export function handleGoalAction(action, context) {
  if (!action.startsWith('goal:')) return false;
  const [, verb, id] = action.split(':');
  if (verb === 'create') { openCreate(context); return true; }
  const goal = byId(id); if (!goal) { context.toast('Không tìm thấy mục tiêu này.'); return true; }
  if (verb === 'detail') void openDetail(goal, context); else if (verb === 'edit') openEdit(goal, context); else context.toast('Thao tác mục tiêu không hợp lệ.');
  return true;
}
