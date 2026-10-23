/** WealthFlow loans backed only by authenticated PHP APIs. */
import { loadView } from './view-loader.js';
import { loans, wallets, dataStatus } from '../data/live-data.js?v=wealth-ui-2';
import { LoanApi } from '../services/loan-api.js';
import { money, safe, formatMoneyInput, moneyInputValue, percent } from './ui-core.js?v=wealth-ui-2';

const localDate = () => new Date().toLocaleDateString('en-CA');
const dateText = value => value ? new Intl.DateTimeFormat('vi-VN').format(new Date(`${value}T00:00:00`)) : '—';
const byId = id => loans.find(loan => String(loan.id) === String(id));
const walletOptions = () => wallets.filter(wallet =>
  String(wallet.status || 'ACTIVE').toUpperCase() === 'ACTIVE'
  && String(wallet.type || wallet.walletType || '').toUpperCase() !== 'CREDIT'
  && String(wallet.currency || '').toUpperCase() === 'VND'
);

function cards() {
  const state = dataStatus.loans?.state;
  if (state === 'loading' || state === 'idle') return '<div class="empty-state"><strong>Đang tải khoản vay…</strong></div>';
  if (state !== 'ready') return `<div class="empty-state"><strong>Chưa tải được khoản vay</strong><p>${safe(dataStatus.loans?.message || 'Vui lòng kiểm tra kết nối máy chủ.')}</p><button type="button" class="btn btn--outline btn-sm" data-page="loans">Thử lại</button></div>`;
  if (!loans.length) return '<div class="empty-state"><strong>Bạn chưa có khoản vay hoặc khoản cho vay</strong><p>Thêm khoản mới để hệ thống theo dõi dư gốc, lãi/phí và lịch thanh toán.</p></div>';
  return loans.map(loan => {
    const principal = Number(loan.principalOriginal || 0);
    const remaining = Number(loan.remainingPrincipal || 0);
    const paid = Math.max(0, principal - remaining);
    const direction = loan.direction === 'LENT' ? 'Cho vay' : 'Đi vay';
    const status = loan.status === 'PAID_OFF' ? 'Đã tất toán' : loan.status === 'DEFAULTED' ? 'Quá hạn' : 'Đang hoạt động';
    return `<article class="loan-card">
      <div class="status-row"><span class="badge ${loan.direction === 'BORROWED' ? 'warn' : 'blue'}">${direction}</span><span class="description">${safe(status)}</span></div>
      <div><h3>${safe(loan.name)}</h3><p>Đối tác: ${safe(loan.partnerName || '—')} · ${safe(loan.walletName || '—')}</p></div>
      <div><span class="description">Dư gốc còn lại</span><div class="loan-amount">${money(remaining)}</div><div class="budget-foot" style="margin:7px 0">Đã thanh toán ${money(paid)} / ${money(principal)}</div><div class="bar-track"><div class="bar-fill" style="width:${percent(paid, principal)}%"></div></div></div>
      <div class="status-row"><span class="description">Hạn tiếp: ${safe(dateText(loan.nextDueDate))}</span><span class="goal-card-actions"><button type="button" class="btn btn--outline btn-sm" data-action="loan:detail:${safe(loan.id)}">Chi tiết</button>${loan.status === 'ACTIVE' ? `<button type="button" class="btn btn--primary btn-sm" data-action="loan:payment:${safe(loan.id)}">${loan.direction === 'LENT' ? 'Ghi nhận thu' : 'Ghi nhận trả'}</button>` : ''}</span></div>
    </article>`;
  }).join('');
}

export async function renderLoansPage() {
  const rawHtml = await loadView('loans');
  const temp = document.createElement('div');
  temp.innerHTML = rawHtml;
  const ready = dataStatus.loans?.state === 'ready';
  const active = ready ? loans.filter(loan => loan.status === 'ACTIVE') : [];
  const borrowed = active.filter(loan => loan.direction === 'BORROWED').reduce((sum, loan) => sum + Number(loan.remainingPrincipal || 0), 0);
  const lent = active.filter(loan => loan.direction === 'LENT').reduce((sum, loan) => sum + Number(loan.remainingPrincipal || 0), 0);
  temp.querySelector('#loan-borrowed-total').textContent = ready ? money(borrowed) : '—';
  temp.querySelector('#loan-lent-total').textContent = ready ? money(lent) : '—';
  temp.querySelector('#loan-active-count').textContent = ready ? String(active.length) : '—';
  temp.querySelector('#loan-card-list').innerHTML = cards();
  return temp.innerHTML;
}

function setModal(dialog, title, html) {
  document.querySelector('#modal-overline').textContent = '';
  document.querySelector('#modal-title').textContent = title;
  document.querySelector('#modal-content').innerHTML = html;
  dialog.showModal();
}

function selectWallet(name, selected = '') {
  return `<div class="field"><label for="loan-${name}">Ví VND</label><select id="loan-${name}" name="${safe(name)}" required><option value="">Chọn ví</option>${walletOptions().map(wallet => `<option value="${safe(wallet.id)}" ${String(wallet.id) === String(selected) ? 'selected' : ''}>${safe(wallet.name)} · ${money(Number(wallet.balance))}</option>`).join('')}</select></div>`;
}

function bind(form, request, context, success) {
  form.addEventListener('submit', async event => {
    event.preventDefault();
    if (!form.reportValidity()) return;
    const button = form.querySelector('[type=submit]');
    const feedback = form.querySelector('[role=status]');
    const data = Object.fromEntries(new FormData(form));
    for (const key of ['principalAmount', 'interestFeeAmount']) {
      if (key in data) data[key] = moneyInputValue(data[key]);
    }
    data.walletId = Number(data.walletId);
    if ('termMonths' in data) data.termMonths = Number(data.termMonths);
    button.disabled = true;
    feedback.textContent = 'Đang lưu và đối chiếu bút toán…';
    try {
      await request(data);
      context.dialog.close();
      await context.reload();
      context.toast(success);
    } catch (error) {
      feedback.textContent = error.message;
      button.disabled = false;
    }
  });
}

function openCreate(context) {
  if (!walletOptions().length) {
    context.toast('Bạn cần một ví tài sản VND đang hoạt động để ghi nhận khoản vay.');
    return;
  }
  setModal(context.dialog, 'Thêm khoản vay hoặc cho vay', `<form id="loan-form"><p class="form-hint">Đi vay sẽ cộng tiền vào ví và ghi tăng nghĩa vụ nợ. Cho vay sẽ trừ tiền khỏi ví và ghi tăng khoản phải thu; cả hai không được tính là thu nhập hoặc chi phí.</p><div class="form-grid">
    <div class="field"><label for="loan-title">Tên khoản</label><input id="loan-title" name="title" maxlength="150" required></div>
    <div class="field"><label for="loan-partner">Đối tác</label><input id="loan-partner" name="partnerName" maxlength="150" required></div>
    <div class="field"><label for="loan-direction">Loại</label><select id="loan-direction" name="direction" required><option value="BORROWED">Tôi đi vay</option><option value="LENT">Tôi cho vay</option></select></div>
    <div class="field"><label for="loan-principal">Tiền gốc (VND)</label><input id="loan-principal" name="principalAmount" type="text" inputmode="numeric" data-money-input required></div>
    <div class="field"><label for="loan-rate">Lãi suất năm (%)</label><input id="loan-rate" name="interestRateYear" type="number" min="0" max="100" step="0.0001" value="0" required></div>
    <div class="field"><label for="loan-term">Kỳ hạn (tháng)</label><input id="loan-term" name="termMonths" type="number" min="1" max="600" value="12" required></div>
    ${selectWallet('walletId')}
    <div class="field"><label for="loan-date">Ngày giải ngân</label><input id="loan-date" name="startDate" type="date" max="${localDate()}" value="${localDate()}" required></div>
  </div><p role="status" aria-live="polite"></p><div class="modal-footer"><button type="button" class="btn btn--outline" data-close-modal>Hủy</button><button type="submit" class="btn btn--primary">Lưu khoản vay</button></div></form>`);
  bind(document.querySelector('#loan-form'), data => LoanApi.createLoan(data), context, 'Đã ghi nhận khoản vay và cập nhật số dư ví.');
}

function openPayment(loan, context) {
  if (!walletOptions().length) {
    context.toast('Bạn cần một ví tài sản VND đang hoạt động để thanh toán.');
    return;
  }
  setModal(context.dialog, loan.direction === 'LENT' ? `Thu tiền ${loan.name}` : `Trả tiền ${loan.name}`, `<form id="loan-form"><p class="form-hint">Tiền gốc làm giảm dư nợ. Lãi/phí được ghi riêng vào thu nhập hoặc chi phí để báo cáo dòng tiền không bị sai.</p><div class="form-grid">
    <div class="field"><label for="loan-principal">Tiền gốc (VND)</label><input id="loan-principal" name="principalAmount" type="text" inputmode="numeric" data-money-input value="${safe(formatMoneyInput(loan.remainingPrincipal))}" required></div>
    <div class="field"><label for="loan-interest">Tiền lãi/phí (VND)</label><input id="loan-interest" name="interestFeeAmount" type="text" inputmode="numeric" data-money-input value="0" required></div>
    ${selectWallet('walletId', loan.walletId)}
    <div class="field"><label for="loan-date">Ngày thanh toán</label><input id="loan-date" name="paidOn" type="date" max="${localDate()}" value="${localDate()}" required></div>
  </div><p role="status" aria-live="polite"></p><div class="modal-footer"><button type="button" class="btn btn--outline" data-close-modal>Hủy</button><button type="submit" class="btn btn--primary">Xác nhận thanh toán</button></div></form>`);
  bind(document.querySelector('#loan-form'), data => LoanApi.recordPayment(loan.id, data), context, 'Đã ghi nhận thanh toán và cập nhật dư gốc.');
}

async function openDetail(loan, context) {
  setModal(context.dialog, `Chi tiết ${loan.name}`, '<div class="empty-state">Đang tải lịch trả và thanh toán…</div>');
  try {
    const result = await LoanApi.getLoan(loan.id);
    const detail = result.loan || result;
    const schedules = detail.schedules || [];
    const payments = detail.payments || [];
    document.querySelector('#modal-content').innerHTML = `<div class="goal-detail-summary"><div><small>Tiền gốc ban đầu</small><strong>${money(Number(detail.principalOriginal))}</strong></div><div><small>Dư gốc</small><strong>${money(Number(detail.remainingPrincipal))}</strong></div><div><small>Lãi suất năm</small><strong>${safe(detail.interestRateYear)}%</strong></div><div><small>Kỳ hạn</small><strong>${safe(detail.termMonths)} tháng</strong></div></div>
      <div class="goal-history"><h3>Lịch thanh toán</h3>${schedules.length ? schedules.map(row => `<div class="goal-history-row"><span><strong>Kỳ ${safe(row.periodNumber)} · ${safe(dateText(row.dueDate))}</strong><small>Gốc ${money(Number(row.principalDue))} · Lãi dự kiến ${money(Number(row.interestDue))}</small></span><b>${row.status === 'PAID' ? 'Đã trả' : row.status === 'PARTIAL' ? 'Một phần' : 'Chờ trả'}</b></div>`).join('') : '<p class="form-hint">Chưa có lịch thanh toán.</p>'}</div>
      <div class="goal-history"><h3>Lịch sử thực trả</h3>${payments.length ? payments.map(row => `<div class="goal-history-row"><span><strong>${safe(dateText(row.paidOn))}</strong><small>Gốc ${money(Number(row.principalAmount))} · Lãi/phí ${money(Number(row.interestFeeAmount))}</small></span><b>${money(Number(row.principalAmount) + Number(row.interestFeeAmount))}</b></div>`).join('') : '<p class="form-hint">Chưa có lần thanh toán nào.</p>'}</div>
      <div class="modal-footer"><button type="button" class="btn btn--outline" data-close-modal>Đóng</button></div>`;
  } catch (error) {
    document.querySelector('#modal-content').innerHTML = `<p class="form-hint">${safe(error.message)}</p><div class="modal-footer"><button type="button" class="btn btn--outline" data-close-modal>Đóng</button></div>`;
  }
}

export function handleLoanAction(action, context) {
  if (!action.startsWith('loan:')) return false;
  const [, verb, id] = action.split(':');
  if (verb === 'create') {
    if (dataStatus.loans?.state !== 'ready') context.toast('Chưa kết nối được API khoản vay.'); else openCreate(context);
    return true;
  }
  const loan = byId(id);
  if (!loan) {
    context.toast('Không tìm thấy khoản vay này.');
    return true;
  }
  if (verb === 'detail') void openDetail(loan, context);
  else if (verb === 'payment') openPayment(loan, context);
  else context.toast('Thao tác khoản vay không hợp lệ.');
  return true;
}
