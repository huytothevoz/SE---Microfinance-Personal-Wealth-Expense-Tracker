/** WealthFlow live, account-scoped page templates. No static financial figures. */
import { wallets, transactions, budgets, recurring, goals, loans, gold, notifications, categories, adminUsers, analytics, expenseAnalytics, dataStatus, mutateLiveData, loadLiveData } from '../data/live-data.js?v=finance-ui-1';
import { icon, formatMoneyInput, moneyInputValue } from './ui-core.js?v=wealth-ui-2';
import { httpClient } from '../services/http-client.js';
import { GoalApi } from '../services/goal-api.js';

const ITEMS = { wallets, transactions, budgets, recurring, goals, loans, gold, notifications, categories, adminUsers };
const TITLES = { wallets: 'Ví', transactions: 'Giao dịch', categories: 'Danh mục', budgets: 'Ngân sách', recurring: 'Giao dịch định kỳ', goals: 'Mục tiêu tiết kiệm', loans: 'Vay và cho vay', gold: 'Tài sản vàng', notifications: 'Thông báo', adminUsers: 'Người dùng', expenseAnalytics: 'Dashboard chi tiêu' };
const esc = value => String(value ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
const vnd = n => Number.isFinite(Number(n)) ? new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND', maximumFractionDigits: 0 }).format(Number(n)) : '—';
const val = (obj, ...keys) => keys.map(k => obj?.[k]).find(x => x !== null && x !== undefined && x !== '');
const count = list => list.length;
const sum = list => list.reduce((s, row) => s + Math.abs(Number(row.amount || 0)), 0);
const ownMonth = iso => String(iso ?? '').slice(0, 7);
const month = () => { const d = new Date(); return `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2, '0')}`; };
const posted = () => transactions.filter(t => t.status === 'POSTED');
const TRANSACTION_TYPE_LABELS = { income: 'Thu nhập', expense: 'Chi tiêu', transfer: 'Chuyển tiền', savings_goal: 'Mục tiêu tiết kiệm', loan_disbursement: 'Giải ngân khoản vay', loan_payment: 'Thanh toán khoản vay', gold_purchase: 'Mua vàng', gold_sale: 'Bán vàng', other: 'Số dư' };
const DETAIL_FIELDS = {
  wallets: [['Tên ví','name'],['Loại ví','type'],['Tiền tệ','currency'],['Số dư','balance','money'],['Trạng thái','status','status']],
  categories: [['Tên danh mục','name'],['Loại danh mục','type','categoryType'],['Ghi chú','description']],
  budgets: [['Danh mục','name'],['Tháng áp dụng','month'],['Hạn mức','limit','money'],['Đã chi','spent','money'],['Còn lại','remaining','money']],
  recurring: [['Tên khoản định kỳ','name'],['Mô tả','description'],['Số tiền','amount','money'],['Chu kỳ','frequency'],['Ngày tiếp theo','nextRun','date'],['Trạng thái','status','status']],
  goals: [['Mục tiêu','name'],['Đã tích lũy','current','money'],['Mục tiêu cần đạt','target','money'],['Ngày hoàn thành','deadline','date'],['Ưu tiên','priority','priority'],['Trạng thái','status','status']],
  loans: [['Khoản vay','name'],['Hình thức','direction','loanDirection'],['Dư nợ còn lại','remainingPrincipal','money'],['Tiền tệ','currency'],['Ngày đến hạn','nextDueDate','date'],['Trạng thái','status','status']],
  gold: [['Tài sản vàng','name'],['Loại vàng','goldType','goldType'],['Khối lượng (chỉ)','quantityChi'],['Giá trị hiện tại','currentValue','money'],['Ngày định giá','valuationDate','date'],['Trạng thái','status','status']],
  notifications: [['Thông báo','title'],['Nội dung','body'],['Mức độ','priority','priority'],['Ngày nhận','createdAt','date'],['Trạng thái','status','status']],
  adminUsers: [['Họ và tên','fullName|name'],['Email','email'],['Vai trò','role','role'],['Trạng thái','status','status']]
};
const stateOf = key => dataStatus[key]?.state || 'idle';
const ready = key => stateOf(key) === 'ready';
const loading = key => stateOf(key) === 'loading' || stateOf(key) === 'idle';
const message = (title, description) => `<div class="empty-state wf-empty"><strong>${esc(title)}</strong><p>${esc(description)}</p></div>`;
const problem = key => loading(key) ? message('Đang tải dữ liệu…', 'Đang lấy dữ liệu của tài khoản hiện tại từ máy chủ.')
  : ready(key) ? null : `<div class="wf-error" role="alert"><strong>${esc(TITLES[key] || key)}: Chưa kết nối API</strong><p>${esc(dataStatus[key]?.message || 'Chưa thể tải dữ liệu.')}</p><small>Không sử dụng dữ liệu mô phỏng để thay thế.</small></div>`;
const empty = (key, what) => message(`Chưa có ${what}`, 'Tài khoản mới bắt đầu trống. Hãy thêm dữ liệu đầu tiên để xem thống kê.');
const addButton = (key, text = `Thêm ${TITLES[key] || 'mới'}`) => `<button type="button" class="btn btn--primary btn-sm" data-action="live:create:${key}" ${(!ready(key) || !['transactions','categories','budgets','recurring'].includes(key)) ? 'disabled title="Chức năng ghi chưa được xác nhận an toàn với phiên bản backend này"' : ''}>${icon('plus')} ${esc(text)}</button>`;
const display = (x, fmt, currency='VND') => fmt === 'money' ? (Number.isFinite(Number(x)) ? new Intl.NumberFormat('vi-VN', {style:'currency',currency: ['VND','USD','EUR'].includes(String(currency)) ? String(currency) : 'VND',maximumFractionDigits: currency==='VND'?0:2}).format(Number(x)) : '—') : fmt === 'date' ? esc(String(x ?? '').slice(0, 10) || '—') : esc(x ?? '—');
const baseStyle = `<style>
.wf-empty {padding:38px 20px;line-height:1.6;text-align:center}.wf-empty p{margin:6px 0;color:var(--text-secondary,#69778a)}
.wf-error{margin:16px 0;border:1px solid #d8a9a9;border-radius:12px;padding:18px;background:rgba(190,55,55,.05)}.wf-error p{margin:8px 0}
.wf-muted{color:var(--text-secondary,#68778a)}.wf-graph{display:flex;gap:12px;height:200px;align-items:end;border-bottom:1px solid #cbd5e1;padding:10px 4px 18px}
.wf-bar-group{flex:1;display:flex;align-items:end;gap:3px;justify-content:center;height:100%;position:relative}
.wf-bar{min-width:12px;max-width:35px;flex:1;background:#16869e;border-radius:5px 5px 0 0}
.wf-bar.out{background:#ef9b71}.wf-bar-group small{position:absolute;top:100%;padding-top:5px;white-space:nowrap;font-size:10px}
.wf-progress{height:9px;border-radius:8px;background:#d7e3e9;overflow:hidden}.wf-progress i{display:block;background:#16869e;height:100%}
.wf-table-actions{white-space:nowrap}.wf-grid-actions{display:flex;flex-wrap:wrap;gap:8px}.wf-stat-note{font-size:12px;color:#68778a;margin-top:7px}
.expense-dashboard-grid{display:grid;grid-template-columns:minmax(280px,.9fr) minmax(420px,1.4fr);gap:18px}.expense-donut-layout{display:grid;grid-template-columns:210px 1fr;gap:20px;align-items:center}.expense-donut{width:210px;height:210px;transform:rotate(-90deg)}.expense-donut-bg{fill:none;stroke:var(--surface-soft,#e7eef2);stroke-width:18}.expense-donut-segment{fill:none;stroke-width:18}.expense-legend{display:grid;gap:10px}.expense-legend-row{display:grid;grid-template-columns:12px 1fr auto;gap:8px;align-items:center}.expense-legend-dot{width:10px;height:10px;border-radius:50%}.expense-bars{height:250px;display:flex;gap:14px;align-items:flex-end;border-bottom:1px solid var(--border,#d7e0e5);padding:18px 8px 24px}.expense-month{flex:1;height:100%;display:flex;gap:5px;align-items:flex-end;justify-content:center;position:relative}.expense-column{width:min(32px,42%);min-height:2px;border-radius:7px 7px 2px 2px;background:#44c2c7}.expense-column.is-expense{background:#f08b65}.expense-month small{position:absolute;top:calc(100% + 7px);font-size:11px}.expense-chart-legend{display:flex;gap:18px;flex-wrap:wrap;margin-top:14px}.expense-chart-legend span{display:flex;align-items:center;gap:7px}.expense-chart-legend i{width:10px;height:10px;border-radius:50%;background:#44c2c7}.expense-chart-legend .is-expense{background:#f08b65}
@media(max-width:640px){.wf-graph{height:150px;gap:3px}.wf-bar{min-width:5px}}
@media(max-width:960px){.expense-dashboard-grid{grid-template-columns:1fr}.expense-donut-layout{grid-template-columns:1fr;justify-items:center}.expense-legend{width:100%}}
</style>`;
function table(key, columns, list = ITEMS[key]) {
  const issue = problem(key);
  if (issue) return issue;
  if (!list.length) return empty(key, TITLES[key]?.toLowerCase() || 'dữ liệu');
  return `<div class="data-table-wrap"><table class="data-table"><thead><tr>${columns.map(([label]) => `<th>${esc(label)}</th>`).join('')}<th></th></tr></thead><tbody>${list.map(row => {
    const moneyCurrency = key === 'wallets'
      ? row.currency
      : key === 'transactions'
        ? (row.hasBaseAmount ? 'VND' : row.currency)
        : 'VND';
    return `<tr>${columns.map(([, field, fmt]) => {
      if (key === 'categories' && field === 'description' && !row.description) {
        return `<td><button type="button" class="text-action" data-action="live:edit:categories:${encodeURIComponent(String(row.id ?? ''))}" title="Thêm ghi chú" aria-label="Thêm ghi chú cho ${esc(row.name)}">+</button></td>`;
      }
      return `<td>${display(val(row, ...field.split('|')), fmt, moneyCurrency)}</td>`;
    }).join('')}<td class="wf-table-actions"><button type="button" class="text-action" data-action="live:detail:${key}:${encodeURIComponent(String(row.id ?? ''))}">Chi tiết</button>${key === 'recurring' && row.id && ['ACTIVE','PAUSED'].includes(String(row.status).toUpperCase()) ? `<button type="button" class="text-action" data-action="live:${String(row.status).toUpperCase() === 'ACTIVE' ? 'pause' : 'resume'}:recurring:${encodeURIComponent(String(row.id))}">${String(row.status).toUpperCase() === 'ACTIVE' ? 'Tạm dừng' : 'Tiếp tục'}</button>` : ''}</td></tr>`;
  }).join('')}</tbody></table></div>`;
}
function card(label, value, caption) { return `<div class="kpi-card"><div class="kpi-top"><span class="kpi-label">${esc(label)}</span></div><div class="kpi-number">${esc(value)}</div><div class="kpi-desc">${esc(caption)}</div></div>`; }
function monthStats() {
  if (!ready('transactions')) return null;
  const group = posted().filter(t => ownMonth(t.date) === month());
  const income = sum(group.filter(t => t.type === 'income'));
  const expense = sum(group.filter(t => t.type === 'expense'));
  return { income, expense, net: income - expense, rate: income > 0 ? (income - expense) / income * 100 : null };
}
function reports() {
  const issue = problem('expenseAnalytics');
  if (issue) return issue;
  const byCategory = Array.isArray(expenseAnalytics.byCategory) ? expenseAnalytics.byCategory : [];
  const months = Array.isArray(expenseAnalytics.trend) ? expenseAnalytics.trend : [];
  const totalExpense = Number(expenseAnalytics.totalExpense || 0);
  const palette = ['#31b9bd','#f08b65','#705cf6','#f2bd52','#4f8fdd','#cf67a4','#5caa72','#8c78c6'];
  let offset = 0;
  const segments = byCategory.map((item, index) => {
    const ratio = totalExpense > 0 ? Number(item.amount || 0) / totalExpense * 100 : 0;
    const segment = `<circle class="expense-donut-segment" cx="50" cy="50" r="38" pathLength="100" stroke="${palette[index % palette.length]}" stroke-dasharray="${ratio} ${100-ratio}" stroke-dashoffset="${-offset}"><title>${esc(item.category)}: ${esc(vnd(item.amount))}</title></circle>`;
    offset += ratio;
    return segment;
  }).join('');
  const categoryPanel = byCategory.length
    ? `<div class="expense-donut-layout"><svg class="expense-donut" viewBox="0 0 100 100" role="img" aria-label="Cơ cấu chi tiêu theo danh mục"><circle class="expense-donut-bg" cx="50" cy="50" r="38"/>${segments}</svg><div class="expense-legend">${byCategory.map((item,index)=>`<div class="expense-legend-row"><i class="expense-legend-dot" style="background:${palette[index%palette.length]}"></i><span>${esc(item.category)}</span><strong>${vnd(item.amount)}</strong></div>`).join('')}</div></div>`
    : empty('expenseAnalytics','chi tiêu trong tháng');
  const ceiling = Math.max(1, ...months.flatMap(item => [Number(item.income || 0), Number(item.expense || 0)]));
  const barPanel = months.length
    ? `<div class="expense-bars">${months.map(item=>`<div class="expense-month"><i class="expense-column" style="height:${Math.max(1,Number(item.income||0)/ceiling*100)}%" title="${esc(item.month)} · Thu nhập: ${esc(vnd(item.income))}"></i><i class="expense-column is-expense" style="height:${Math.max(1,Number(item.expense||0)/ceiling*100)}%" title="${esc(item.month)} · Chi tiêu: ${esc(vnd(item.expense))}"></i><small>${esc(item.month.slice(5))}</small></div>`).join('')}</div><div class="expense-chart-legend"><span><i></i>Thu nhập</span><span><i class="is-expense"></i>Chi tiêu</span></div>`
    : empty('expenseAnalytics','dữ liệu sáu tháng');
  return `<div class="report-actions"><span class="wf-muted">Dựa trên các giao dịch đã ghi sổ</span><button type="button" class="btn btn--outline btn-sm" data-action="refresh-data">↻ Làm mới dữ liệu</button></div><div class="kpi-grid report-kpi-grid">${card('Tổng chi tiêu tháng',vnd(expenseAnalytics.totalExpense),`Tháng ${expenseAnalytics.month}`)}${card('Số danh mục phát sinh',String(byCategory.length),'Các danh mục có chi tiêu trong tháng')}${card('Đơn vị báo cáo',String(expenseAnalytics.currency || 'VND'),'Đơn vị dùng trong các biểu đồ')}</div><div class="expense-dashboard-grid"><section class="panel"><div class="panel-title"><div><h2>Chi tiêu theo danh mục</h2><p>Biểu đồ tròn · tháng ${esc(expenseAnalytics.month)}</p></div></div>${categoryPanel}</section><section class="panel"><div class="panel-title"><div><h2>Thu nhập và chi tiêu</h2><p>Biểu đồ cột · 6 tháng gần nhất</p></div></div>${barPanel}</section></div>`;
}
function overview() {
  const issue = problem('analytics');
  if (issue) return `<section class="panel">${issue}<div class="wf-grid-actions"><button type="button" class="btn btn--outline btn-sm" data-page="overview">Thử tải lại</button></div></section>`;
  const assets = analytics.assets || {};
  const liabilities = analytics.liabilities || {};
  const cashFlow = analytics.cashFlow || {};
  const goalSummary = analytics.goals || {};
  const breakdown = assets.breakdown || {};
  const goalItems = Array.isArray(goalSummary.items) ? goalSummary.items : [];
  const goalHtml = goalItems.length ? goalItems.map(goal => {
    const progress = Math.max(0, Math.min(100, Number(goal.progressPercent || 0)));
    return `<div class="budget-row"><div class="budget-info"><strong>${esc(goal.name)}</strong><span>${vnd(goal.currentAmount)} / ${vnd(goal.targetAmount)}</span></div><div class="wf-progress"><i style="width:${progress}%"></i></div><div class="budget-foot">${progress.toLocaleString('vi-VN')}% · hạn ${display(goal.deadline, 'date')}</div></div>`;
  }).join('') : empty('goals', 'mục tiêu đang thực hiện');
  const allocationRows = [
    ['Tiền mặt', breakdown.cash],
    ['Ngân hàng', breakdown.bank],
    ['Ví điện tử', breakdown.eWallet],
    ['Tiết kiệm', breakdown.savings],
    ['Vàng', breakdown.gold],
    ['Khoản cho vay', breakdown.receivableLoans]
  ].filter(([, amount]) => Number(amount || 0) !== 0);
  const allocationHtml = allocationRows.length
    ? `<div class="goal-history">${allocationRows.map(([label, amount]) => `<div class="goal-history-row"><span><strong>${esc(label)}</strong></span><b>${vnd(amount)}</b></div>`).join('')}</div>`
    : message('Chưa có tài sản', 'Các nguồn tài sản hợp lệ của tài khoản hiện có giá trị bằng 0.');
  return `<div class="kpi-grid">
    ${card('Tài sản ròng', vnd(analytics.netWorth), 'Tổng tài sản − Tổng nợ phải trả')}
    ${card('Tổng tài sản', vnd(assets.totalAssets), 'Ví + vàng + khoản cho vay, không tính tiền mục tiêu lần hai')}
    ${card('Tổng nợ phải trả', vnd(liabilities.totalLiabilities), 'Ví tín dụng + dư nợ gốc đi vay')}
    ${card('Đang tích lũy', vnd(goalSummary.currentAmount), `${Number(goalSummary.activeCount || 0)} mục tiêu đang thực hiện`)}</div>
    <div class="kpi-grid">${card('Thu nhập tháng', vnd(cashFlow.income), 'Chỉ giao dịch thu nhập đã ghi sổ')}${card('Chi phí tháng', vnd(cashFlow.expense), 'Chỉ giao dịch chi phí đã ghi sổ')}${card('Dòng tiền thuần', vnd(cashFlow.net), 'Thu nhập − Chi phí')}${card('Tỷ lệ tiết kiệm', cashFlow.savingRate === null ? 'N/A' : `${esc(cashFlow.savingRate)}%`, cashFlow.savingRate === null ? 'Chưa có thu nhập để tính' : `Tháng ${esc(cashFlow.month)}`)}</div>
    <div class="panel-grid"><section class="panel"><div class="panel-title"><div><h2>Cơ cấu tài sản</h2><p>Phân bổ theo giá trị hiện tại</p></div></div>${allocationHtml}</section><section class="panel"><div class="panel-title"><div><h2>Mục tiêu đang thực hiện</h2><p>Tiền phân bổ vẫn nằm trong ví, không cộng lại vào tài sản</p></div></div>${goalHtml}</section></div>
    <section class="panel"><div class="panel-title"><h2>Giao dịch gần đây</h2></div>${table('transactions', [['Giao dịch','name'],['Loại','type'],['Ngày','date','date'],['Giá trị','amount','money']],transactions.slice(0,5))}</section>`;
}
function budgetCards(limit = Infinity, showActions = false) {
  const issue = problem('budgets'); if (issue) return issue;
  if (!budgets.length) return empty('budgets','ngân sách');
  return budgets.slice(0,limit).map(b => {
    const rate = b.limit > 0 ? Math.max(0, b.spent / b.limit * 100) : 0;
    const statusText = rate >= 100 ? 'Cảnh báo Đỏ · Đã vượt hạn mức (≥ 100%)' : rate >= 80 ? 'Cảnh báo Vàng · Sắp chạm hạn mức (≥ 80%)' : 'An toàn · Trong hạn mức (< 80%)';
    const remainingText = b.spent > b.limit ? `Vượt ${vnd(b.spent - b.limit)}` : `Còn lại ${vnd(Math.max(0, b.limit - b.spent))}`;
    const actionsHtml = showActions && b.id ? `<div class="wf-table-actions" style="margin-top:8px;display:flex;gap:12px">
      <button type="button" class="text-action" data-action="live:detail:budgets:${encodeURIComponent(String(b.id))}">Chi tiết</button>
      <button type="button" class="text-action" data-action="live:edit:budgets:${encodeURIComponent(String(b.id))}">Sửa hạn mức</button>
      <button type="button" class="text-action" data-action="live:delete:budgets:${encodeURIComponent(String(b.id))}">Lưu trữ</button>
    </div>` : '';
    return `<div class="budget-row"><div class="budget-info"><strong>${esc(b.name)}${b.month ? ` <small class="wf-muted">(${esc(b.month)})</small>` : ''}</strong><span>${vnd(b.spent)} / ${vnd(b.limit)}</span></div>
    <div class="wf-progress"><i style="width:${Math.min(rate,100)}%;background:${rate >=100?'#bb4545':rate>=80?'#e1a145':'#16869e'}"></i></div><div class="budget-foot">${rate.toFixed(1)}% · ${statusText} · ${remainingText}</div>${actionsHtml}</div>`;
  }).join('');
}

function budgetsPage() {
  const issue = problem('budgets');
  const totalLimit = ready('budgets') ? budgets.reduce((s, b) => s + Number(b.limit || 0), 0) : 0;
  const totalSpent = ready('budgets') ? budgets.reduce((s, b) => s + Number(b.spent || 0), 0) : 0;
  const overallRemaining = Math.max(0, totalLimit - totalSpent);
  const overallRate = totalLimit > 0 ? (totalSpent / totalLimit * 100) : 0;
  const redCount = ready('budgets') ? budgets.filter(b => b.limit > 0 && (b.spent / b.limit * 100) >= 100).length : 0;
  const yellowCount = ready('budgets') ? budgets.filter(b => b.limit > 0 && (b.spent / b.limit * 100) >= 80 && (b.spent / b.limit * 100) < 100).length : 0;
  const kpis = ready('budgets') ? `<div class="kpi-grid">
    ${card('Tổng ngân sách tháng', vnd(totalLimit), `${budgets.length} danh mục chi tiêu đang theo dõi`)}
    ${card('Chi tiêu ròng trong kỳ', vnd(totalSpent), 'Không tính chuyển ví nội bộ & trả nợ gốc')}
    ${card('Hạn mức còn lại', vnd(overallRemaining), `Tỷ lệ sử dụng chung: ${overallRate.toFixed(1)}%`)}
    ${card('Ngưỡng cảnh báo', `${redCount} Đỏ · ${yellowCount} Vàng`, 'Cảnh báo tự động ở mốc 80% và 100%')}
  </div>` : '';
  return `<div class="wf-grid-actions">${addButton('budgets', 'Thiết lập ngân sách')}</div>${kpis}<section class="panel"><div class="panel-title"><h2>Hạn mức ngân sách theo danh mục (${esc(month())})</h2></div>${issue || budgetCards(Infinity, true)}</section>`;
}

function notificationsPage(state = {}) {
  const issue = problem('notifications');
  if (issue) return `<section class="panel">${issue}</section>`;
  const unreadFilter = String(state.notifFilter || 'all') === 'unread';
  const unreadCount = notifications.filter(n => !n.isRead && !n.read_at && !n.readAt).length;
  const budgetAlertCount = notifications.filter(n => String(n.type || n.notification_type || '').toUpperCase() === 'BUDGET').length;
  const list = unreadFilter ? notifications.filter(n => !n.isRead && !n.read_at && !n.readAt) : notifications;
  const typeLabel = t => ({ BUDGET: 'Ngân sách', RECURRING: 'Định kỳ', LOAN: 'Khoản vay', BROADCAST: 'Hệ thống', SYSTEM: 'Hệ thống' }[String(t || '').toUpperCase()] || String(t || 'Thông báo'));
  return `<div class="kpi-grid">
    ${card('Tổng thông báo', String(notifications.length), 'Đồng bộ trực tiếp từ máy chủ')}
    ${card('Chưa đọc', String(unreadCount), unreadCount > 0 ? 'Cần kiểm tra các cảnh báo mới' : 'Bạn đã đọc hết thông báo')}
    ${card('Cảnh báo ngân sách', String(budgetAlertCount), 'Ngưỡng 80% (Vàng) và 100% (Đỏ)')}
  </div>
  <section class="panel">
    <div class="filter-row" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;margin-bottom:14px">
      <div class="tabs">
        <button type="button" data-notif-filter="all" class="${!unreadFilter ? 'active' : ''}">Tất cả (${notifications.length})</button>
        <button type="button" data-notif-filter="unread" class="${unreadFilter ? 'active' : ''}">Chưa đọc (${unreadCount})</button>
      </div>
      <button type="button" class="btn btn--outline btn-sm" data-action="live:read-all:notifications" ${unreadCount === 0 ? 'disabled' : ''}>Đánh dấu tất cả đã đọc</button>
    </div>
    ${!list.length ? empty('notifications', unreadFilter ? 'thông báo chưa đọc' : 'thông báo') : `<div class="data-table-wrap"><table class="data-table"><thead><tr><th>Thông báo</th><th>Phân loại</th><th>Ngày tạo</th><th>Trạng thái</th><th></th></tr></thead><tbody>${list.map(row => {
      const unread = !row.isRead && !row.read_at && !row.readAt;
      const title = val(row, 'title', 'message') || 'Thông báo';
      const body = val(row, 'body', 'details') || '';
      const idEnc = encodeURIComponent(String(row.id ?? ''));
      return `<tr style="${unread ? 'font-weight:600' : ''}">
        <td><div><strong>${esc(title)}</strong>${body ? `<p class="wf-muted" style="margin:4px 0 0;font-weight:400;font-size:13px">${esc(body)}</p>` : ''}</div></td>
        <td>${esc(typeLabel(val(row, 'type', 'notification_type')))}</td>
        <td>${display(val(row, 'createdAt', 'created_at'), 'date')}</td>
        <td>${unread ? '<span style="color:#b45309">● Chưa đọc</span>' : '<span class="wf-muted">Đã đọc</span>'}</td>
        <td class="wf-table-actions">
          <button type="button" class="text-action" data-action="live:detail:notifications:${idEnc}">Chi tiết</button>
          ${unread && row.id ? `<button type="button" class="text-action" data-action="live:read:notifications:${idEnc}">Đã đọc</button>` : ''}
        </td>
      </tr>`;
    }).join('')}</tbody></table></div>`}
  </section>`;
}

export function liveTransactionTable(items = transactions) {
  const issue = problem('transactions');
  if (issue) return issue;
  if (!items.length) return empty('transactions', 'giao dịch');
  const amount = row => {
    const currency = ['VND','USD','EUR'].includes(String(row.currency).toUpperCase()) ? String(row.currency).toUpperCase() : 'VND';
    const formatted = new Intl.NumberFormat('vi-VN', {
      style: 'currency', currency, maximumFractionDigits: currency === 'VND' ? 0 : 2
    }).format(Math.abs(Number(row.amount || 0)));
    const sign = row.type === 'income' ? '+' : row.type === 'expense' ? '−' : row.type === 'savings_goal' ? (row.goalAction === 'WITHDRAWAL' ? '← ' : '→ ') : '';
    const tone = row.type === 'income' ? ' positive' : row.type === 'expense' ? ' negative' : '';
    return `<span class="amount${tone}">${sign}${esc(formatted)}</span>`;
  };
  return `<div class="data-table-wrap"><table class="data-table transaction-table"><thead><tr>
    <th>Giao dịch</th><th>Loại</th><th>Danh mục</th><th>Ngày</th><th>Ví sử dụng</th><th>Số tiền</th><th></th>
  </tr></thead><tbody>${items.map(row => `<tr>
    <td><div class="table-name"><span class="table-icon" aria-hidden="true">${esc(row.icon)}</span><span>${esc(row.name)}</span></div></td>
    <td>${esc(TRANSACTION_TYPE_LABELS[row.type] || row.type || 'Giao dịch')}</td>
    <td>${esc(row.type === 'transfer' ? 'Chuyển nội bộ' : row.type === 'savings_goal' ? (row.goalName || 'Mục tiêu tiết kiệm') : row.category)}</td>
    <td>${display(row.date, 'date')}</td>
    <td class="transaction-wallet">${esc(row.wallet)}</td>
    <td>${amount(row)}</td>
    <td class="wf-table-actions"><button type="button" class="text-action" data-action="live:detail:transactions:${encodeURIComponent(String(row.id))}">Chi tiết</button></td>
  </tr>`).join('')}</tbody></table></div>`;
}

function transactionList(state) {
  const savedFilters = Array.isArray(state.txFilter) ? state.txFilter : [state.txFilter || 'ALL'];
  const filters = savedFilters.map(value => String(value).toUpperCase());
  const selected = filters.filter(value => value !== 'ALL');
  const q = String(state.search || '').trim().toLocaleLowerCase('vi');
  const list = transactions.filter(t => (!selected.length || selected.includes(String(t.type).toUpperCase()))
    && (!q || [t.name,t.category,t.wallet,t.id].join(' ').toLocaleLowerCase('vi').includes(q)));
  const filterLabel = !selected.length ? 'Tất cả'
    : selected.length === 1 ? (TRANSACTION_TYPE_LABELS[selected[0].toLowerCase()] || selected[0])
      : `${selected.length} loại`;
  return `<div class="filter-row"><div class="filter-controls">
    <button type="button" class="btn btn--outline btn-sm wallet-filter-button transaction-filter-button" data-action="transaction-filter">
      <svg class="nav-icon" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h16M7 12h10M10 18h4"/></svg>
      <span>Lọc theo loại giao dịch · ${esc(filterLabel)}</span>
    </button>
    <input id="transaction-search" class="input-sm" type="search" placeholder="Tìm tên, danh mục hoặc ví" aria-label="Tìm kiếm giao dịch" value="${esc(state.search||'')}">
  </div></div>
  <div id="transaction-table">${liveTransactionTable(list)}</div>`;
}

function transactionDetail(item) {
  const currency = ['VND','USD','EUR'].includes(String(item.currency).toUpperCase()) ? String(item.currency).toUpperCase() : 'VND';
  const formattedAmount = new Intl.NumberFormat('vi-VN', {
    style: 'currency', currency, maximumFractionDigits: currency === 'VND' ? 0 : 2
  }).format(Math.abs(Number(item.amount || 0)));
  const sign = item.type === 'income' ? '+' : item.type === 'expense' ? '−' : item.type === 'savings_goal' ? (item.goalAction === 'WITHDRAWAL' ? '← ' : '→ ') : '';
  const category = item.type === 'transfer' ? 'Chuyển nội bộ' : item.type === 'savings_goal' ? (item.goalName || 'Mục tiêu tiết kiệm') : item.category;
  const specialStatus = item.status === 'DRAFT' ? '<span class="badge neutral">Bản nháp</span>'
    : item.status === 'REVERSED' ? '<span class="badge warn">Đã đảo</span>' : '';
  return `<div class="transaction-detail-hero">
    <span class="transaction-detail-icon" aria-hidden="true">${esc(item.icon)}</span>
    <div><small>${esc(TRANSACTION_TYPE_LABELS[item.type] || item.type || 'Giao dịch')}</small><strong>${esc(item.name)}</strong></div>
    <b class="transaction-detail-amount">${sign}${esc(formattedAmount)}</b>
  </div>
  <div class="transaction-detail-grid">
    <div><small>Ví sử dụng</small><strong>${esc(item.wallet)}</strong></div>
    <div><small>Danh mục</small><strong>${esc(category)}</strong></div>
    <div><small>Ngày giao dịch</small><strong>${display(item.date, 'date')}</strong></div>
    <div><small>Tiền tệ</small><strong>${esc(currency)}</strong></div>
    ${item.details ? `<div class="transaction-detail-note"><small>Ghi chú</small><strong>${esc(item.details)}</strong></div>` : ''}
  </div>
  ${specialStatus ? `<div class="transaction-detail-status">${specialStatus}</div>` : ''}
  <div class="modal-footer"><button type="button" class="btn btn--outline" data-close-modal>Đóng</button></div>`;
}
function genericPage(key, cols) {
  const canCreate = ['transactions', 'categories', 'budgets', 'recurring'].includes(key);
  return `${canCreate ? `<div class="wf-grid-actions">${addButton(key)}</div>` : ''}<section class="panel">${table(key,cols)}</section>`;
}
const tableColumns = {
  wallets:[['Tên ví','name'],['Loại','type'],['Tiền tệ','currency'],['Số dư','balance','money']],
  categories:[['Danh mục','name'],['Loại','type'],['Ghi chú','description']],
  recurring:[['Tên','name|description'],['Số tiền','amount','money'],['Chu kỳ','frequency|interval'],['Ngày tiếp theo','nextRun|nextRunAt','date']],
  goals:[['Mục tiêu','name'],['Đã tích lũy','current','money'],['Đích đến','target','money'],['Hạn hoàn thành','deadline','date']],
  loans:[['Khoản vay','name|title'],['Loại','type|direction'],['Dư gốc','outstanding|remainingPrincipal|balance','money'],['Hạn trả','dueDate','date']],
  gold:[['Tài sản','name|type'],['Số lượng','quantity'],['Định giá','value|currentValue|valuation','money']],
  notifications:[['Nội dung','message|title'],['Loại','type'],['Ngày','createdAt|created_at','date'],['Trạng thái','status']],
  adminUsers:[['Người dùng','fullName|name'],['Email','email'],['Quyền','role'],['Trạng thái','status']]
};
function settingsPage(user) {
  const row = (label, details, control = '') => `<div class="settings-row"><div><strong>${esc(label)}</strong><p>${esc(details)}</p></div>${control}</div>`;
  return `<div class="info-grid">
  <section class="panel"><h2>Hồ sơ và tài khoản</h2>
    ${row('Họ và tên', user.fullName)}
    ${row('Email', user.email)}
    ${row('Tiền cơ sở', user.baseCurrency || 'VND')}
    ${row('Ngôn ngữ', 'Tiếng Việt (vi-VN)')}
  </section>
  <section class="panel"><h2>Tài khoản và bảo mật</h2>
    ${row('Đổi mật khẩu', 'Yêu cầu mật khẩu hiện tại. Thu hồi các phiên cũ.', '<button class="btn btn--outline btn-sm" data-action="password">Đổi mật khẩu</button>')}
    ${row('Phiên đăng nhập', 'Tài khoản đang đăng nhập trên trình duyệt này. Dùng nút đăng xuất để kết thúc phiên.')}
    ${row('Đăng xuất', 'Kết thúc phiên đăng nhập hiện tại.', '<button class="btn btn--outline btn-sm" data-action="logout">Đăng xuất</button>')}
  </section>
  <section class="panel"><h2>Dữ liệu và quyền riêng tư</h2>
    ${row('Xuất dữ liệu giao dịch', 'Tải các giao dịch từ máy chủ ra tệp CSV.', '<button class="btn btn--outline btn-sm" data-action="export">Xuất CSV</button>')}
  </section>
  <section class="panel"><h2>Thông báo và trải nghiệm</h2>
    ${row('Cảnh báo ngân sách', 'Được tính theo các ngưỡng 80% và 100% từ dữ liệu chi tiêu đã ghi sổ.')}
    ${row('Quản lý thông báo', 'Xem thông báo được đồng bộ từ máy chủ.', '<button class="btn btn--outline btn-sm" data-page="notifications">Xem thông báo</button>')}
    ${row('Tùy chỉnh giao diện', 'Chưa có API lưu tùy chọn giao diện theo tài khoản. Không hiển thị tùy chỉnh giả.')}
  </section>
  <section class="danger-zone">
    <div class="danger-zone__heading"><span class="danger-zone__icon" aria-hidden="true">!</span><div><h2>Vùng nguy hiểm</h2><p>Các thao tác này ảnh hưởng trực tiếp đến dữ liệu và trạng thái tài khoản.</p></div></div>
    <div class="danger-zone__actions">
      <div><h3>Tải toàn bộ dữ liệu</h3><p>Tạo bản xuất JSON gồm hồ sơ và các dữ liệu tài chính, lịch sử thuộc tài khoản. Mật khẩu, token và thông tin xác thực không được đưa vào tệp.</p></div>
      <button class="btn btn--outline btn-sm" data-action="export-all-data">Tải JSON</button>
    </div>
    <div class="danger-zone__actions danger-zone__actions--close">
      <div><h3>Đóng tài khoản</h3><p>Tài khoản sẽ bị khóa và đăng xuất khỏi mọi thiết bị. Dữ liệu tài chính được giữ lại để phục vụ kiểm toán; thao tác này không thể tự hoàn tác.</p></div>
      <button class="btn btn--danger btn-sm" data-action="close-account">Đóng tài khoản</button>
    </div>
  </section></div>`;
}
export function liveRender(page, { state, heading, currentUser }) {
  if (page.startsWith('admin-') && currentUser.role !== 'ADMIN') return message('Không có quyền','Chỉ quản trị viên mới truy cập được mục này.');
  const section = page === 'overview' ? overview(currentUser)
    : page === 'transactions' ? `<div class="wf-grid-actions">${addButton('transactions','Ghi giao dịch')}</div><section class="panel">${transactionList(state)}</section>`
    : page === 'budgets' ? budgetsPage()
    : page === 'notifications' ? notificationsPage(state)
    : page === 'reports' || page === 'report' ? reports()
    : page === 'settings' ? settingsPage(currentUser)
    : page === 'admin-settings' ? message('Cấu hình quản trị', 'Các API quản trị chưa được xác minh ở bản nguồn này; không hiển thị dữ liệu giả.')
    : page === 'admin-overview' ? `<section class="panel">${table('adminUsers',tableColumns.adminUsers)}</section>`
    : page === 'admin-users' ? genericPage('adminUsers',tableColumns.adminUsers)
    : page === 'admin-categories' ? genericPage('categories',tableColumns.categories)
    : page === 'admin-audit' || page === 'reconcile' ? message('Chưa có dữ liệu đối soát thực','Chỉ hiển thị kết quả sau khi backend cung cấp endpoint và đã kiểm chứng số liệu.')
    : page in tableColumns ? genericPage(page,tableColumns[page])
    : message('Chức năng đang được tích hợp','Chưa có API thực cho màn hình này. Không hiển thị dữ liệu mẫu.');
  return baseStyle + heading(page) + section;
}

// Live forms mirror the actual Controller/Service DTOs, never demo-data fields.
const field = (name,label,type='text',extra='') => `<div class="field"><label for="live-${esc(name)}">${esc(label)}</label><input id="live-${esc(name)}" name="${esc(name)}" type="${type}" ${extra} required></div>`;
const moneyField = (name,label,extra='') => field(name,label,'text',`inputmode="numeric" data-money-input maxlength="24" ${extra}`);
const select = (name,label,options,optional=false) => `<div class="field"><label for="live-${esc(name)}">${esc(label)}</label><select id="live-${esc(name)}" name="${esc(name)}" ${optional?'':'required'}>${optional?'<option value="">Không chọn</option>':''}${options.map(([v,l])=>`<option value="${esc(v)}">${esc(l)}</option>`).join('')}</select></div>`;
const walletOptions = (includeCredit = true) => wallets.filter(w => String(w.status||'ACTIVE').toUpperCase()==='ACTIVE'
  && (includeCredit || String(w.type ?? w.wallet_type).toUpperCase() !== 'CREDIT')).map(w=>[w.id,`${w.name} (${w.currency})`]);
const categoryOptions = (type='EXPENSE') => categories.filter(c => !type || c.type === type).map(c=>[c.id,c.name]);
const goalWalletOptions = () => walletOptions(false).filter(([id]) => String(wallets.find(w => String(w.id) === String(id))?.currency).toUpperCase() === 'VND');
const goalOptions = (action='DEPOSIT') => goals.filter(g => action === 'WITHDRAWAL' ? ['ACTIVE','COMPLETED'].includes(String(g.status).toUpperCase()) && Number(g.current) > 0 : String(g.status).toUpperCase() === 'ACTIVE').map(g=>[g.id,`${g.name} · ${vnd(g.current)} / ${vnd(g.target)}`]);
const needReferenceData = name => name === 'transactions' ? (!ready('wallets') || !walletOptions().length)
 : name === 'recurring' ? (!ready('wallets') || !walletOptions().length || !ready('categories'))
 : name === 'budgets' ? (!ready('categories') || !categoryOptions().length) : false;
function createForm(name) {
  if (name==='categories') return field('name','Tên danh mục','text','maxlength="100"')
    +select('type','Loại danh mục',[['EXPENSE','Chi tiêu'],['INCOME','Thu nhập']])
    +`<div class="field form-grid-span"><label for="live-note">Ghi chú (không bắt buộc)</label><textarea id="live-note" name="note" maxlength="500" rows="3" placeholder="Thêm mô tả hoặc lưu ý cho danh mục"></textarea></div>`;
  if (name==='transactions') return field('description','Mô tả giao dịch','text','maxlength="255"')
    +select('type','Loại giao dịch',[['EXPENSE','Chi tiêu'],['INCOME','Thu nhập'],['TRANSFER','Chuyển tiền'],['SAVINGS_GOAL','Chuyển vào mục tiêu tiết kiệm']])
    +select('goalAction','Thao tác với mục tiêu',[['DEPOSIT','Chuyển vào mục tiêu'],['WITHDRAWAL','Lấy khỏi mục tiêu']])
    +moneyField('amount','Số tiền')
    +select('walletId','Ví sử dụng',walletOptions())
    +select('goalId','Mục tiêu tiết kiệm',goalOptions('DEPOSIT'))
    +select('fromWalletId','Ví gửi',walletOptions())
    +select('toWalletId','Ví nhận',walletOptions())
    +select('categoryId','Danh mục',categories.map(c=>[c.id,`${c.name} · ${c.type}`]))
    +field('occurredOn','Ngày giao dịch','date');
  if (name==='budgets') return select('categoryId','Danh mục chi tiêu',categoryOptions())
    +field('month','Kỳ ngân sách','month')+moneyField('limitAmount','Hạn mức');
  if (name==='recurring') return field('name','Tên khoản định kỳ','text','maxlength="120"')
    +moneyField('amount','Số tiền')
    +select('walletId','Ví thanh toán',walletOptions())
    +select('categoryId','Danh mục chi tiêu (nếu có)',categoryOptions(),true)
    +field('dayOfMonth','Ngày lặp lại hằng tháng (1–31)','number','min="1" max="31" step="1"');
  return '';
}
export function liveAction(action, { dialog, toast, reload }) {
  if (!action.startsWith('live:')) return false;
  const [,verb,name,encodedId] = action.split(':');
  const collection = ITEMS[name];
  if (!collection) { toast('Phân hệ không hợp lệ.'); return true; }
  if (verb === 'detail') {
    const item = collection.find(x => String(x.id) === decodeURIComponent(encodedId || ''));
    if (!item) { toast('Không tìm thấy dữ liệu trong tài khoản này.'); return true; }
    if (name === 'transactions') {
      document.querySelector('#modal-overline').textContent = '';
      document.querySelector('#modal-title').textContent = 'Chi tiết giao dịch';
      document.querySelector('#modal-content').innerHTML = transactionDetail(item);
      dialog.showModal(); return true;
    }
    document.querySelector('#modal-overline').textContent = TITLES[name] || name;
    document.querySelector('#modal-title').textContent = String(val(item,'name','title') || TITLES[name] || 'Chi tiết');
    const dl = (DETAIL_FIELDS[name] || []).map(([label, keys, format]) => {
      const value = val(item, ...keys.split('|'));
      if (value === undefined || value === null || value === '') return '';
      let text = value;
      if (format === 'money') text = display(value, 'money', item.currency || 'VND');
      else if (format === 'date') text = display(value, 'date');
      else if (format === 'categoryType') text = ({ INCOME: 'Thu nhập', EXPENSE: 'Chi tiêu' })[String(value).toUpperCase()] || value;
      else if (format === 'loanDirection') text = ({ BORROWED: 'Đi vay', LENT: 'Cho vay' })[String(value).toUpperCase()] || value;
      else if (format === 'goldType') text = ({ RING_9999: 'Vàng nhẫn 9999', SJC_BAR: 'Vàng miếng SJC' })[String(value)] || value;
      else if (format === 'priority') text = ({ LOW: 'Thấp', MEDIUM: 'Trung bình', HIGH: 'Cao' })[String(value).toUpperCase()] || value;
      else if (format === 'role') text = ({ USER: 'Người dùng', ADMIN: 'Quản trị viên' })[String(value).toUpperCase()] || value;
      else if (format === 'status') text = ({ ACTIVE: 'Đang hoạt động', PAUSED: 'Đang tạm dừng', COMPLETED: 'Hoàn thành', CANCELLED: 'Đã hủy', ARCHIVED: 'Đã lưu trữ', HELD: 'Đang nắm giữ', PAID_OFF: 'Đã tất toán', DEFAULTED: 'Quá hạn' })[String(value).toUpperCase()] || value;
      return `<div><dt>${esc(label)}</dt><dd>${esc(text)}</dd></div>`;
    }).filter(Boolean).join('');
    const categoryEdit = name === 'categories' ? `<button type="button" class="btn btn--outline btn-sm" data-action="live:edit:categories:${encodeURIComponent(String(item.id))}">Chỉnh sửa</button>` : '';
    document.querySelector('#modal-content').innerHTML = `<dl class="detail-grid">${dl || '<div><dd>Chưa có thông tin chi tiết.</dd></div>'}</dl><div class="modal-footer">${categoryEdit}<button type="button" class="btn btn--outline" data-close-modal>Đóng</button></div>`;
    dialog.showModal(); return true;
  }
  if (name === 'categories' && verb === 'edit') {
    const id = decodeURIComponent(encodedId || '');
    const item = collection.find(x => String(x.id) === id);
    if (!item || !/^\d+$/.test(id)) { toast('Không tìm thấy danh mục trong tài khoản.'); return true; }
    if (dialog.open) dialog.close();
    document.querySelector('#modal-overline').textContent = 'DANH MỤC CÁ NHÂN';
    document.querySelector('#modal-title').textContent = 'Chỉnh sửa danh mục';
    document.querySelector('#modal-content').innerHTML = `<form id="live-category-edit-form"><div class="form-grid">
      ${field('name','Tên danh mục','text',`maxlength="100" value="${esc(item.name)}"`)}
      <div class="field form-grid-span"><label for="live-note">Ghi chú (không bắt buộc)</label><textarea id="live-note" name="note" maxlength="500" rows="3">${esc(item.description || '')}</textarea></div>
      <p class="form-hint">Loại danh mục: ${esc(item.type === 'INCOME' ? 'Thu nhập' : 'Chi tiêu')}</p>
    </div><p id="live-form-feedback" role="status" aria-live="polite"></p><div class="modal-footer"><button type="button" class="btn btn--outline" data-close-modal>Hủy</button><button type="submit" class="btn btn--primary">Lưu thay đổi</button></div></form>`;
    const form = document.querySelector('#live-category-edit-form');
    form.addEventListener('submit', async e => {
      e.preventDefault(); if (!form.reportValidity()) return;
      const submit = form.querySelector('[type=submit]');
      const feedback = form.querySelector('#live-form-feedback');
      submit.disabled = true; feedback.textContent = 'Đang lưu thay đổi…';
      try {
        await mutateLiveData('categories', 'PUT', { name: form.elements.namedItem('name').value, note: form.elements.namedItem('note').value }, id);
        dialog.close();
        await loadLiveData();
        reload();
        toast('Đã cập nhật danh mục.');
      } catch (error) { feedback.textContent = `Không thể cập nhật: ${error.message}`; }
      finally { submit.disabled = false; }
    });
    dialog.showModal();
    form.elements.namedItem('note').focus();
    return true;
  }
  if (name === 'notifications' && verb === 'read') {
    const id = decodeURIComponent(encodedId || '');
    if (!/^\d+$/.test(id)) { toast('Mã thông báo không hợp lệ.'); return true; }
    void httpClient(`api/notifications/${encodeURIComponent(id)}/read`, { method: 'POST', data: {} })
      .then(async () => { await loadLiveData(); reload(); toast('Đã đánh dấu thông báo là đã đọc.'); })
      .catch(error => toast(`Không thể cập nhật thông báo: ${error.message}`));
    return true;
  }
  if (name === 'notifications' && verb === 'read-all') {
    void httpClient('api/notifications/read-all', { method: 'POST', data: {} })
      .then(async () => { await loadLiveData(); reload(); toast('Đã đánh dấu tất cả thông báo là đã đọc.'); })
      .catch(error => toast(`Không thể cập nhật thông báo: ${error.message}`));
    return true;
  }
  if (name === 'budgets' && verb === 'edit') {
    const id = decodeURIComponent(encodedId || '');
    const item = collection.find(x => String(x.id) === id);
    if (!item || !/^\d+$/.test(id)) { toast('Không tìm thấy ngân sách trong tài khoản.'); return true; }
    document.querySelector('#modal-overline').textContent = 'CẬP NHẬT HẠN MỨC';
    document.querySelector('#modal-title').textContent = `Sửa ngân sách: ${item.name}`;
    document.querySelector('#modal-content').innerHTML = `<p class="form-hint">Điều chỉnh hạn mức ngân sách cho danh mục <strong>${esc(item.name)}</strong> (${esc(item.month || month())}). Đã chi ròng: <strong>${vnd(item.spent)}</strong>.</p>
    <form id="live-budget-edit-form"><div class="form-grid">${moneyField('limitAmount', 'Hạn mức mới (VND)', `value="${esc(formatMoneyInput(item.limit))}"`)}</div><p id="live-form-feedback" role="status" aria-live="polite"></p><div class="modal-footer"><button type="button" class="btn btn--outline" data-close-modal>Hủy</button><button type="submit" class="btn btn--primary">Cập nhật hạn mức</button></div></form>`;
    const form = document.querySelector('#live-budget-edit-form');
    form.addEventListener('submit', async e => {
      e.preventDefault(); if (!form.reportValidity()) return;
      const submit = form.querySelector('[type=submit]');
      const feedback = form.querySelector('#live-form-feedback');
      const limitAmount = moneyInputValue(form.elements.namedItem('limitAmount').value);
      submit.disabled = true; feedback.textContent = 'Đang cập nhật hạn mức…';
      try {
        await mutateLiveData('budgets', 'PUT', { limitAmount }, id);
        dialog.close();
        await loadLiveData();
        reload();
        toast('Đã cập nhật hạn mức ngân sách.');
      } catch (error) { feedback.textContent = `Không thể cập nhật: ${error.message}`; }
      finally { submit.disabled = false; }
    });
    dialog.showModal();
    return true;
  }
  if (name === 'budgets' && verb === 'delete') {
    const id = decodeURIComponent(encodedId || '');
    const item = collection.find(x => String(x.id) === id);
    if (!item || !/^\d+$/.test(id)) { toast('Không tìm thấy ngân sách trong tài khoản.'); return true; }
    document.querySelector('#modal-overline').textContent = 'LƯU TRỮ NGÂN SÁCH';
    document.querySelector('#modal-title').textContent = `Lưu trữ ngân sách: ${item.name}`;
    document.querySelector('#modal-content').innerHTML = `<p class="form-hint">Ngân sách của danh mục <strong>${esc(item.name)}</strong> sẽ được chuyển sang trạng thái Lưu trữ (Archived). Các giao dịch chi tiêu đã ghi sổ không bị ảnh hưởng.</p>
    <form id="live-budget-delete-form"><p id="live-form-feedback" role="status" aria-live="polite"></p><div class="modal-footer"><button type="button" class="btn btn--outline" data-close-modal>Hủy</button><button type="submit" class="btn btn--primary">Xác nhận lưu trữ</button></div></form>`;
    const form = document.querySelector('#live-budget-delete-form');
    form.addEventListener('submit', async e => {
      e.preventDefault();
      const submit = form.querySelector('[type=submit]');
      const feedback = form.querySelector('#live-form-feedback');
      submit.disabled = true; feedback.textContent = 'Đang lưu trữ ngân sách…';
      try {
        await mutateLiveData('budgets', 'DELETE', null, id);
        dialog.close();
        await loadLiveData();
        reload();
        toast('Đã lưu trữ ngân sách thành công.');
      } catch (error) { feedback.textContent = `Không thể lưu trữ: ${error.message}`; }
      finally { submit.disabled = false; }
    });
    dialog.showModal();
    return true;
  }
  if (name === 'recurring' && (verb === 'pause' || verb === 'resume')) {
    const id = decodeURIComponent(encodedId || '');
    const item = collection.find(x => String(x.id) === id);
    if (!item || !/^\d+$/.test(id)) { toast('Không tìm thấy khoản định kỳ trong tài khoản.'); return true; }
    const currentStatus = String(item.status || '').toUpperCase();
    if ((verb === 'pause' && currentStatus !== 'ACTIVE') || (verb === 'resume' && currentStatus !== 'PAUSED')) {
      toast('Trạng thái khoản định kỳ đã thay đổi. Vui lòng tải lại.'); return true;
    }
    void httpClient(`api/recurring/${encodeURIComponent(id)}/${verb}`, { method: 'POST', data: {} })
      .then(async () => { await loadLiveData(); reload(); toast(verb === 'pause' ? 'Đã tạm dừng khoản định kỳ.' : 'Đã kích hoạt lại khoản định kỳ.'); })
      .catch(error => toast(`Không thể cập nhật khoản định kỳ: ${error.message}`));
    return true;
  }
  if (verb !== 'create' || !['transactions','categories','budgets','recurring'].includes(name)) { toast('Thao tác chưa có API được xác nhận. Không ghi dữ liệu giả.'); return true; }
  if (!ready(name)) { toast('Chưa có API thật để ghi dữ liệu.'); return true; }
  if (needReferenceData(name)) { toast('Hãy tạo ít nhất một ví và danh mục phù hợp trước; hoặc kiểm tra kết nối API.'); return true; }
  const isTransaction = name === 'transactions';
  document.querySelector('#modal-overline').textContent = isTransaction ? '' : 'DỮ LIỆU THẬT';
  document.querySelector('#modal-title').textContent = isTransaction ? 'Thêm Giao dịch' : `Thêm ${TITLES[name]}`;
  document.querySelector('#modal-content').innerHTML = `${isTransaction ? '' : '<p class="form-hint">Dữ liệu chỉ được ghi khi backend xác nhận thành công. Nếu API thiếu chức năng, hệ thống sẽ báo lỗi.</p>'}
  <form id="live-entry-form"><div class="form-grid">${createForm(name)}</div><p id="live-form-feedback" role="status" aria-live="polite"></p><div class="modal-footer"><button type="button" class="btn btn--outline" data-close-modal>Hủy</button><button type="submit" class="btn btn--primary">Lưu vào hệ thống</button></div></form>`;
  const form = document.querySelector('#live-entry-form');
  if (name==='transactions') {
    const types = form.elements.namedItem('type');
    const wallet = form.elements.namedItem('walletId');
    const fromWallet = form.elements.namedItem('fromWalletId');
    const toWallet = form.elements.namedItem('toWalletId');
    const category = form.elements.namedItem('categoryId');
    const goalAction = form.elements.namedItem('goalAction');
    const goal = form.elements.namedItem('goalId');
    if (toWallet.options.length > 1) toWallet.selectedIndex = 1;
    function syncTransactionFields() {
      const isTransfer = types.value === 'TRANSFER';
      const isGoal = types.value === 'SAVINGS_GOAL';
      wallet.disabled = isTransfer;
      category.disabled = isTransfer || isGoal;
      goalAction.disabled = !isGoal;
      goal.disabled = !isGoal;
      fromWallet.disabled = !isTransfer;
      toWallet.disabled = !isTransfer;
      wallet.closest('.field').hidden = isTransfer;
      category.closest('.field').hidden = isTransfer || isGoal;
      goalAction.closest('.field').hidden = !isGoal;
      goal.closest('.field').hidden = !isGoal;
      fromWallet.closest('.field').hidden = !isTransfer;
      toWallet.closest('.field').hidden = !isTransfer;
      wallet.closest('.field').querySelector('label').textContent = isGoal && goalAction.value === 'DEPOSIT' ? 'Ví chuyển' : isGoal ? 'Ví nhận' : 'Ví sử dụng';
      const walletChoices = isGoal ? goalWalletOptions() : walletOptions(types.value !== 'INCOME');
      const previousWallet = wallet.value;
      wallet.innerHTML = walletChoices.map(([v,l])=>`<option value="${esc(v)}">${esc(l)}</option>`).join('');
      if (walletChoices.some(([v]) => String(v) === String(previousWallet))) wallet.value = previousWallet;
      if (isTransfer) {
        const previousFrom = fromWallet.value;
        const previousTo = toWallet.value;
        fromWallet.innerHTML = walletOptions(false).map(([v,l])=>`<option value="${esc(v)}">${esc(l)}</option>`).join('');
        toWallet.innerHTML = walletOptions(true).map(([v,l])=>`<option value="${esc(v)}">${esc(l)}</option>`).join('');
        if ([...fromWallet.options].some(option => option.value === previousFrom)) fromWallet.value = previousFrom;
        if ([...toWallet.options].some(option => option.value === previousTo)) toWallet.value = previousTo;
      }
      const availableGoals = isGoal ? goalOptions(goalAction.value) : [];
      const previousGoal = goal.value;
      if (isGoal) goal.innerHTML = availableGoals.map(([v,l])=>`<option value="${esc(v)}">${esc(l)}</option>`).join('');
      if (availableGoals.some(([v]) => String(v) === String(previousGoal))) goal.value = previousGoal;
      const choices = (isTransfer || isGoal) ? [] : categoryOptions(types.value);
      if (!isTransfer && !isGoal) category.innerHTML = choices.map(([v,l])=>`<option value="${esc(v)}">${esc(l)}</option>`).join('');
      const submit = form.querySelector('[type=submit]');
      const feedback = form.querySelector('#live-form-feedback');
      const sameWallet = isTransfer && fromWallet.value === toWallet.value;
      const missingTransferWallets = isTransfer && (!fromWallet.value || !toWallet.value || walletOptions().length < 2);
      const missingGoalData = isGoal && (!wallet.value || !goal.value || !availableGoals.length);
      submit.disabled = isTransfer ? (missingTransferWallets || sameWallet) : isGoal ? missingGoalData : !choices.length;
      feedback.textContent = missingTransferWallets ? 'Bạn cần có ít nhất hai ví đang sử dụng để chuyển tiền.'
        : sameWallet ? 'Ví gửi và ví nhận phải khác nhau.'
          : missingGoalData ? 'Bạn cần có ví VND và một mục tiêu đang hoạt động.'
            : (!isTransfer && !isGoal && !choices.length ? 'Bạn cần tạo danh mục tương ứng trước khi ghi giao dịch.' : '');
    }
    types.addEventListener('change',syncTransactionFields);
    goalAction.addEventListener('change',syncTransactionFields);
    fromWallet.addEventListener('change',syncTransactionFields);
    toWallet.addEventListener('change',syncTransactionFields);
    syncTransactionFields();
  }
  if (name==='transactions') form.elements.namedItem('occurredOn').value = new Date().toLocaleDateString('en-CA');
  if (name==='budgets') form.elements.namedItem('month').value = new Date().toLocaleDateString('en-CA').slice(0,7);
  form.addEventListener('submit', async e => {
    e.preventDefault(); if (!form.reportValidity()) return;
    const submit = form.querySelector('[type=submit]');
    const feedback = document.querySelector('#live-form-feedback');
    const record = Object.fromEntries(new FormData(form));
    for (const key of ['amount','limitAmount']) if (record[key] !== undefined && record[key] !== '') record[key] = moneyInputValue(record[key]);
    for (const key of ['walletId','goalId','fromWalletId','toWalletId','categoryId','dayOfMonth']) if (record[key] !== undefined && record[key] !== '') record[key] = Number(record[key]);
    if (record.categoryId === '') delete record.categoryId;
    const isTransfer = name === 'transactions' && record.type === 'TRANSFER';
    const isGoal = name === 'transactions' && record.type === 'SAVINGS_GOAL';
    if (name==='transactions' && !isTransfer && !isGoal) {
      const selected = wallets.find(w=>String(w.id)===String(record.walletId));
      record.currency = selected?.currency || 'VND';
    }
    if (isTransfer) delete record.type;
    if (name==='recurring') record.executionMode = 'DRAFT_REMINDER';
    submit.disabled = true; feedback.textContent = 'Đang lưu vào backend…';
    try {
      if (isTransfer) await httpClient('transactions/transfer', { method: 'POST', data: record });
      else if (isGoal) {
        const payload = { walletId: record.walletId, amount: record.amount, allocatedOn: record.occurredOn, note: record.description };
        if (record.goalAction === 'WITHDRAWAL') await GoalApi.withdrawFunds(record.goalId, payload);
        else await GoalApi.allocateFunds(record.goalId, payload);
      } else await mutateLiveData(name,'POST',record);
      dialog.close();
      await loadLiveData();
      reload();
      toast('Đã lưu thành công trên máy chủ.');
    } catch (error) { feedback.textContent = `Không thể lưu: ${error.message}`; }
    finally { submit.disabled = false; }
  });
  dialog.showModal(); return true;
}
