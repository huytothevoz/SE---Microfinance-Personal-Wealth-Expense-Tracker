import { wallets, transactions, budgets, recurring, goals, loans, gold, notifications, categories, adminUsers, trend, incomeTrend, expenseTrend } from './demo-data.js';
/** Construction UI-only shell. No business or authorization is performed client-side. */
const paths = {
  grid: '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
  wallet: '<path d="M20 7V5a2 2 0 0 0-2-2H5a3 3 0 0 0-3 3v12a3 3 0 0 0 3 3h15a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2H5a3 3 0 0 1-3-3"/><path d="M16 14h6"/>',
  arrows: '<path d="M3 8h16m-5-5 5 5-5 5M21 16H5m5-5-5 5 5 5"/>',
  pie: '<path d="M21 12A9 9 0 1 1 12 3v9z"/><path d="M21 9h-6V3a9 9 0 0 1 6 6"/>',
  repeat: '<path d="m17 2 4 4-4 4M3 11V9a3 3 0 0 1 3-3h15M7 22l-4-4 4-4m14-1v2a3 3 0 0 1-3 3H3"/>',
  coin: '<circle cx="12" cy="12" r="9"/><path d="M15.5 8.5c-2-1.5-7-1-7 1.8 0 3 7 1.5 7 4.6 0 3-5 3.4-7 1.7M12 5v14"/>',
  diamond: '<path d="M6 3h12l5 6-11 12L1 9zM1 9h22M6 3l2 6 4 12 4-12 2-6"/>',
  target: '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1"/>',
  chart: '<path d="M3 3v18h18M7 16l4-5 4 2 5-7"/>',
  file: '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M8 13h8M8 17h8"/>',
  check: '<path d="m4 12 5 5L20 6"/>',
  bell: '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/>',
  gear: '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .34 1.88l.07.07-1.94 1.94-.07-.07a1.7 1.7 0 0 0-1.88-.34 1.7 1.7 0 0 0-.92 1.54V21h-3v-.98A1.7 1.7 0 0 0 10.08 18.5a1.7 1.7 0 0 0-1.88.34l-.07.07-1.94-1.94.07-.07a1.7 1.7 0 0 0 .34-1.88A1.7 1.7 0 0 0 5.06 14H4v-3h1.06a1.7 1.7 0 0 0 1.54-.92 1.7 1.7 0 0 0-.34-1.88l-.07-.07L8.13 6.2l.07.07a1.7 1.7 0 0 0 1.88.34A1.7 1.7 0 0 0 11 5.07V4h3v1.07a1.7 1.7 0 0 0 .92 1.54 1.7 1.7 0 0 0 1.88-.34l.07-.07 1.94 1.94-.07.07a1.7 1.7 0 0 0-.34 1.88A1.7 1.7 0 0 0 19.94 11H21v3h-1.06A1.7 1.7 0 0 0 19.4 15z"/>',
  search: '<circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/>',
  plus: '<path d="M12 5v14M5 12h14"/>',
  down: '<path d="m6 9 6 6 6-6"/>',
  users: '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>',
  speaker: '<path d="M3 11v2h4l8 6V5l-8 6H3zM19 9a5 5 0 0 1 0 6M21 6a9 9 0 0 1 0 12"/>',
  layers: '<path d="m12 2 9 5-9 5-9-5 9-5zM3 12l9 5 9-5M3 17l9 5 9-5"/>',
  user: '<circle cx="12" cy="8" r="4"/><path d="M4 21v-2a8 8 0 0 1 16 0v2"/>',
  calendar: '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 11h18"/>',
  info: '<circle cx="12" cy="12" r="10"/><path d="M12 11v6M12 7h.01"/>',
  lock: '<rect x="4" y="10" width="16" height="12" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/>',
  download: '<path d="M12 3v12m-5-5 5 5 5-5M4 17v4h16v-4"/>',
  dots: '<circle cx="5" cy="12" r="1"/><circle cx="12" cy="12" r="1"/><circle cx="19" cy="12" r="1"/>',
  clock: '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>'

};
function icon(key) {
  return `<svg class="nav-icon" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">\
${paths[key] || paths.grid}</svg>`;

}
function safe(value) {
  return String(value).replace(/[&<>"']/g, x => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#39;'

  }[x]));

}
const money = value => new Intl.NumberFormat('vi-VN', { maximumFractionDigits: 0 }).format(value) + ' đ';
const shortMoney = value => Math.abs(value) >= 1e6 ? (value / 1e6).toLocaleString('vi-VN', { maximumFractionDigits: 2 }) + ' triệu' : money(value);
const percent = (a, b) => b > 0 ? Math.round(a / b * 100) : 0;
const USER_NAV = [
  { group: 'TỔNG QUAN', items: [['overview', 'Tổng quan', 'grid']] },
  {
    group: 'QUẢN LÝ TÀI CHÍNH',
    items: [
      ['transactions', 'Giao dịch', 'arrows'],
      ['wallets', 'Ví & tài khoản', 'wallet'],
      ['categories', 'Danh mục thu chi', 'layers'],
      ['budgets', 'Ngân sách', 'pie'],
      ['recurring', 'Định kỳ', 'repeat']

    ]

  },
  {
    group: 'TÀI SẢN & TÍCH LŨY',
    items: [
      ['loans', 'Khoản vay & nợ', 'coin'],
      ['gold', 'Tài sản vàng', 'diamond'],
      ['goals', 'Mục tiêu tài chính', 'target']

    ]

  },
  {
    group: 'PHÂN TÍCH & QUẢN LÝ',
    items: [
      ['reports', 'Báo cáo tài chính', 'chart'],
      ['reconciliation', 'Đối soát & xuất CSV', 'file'],
      ['notifications', 'Thông báo', 'bell'],
      ['settings', 'Cài đặt & bảo mật', 'gear']

    ]

  }

];
const ADMIN_NAV = [
  {
    group: 'QUẢN TRỊ HỆ THỐNG',
    items: [
      ['admin-overview', 'Tổng quan hệ thống', 'grid'],
      ['admin-users', 'Người dùng', 'users'],
      ['admin-categories', 'Danh mục mặc định', 'layers'],
      ['admin-broadcasts', 'Thông báo toàn hệ thống', 'speaker'],
      ['admin-settings', 'Cấu hình & nhật ký', 'gear']

    ]

  }

];
const META = {
  overview: ['Tổng quan', 'Bức tranh tài chính trong một màn hình', 'FR08 · FR10 · FR11 / UC10'],
  transactions: ['Giao dịch', 'Quản lý thu chi, chuyển ví và lịch sử bút toán', 'FR03 · FR04 / UC03'],
  wallets: ['Ví & tài khoản', 'Mọi nguồn tiền của bạn tại một nơi', 'FR02 · FR04 / UC02'],
  categories: ['Danh mục thu chi', 'Tổ chức thu nhập và chi tiêu có hệ thống', 'FR03.05'],
  budgets: ['Ngân sách', 'Theo dõi hạn mức và nhận diện rủi ro bội chi', 'FR05 / UC05'],
  recurring: ['Giao dịch định kỳ', 'Không bỏ lỡ khoản thanh toán hoặc thu nhập thường xuyên', 'FR06 / UC04'],
  loans: ['Khoản vay & nợ', 'Theo dõi gốc, lãi/phí và lịch trả nợ', 'FR09 / UC06 · UC07'],
  gold: ['Tài sản vàng', 'Đánh giá tài sản tích lũy ngoài ví', 'FR08 / UC08'],
  goals: ['Mục tiêu tài chính', 'Chuyển kế hoạch tích lũy thành hành động', 'FR07 / UC09'],
  reports: ['Báo cáo tài chính', 'Hiểu dòng tiền và tỷ lệ tiết kiệm', 'FR10 · FR11 · FR12 / UC10 · UC11'],
  reconciliation: ['Đối soát & xuất CSV', 'Kiểm tra tính nhất quán và lưu trữ báo cáo', 'FR12 / UC11'],
  notifications: ['Trung tâm thông báo', 'Theo dõi các cảnh báo và nhắc nhở quan trọng', 'FR05 · FR09 · FR13'],
  settings: ['Cài đặt cá nhân', 'Hồ sơ, tiền cơ sở, bảo mật phiên đăng nhập', 'FR01 · FR15 / UC01'],
  'admin-overview': ['Tổng quan hệ thống', 'Thống kê vĩ mô, không bao gồm dữ liệu tài chính cá nhân', 'FR14 / UC12'],
  'admin-users': ['Quản lý người dùng', 'Trạng thái tài khoản và quyền truy cập', 'FR14 / UC12'],
  'admin-categories': ['Danh mục mặc định', 'Thiết lập danh mục mẫu cho người dùng mới', 'FR13 / UC13'],
  'admin-broadcasts': ['Thông báo hệ thống', 'Soạn và gửi broadcast announcement', 'FR13 / UC13'],
  'admin-settings': ['Cấu hình hệ thống', 'Tham số vận hành và nhật ký quản trị', 'FR13 · FR14 / UC13']

};
const state = {
  page: 'overview',
  admin: false,
  txFilter: 'all',
  search: '',
  onlyActiveWallets: true,
  reportRange: '6m'

};
const app = document.querySelector('#app-content'); const nav = document.querySelector('#sidebar-nav'); const sidebar = document.querySelector('#sidebar'); const dialog = document.querySelector('#action-dialog');
function heading(id, buttons = '') {
  const [name, description, ref] = META[id];
  return `<div class="page-heading">\
<div>\
<div class="page-kicker">\
${safe(ref)}\
</div>\
<h1>\
${safe(name)}\
</h1>\
<p>\
${safe(description)}\
</p>\
</div>\
<div class="page-actions">\
${buttons}</div></div>`;

}
const btn = (label, action, style = 'primary', ico = 'plus') => `<button type="button" class="btn btn--${style} btn-sm" data-action="${action}">${icon(ico)} ${label}</button>`;
const panel = (title, subtitle, content, right = '') => `<section class="panel">\
<div class="panel-title">\
<div>\
<h2>\
${title}</h2>${subtitle ? `<p>\
${subtitle}</p>` : ''}\
</div>\
<div class="panel-tools">\
${right}</div></div>${content}</section>`;
function renderNav() {
  let groups = state.admin ? ADMIN_NAV : USER_NAV;
  nav.innerHTML = groups.map(group => `<div class="sidebar-label">\
${group.group}\
</div>\
<div class="sidebar-group">\
${group.items.map(([id, label, ico]) => `<button type="button" data-page="${id}" class="nav-link ${state.page === id ? 'is-active' : ''}" ${state.page === id ? 'aria-current="page"' : ''}>${icon(ico)}\
<span>\
${label}</span>${id === 'notifications' ? '<span class="nav-count">4</span>' : ''}</button>`).join('')}</div>`).join('') + `<div class="sidebar-label">CHẾ ĐỘ HIỂN THỊ</div>\
<div class="sidebar-group">\
<button class="nav-link" data-action="switch-role">\
${icon(state.admin ? 'user' : 'lock')}\
<span>\
${state.admin ? 'Quay về người dùng' : 'Xem giao diện Admin'}\
</span>\
</button>\
</div>\
<p class="admin-preview-note">Chuyển vai trò chỉ để trình diễn UI, không cấp quyền thật.</p>`;
  document.body.classList.toggle('admin-mode', state.admin);

}
function navigate(page) {
  if (!META[page]) return;
  state.page = page;
  renderNav();
  document.querySelector('#breadcrumb-name').textContent = META[page][0];
  app.innerHTML = `<div class="demo-banner">\
<span class="demo-icon">\
${icon('info')}\
</span>\
<span>\
<strong>\
${state.admin ? 'Bản xem trước Admin' : 'Chế độ demo'}:</strong> Các con số chỉ minh họa; biểu mẫu chưa ghi vào MySQL, chưa có xác thực và ghi sổ thực tế. ${state.admin ? 'Admin không được xem sổ sách cá nhân.' : ''}</span></div>${PAGES[page]()}`;
  closeSidebar();
  const hash = '#' + page;
  if (location.hash !== hash) history.replaceState(null, '', hash);
  window.scrollTo({ top: 0, behavior: 'instant' });

}
function closeSidebar() {
  sidebar.classList.remove('is-open');
  document.querySelector('#mobile-overlay').classList.remove('is-open');
  document.querySelector('#sidebar-toggle').setAttribute('aria-expanded', 'false');

}
function kpi(label, value, desc, ico = 'chart', tone = '', delta = '') {
  return `<div class="kpi-card">\
<div class="kpi-top">\
<span class="kpi-label">\
${label}\
</span>\
<div class="kpi-icon ${tone}">${icon(ico)}\
</div>\
</div>\
<div class="kpi-number">\
${value}\
</div>\
<div class="kpi-desc">\
${delta ? `<span class="delta ${delta.startsWith('-') ? 'down' : ''}">${delta}</span>` : ''}${desc}</div></div>`;

}
function lineChart() {
  const max = 145, min = 65, coords = trend.map((v, i) => `${30 + i * 78},${190 - (v - min) / (max - min) * 155}`);
  const area = `M${coords[0]} L${coords.slice(1).join(' L')} L498,190 L30,190 Z`;
  return `<svg class="chart-svg" viewBox="0 0 525 220" role="img" aria-label="Tài sản ròng tăng từ 76 lên 128,45 triệu đồng qua 7 kỳ">\
<defs>\
<linearGradient id="netFill" x1="0" y1="0" x2="0" y2="1">\
<stop offset="0" stop-color="#39a5b4" stop-opacity=".2"/>\
<stop offset="1" stop-color="#39a5b4" stop-opacity="0"/>\
</linearGradient>\
</defs>\
${[36, 85, 135, 185].map(y => `<path class="chart-gridline" d="M30 ${y}H510"/>`).join('')}\
<path d="${area}" fill="url(#netFill)"/>\
<path d="M${coords.join(' L')}" fill="none" stroke="#17899d" stroke-width="3.4" stroke-linecap="round" stroke-linejoin="round"/>\
<circle cx="498" cy="${190 - (128.45 - min) / (max - min) * 155}" r="5" fill="#17899d" stroke="white" stroke-width="3"/>\
<text x="5" y="40" class="chart-label">140tr</text>\
<text x="5" y="90" class="chart-label">115tr</text>\
<text x="5" y="140" class="chart-label">90tr</text>\
<text x="5" y="190" class="chart-label">65tr</text>\
</svg>\
<div class="chart-months">\
<span>Thg 4</span>\
<span>Thg 5</span>\
<span>Thg 6</span>\
<span>Thg 7</span>\
<span>Thg 8</span>\
<span>Thg 9</span>\
<span>Thg 10</span>\
</div>`;

}
function budgetsUI(limit = 5) {
  return `<div class="budget-stack">\
${budgets.slice(0, limit).map(b => {
    const used = percent(b.spent, b.limit);
    return `<div class="budget-row">\
<div class="budget-info">\
<strong>\
${safe(b.name)}\
</strong>\
<span>\
${money(b.spent)} / ${money(b.limit)}\
</span>\
</div>\
<div class="bar-track">\
<div class="bar-fill ${used >= 100 ? 'danger' : used >= 80 ? 'warn' : ''}" style="width:${Math.min(used, 100)}%">\
</div>\
</div>\
<div class="budget-foot">\
${used}% hạn mức ${used >= 100 ? '· Đã vượt mức' : used >= 80 ? '· Cần chú ý' : ''}</div></div>`

  }).join('')}</div>`;

}
function txTable(items = transactions) {
  if (!items.length) return `<div class="empty-state">\
<span class="empty-icon">⌕</span>\
<strong>Không có giao dịch phù hợp</strong>Thử thay đổi từ khóa hoặc bộ lọc.</div>`;
  return `<div class="data-table-wrap">\
<table class="data-table">\
<thead>\
<tr>\
<th>Giao dịch</th>\
<th>Danh mục</th>\
<th>Ngày</th>\
<th>Ví / nguồn tiền</th>\
<th>Giá trị</th>\
<th>Trạng thái</th>\
<th>\
</th>\
</tr>\
</thead>\
<tbody>\
${items.map(t => `<tr>\
<td>\
<div class="table-name">\
<span class="table-icon">\
${safe(t.icon)}\
</span>\
<span>\
${safe(t.name)}<small>${safe(t.id)}</small></span></div></td><td>${safe(t.category)}</td><td>${safe(t.date)}</td><td>${safe(t.wallet)}</td><td class="amount ${t.type === 'income' ? 'positive' : t.type === 'expense' ? 'negative' : ''}">${t.type === 'income' ? '+' : t.type === 'expense' ? '−' : '⇄'} ${money(Math.abs(t.amount))}\
</td>\
<td>\
<span class="badge ${t.status === 'DRAFT' ? 'warn' : 'neutral'}">${t.status === 'DRAFT' ? 'Bản nháp' : 'Đã ghi sổ'}\
</span>\
</td>\
<td>\
<button class="text-action" data-detail="${safe(t.id)}">Chi tiết</button></td></tr>`).join('')}</tbody></table></div>`;

}
function goalsUI() {
  return `<div class="card-grid">\
${goals.map(g => `<article class="goal-card">\
<div class="goal-top">\
<span class="goal-emoji">\
${g.icon}\
</span>\
<div>\
<h3>\
${g.name}\
</h3>\
<p>Thời hạn ${g.deadline}\
</p>\
</div>\
</div>\
<div class="goal-amount">\
<strong>\
${money(g.current)}</strong><small>/ ${money(g.target)}\
</small>\
</div>\
<div class="bar-track">\
<div class="bar-fill" style="width:${percent(g.current, g.target)}%">\
</div>\
</div>\
<div class="goal-footer">\
<span>\
${percent(g.current, g.target)}% hoàn thành</span>\
<strong>Cần thêm ${money(g.monthly)}/tháng</strong>\
</div>\
<button class="btn btn--outline btn-sm" data-action="goal-allocate">\
${icon('plus')} Phân bổ tích lũy</button>\
<div class="status-row">\
<button class="text-action" data-action="goal-complete">Hoàn thành</button>\
<button class="text-action" data-action="goal-cancel">Hủy mục tiêu</button>\
</div>\
</article>`).join('')}</div>`;

}
function reportBarChart() {
  const max = 20;
  return `<div class="chart-bars">\
${incomeTrend.map((v, i) => `<div class="chart-bar-group">\
<div class="chart-bar" style="height:${v / max * 100}%" title="Thu nhập: ${v} triệu">\
</div>\
<div class="chart-bar out" style="height:${expenseTrend[i] / max * 100}%" title="Chi tiêu: ${expenseTrend[i]} triệu"></div></div>`).join('')}\
</div>\
<div class="chart-bars-labels">\
${[
      'Thg 5',
      'Thg 6',
      'Thg 7',
      'Thg 8',
      'Thg 9',
      'Thg 10'

    ].map(m => `<span>\
${m}</span>`).join('')}\
</div>\
<div class="chart-summary">\
<span class="legend">Thu nhập</span>\
<span class="legend secondary">Chi tiêu</span>\
<span>Đơn vị: triệu VND · dữ liệu minh họa</span>\
</div>`;

}
function monthlySummary() {
  return `<div class="report-stats">\
<div class="report-stat">\
<span>Thu nhập tháng</span>\
<strong class="positive">\
${money(18500000)}\
</strong>\
</div>\
<div class="report-stat">\
<span>Chi tiêu tháng</span>\
<strong>\
${money(8240000)}\
</strong>\
</div>\
<div class="report-stat">\
<span>Saving Rate</span>\
<strong>55,46%</strong>\
</div>\
</div>`;

}
function overview() {
  return `${heading('overview', btn('Thêm giao dịch', 'tx-add'))}\
<div class="kpi-grid">\
${kpi('Tài sản ròng', money(128450000), 'So với kỳ trước', 'chart', '', '+12,8%')}${kpi('Tổng tài sản', money(143550000), 'Ví tiền + tiết kiệm + vàng', 'wallet', 'blue', '+8,2%')}${kpi('Tổng nợ phải trả', money(15100000), 'Chỉ gồm dư nợ đi vay', 'coin', 'orange')}${kpi('Dòng tiền thuần', money(10260000), 'Thu nhập − Chi tiêu tháng này', 'arrows', 'green', '+5,3%')}\
</div>\
<div class="panel-grid">\
${panel('Biến động tài sản ròng', 'Diễn biến 7 kỳ gần nhất · VND', lineChart(), '<span class="panel-pill">7 tháng gần nhất</span>')}${panel('Cơ cấu tài sản',
    'Phân bổ nguồn lực hiện có',
    `<div class="doughnut-wrap">\
<div class="donut">\
<div class="donut-center">\
<strong>143,55tr</strong>\
<span>Tổng tài sản</span>\
</div>\
</div>\
<div class="donut-legend">\
<span>\
<i style="background:#197c96">\
</i>Tiết kiệm<strong>45%</strong>\
</span>\
<span>\
<i style="background:#8ecac5">\
</i>Vàng tích lũy<strong>36%</strong>\
</span>\
<span>\
<i style="background:#aac7e5">\
</i>Ngân hàng<strong>15%</strong>\
</span>\
<span>\
<i style="background:#e3bb83">\
</i>Khác<strong>4%</strong>\
</span>\
</div>\
</div>`)}\
</div>\
<div class="panel-grid">\
${panel('Ngân sách tháng 10', 'Cảnh báo theo ngưỡng 80% / 100%', budgetsUI(3), `<button class="text-action" data-page="budgets">Xem ngân sách →</button>`)}${panel('Mục tiêu đang thực hiện',
      'Các cột mốc tích lũy quan trọng',
      goals.slice(0, 2).map(g => `<div class="budget-row" style="margin:0 0 20px">\
<div class="budget-info">\
<strong>\
${g.name}\
</strong>\
<span>\
${percent(g.current, g.target)}%</span>\
</div>\
<div class="bar-track">\
<div class="bar-fill" style="width:${percent(g.current, g.target)}%">\
</div>\
</div>\
<div class="budget-foot">\
${money(g.current)} / ${money(g.target)}</div></div>`).join(''),
      `<button class="text-action" data-page="goals">Tất cả mục tiêu →</button>`)}</div>${panel('Giao dịch gần đây',
        'Giao dịch từ sổ cái minh họa',
        txTable(transactions.slice(0, 5)),
        `<button class="text-action" data-page="transactions">Xem tất cả →</button>`)}\
<div class="foot-source">Công thức theo SRS: Net Worth = Assets − Liabilities · Net Cash Flow = Income − Expense. Dữ liệu ví dụ, không phải số thực.</div>`;

}
function transactionPage() {
  const filtered = transactions.filter(t => (state.txFilter === 'all' || t.type === state.txFilter) && [t.name, t.id, t.category, t.wallet].join(' ').toLocaleLowerCase('vi').includes(state.search.toLocaleLowerCase('vi')));
  return `${heading('transactions', btn('Ghi giao dịch', 'tx-add') + btn('Chuyển ví', 'transfer', 'outline', 'arrows'))}\
<div class="kpi-grid">\
${kpi('Thu nhập tháng', money(18500000), 'Không bao gồm tiền nhận vay', 'arrows', 'green')}${kpi('Chi tiêu tháng', money(8240000), 'Không bao gồm khoản trả nợ gốc', 'wallet', 'orange')}${kpi('Chuyển nội bộ', money(2500000), 'Không tính thu / chi', 'repeat', 'blue')}${kpi('Giao dịch nháp', '1 bản nháp', 'Chưa vào báo cáo', 'file')}</div>${panel('Lịch sử giao dịch',
    'Theo dõi Posted, Draft và chi tiết bút toán',
    `<div class="filter-row">\
<div class="tabs" role="tablist">\
${[['all', 'Tất cả'], ['income', 'Thu nhập'], ['expense', 'Chi tiêu'], ['transfer', 'Chuyển ví']].map(([id, label]) => `<button class="tab ${state.txFilter === id ? 'active' : ''}" data-tx-filter="${id}" role="tab" aria-selected="${state.txFilter === id}">${label}</button>`).join('')}\
</div>\
<div class="filter-controls">\
<input id="transaction-search" class="input-sm" type="search" placeholder="Tìm tên, mã, danh mục..." value="${safe(state.search)}" aria-label="Tìm kiếm giao dịch">\
<button class="btn btn--outline btn-sm" data-action="export">\
${icon('download')} CSV mẫu</button>\
</div>\
</div>\
<div id="transaction-table">\
${txTable(filtered)}</div>`)}\
<div class="hint-box section-gap" style="margin-top:18px">\
<strong>Nguyên tắc kế toán kép (BR01–BR06):</strong> bản ghi đã Posted không sửa/xóa vật lý; thao tác điều chỉnh về sau phải tạo bút toán đảo (Reversal) và giao dịch thay thế. Chức năng đang ở giai đoạn minh họa UI.</div>`;

}
function walletPage() {
  return `${heading('wallets', btn('Tạo ví mới', 'wallet-add') + btn('Cập nhật tỷ giá', 'rate-refresh', 'outline', 'repeat'))}\
<div class="filter-row">\
<p class="description">Quản lý tiền mặt, ngân hàng, ví điện tử và tài khoản tiết kiệm. Số dư USD quy đổi theo tỷ giá minh họa.</p>\
<button class="btn btn--outline btn-sm" data-action="wallet-toggle">\
${icon('layers')} ${state.onlyActiveWallets ? 'Hiện ví lưu trữ' : 'Ẩn ví lưu trữ'}\
</button>\
</div>\
<div class="card-grid section-gap">\
${wallets.filter(w => w.active || !state.onlyActiveWallets).map(w => `<article class="wallet-card">\
<div class="wallet-header">\
<div>\
<span class="wallet-name">\
${w.name}\
</span>\
<span class="wallet-sub">\
${w.number}\
</span>\
</div>\
<span class="wallet-type">\
${w.type}\
</span>\
</div>\
<div>\
<div class="wallet-balance">\
${w.currency === 'USD' ? w.balance + ' USD' : money(w.balance)}\
</div>\
<div class="wallet-meta">\
${w.currency === 'USD' ? `≈ ${money(w.baseBalance)} · tỷ giá minh họa` : `Tiền tệ ${w.currency}`} ${!w.active ? '· Đã lưu trữ' : ''}</div>${w.active && w.balance === 0 ? `<button class="text-action" style="margin-top:9px" data-action="wallet-archive">Lưu trữ ví →</button>` : w.active ? `<button class="text-action" style="margin-top:9px" data-action="wallet-archive">Quản lý / lưu trữ →</button>` : `<button class="text-action" style="margin-top:9px" data-action="wallet-archive">Xem lịch sử ví →</button>`}</div></article>`).join('')}\
</div>\
<div class="info-grid">\
${panel('Tổng số dư các ví',
    'Chưa bao gồm tài sản vàng',
    `<div class="metric-list">\
<div class="metric-item">\
<span>Tổng số dư quy đổi VND</span>\
<strong>\
${money(91550000)}\
</strong>\
</div>\
<div class="metric-item">\
<span>Ví đang sử dụng</span>\
<strong>5</strong>\
</div>\
<div class="metric-item">\
<span>Ví đã lưu trữ</span>\
<strong>1</strong>\
</div>\
</div>`)}${panel('Quy đổi tiền tệ',
      'Tỷ giá dùng trong màn hình demo',
      `<div class="metric-list">\
<div class="metric-item">\
<span>VND (tiền cơ sở)</span>\
<strong>1</strong>\
</div>\
<div class="metric-item">\
<span>USD → VND</span>\
<strong>26.000 VND / USD</strong>\
</div>\
<div class="metric-item">\
<span>Ghi nhận tỷ giá</span>\
<strong>Snapshot khi ghi sổ (dự kiến)</strong>\
</div>\
</div>`)}\
</div>\
<div class="hint-box" style="margin-top:17px">Ví không còn số dư và không có giao dịch định kỳ hoạt động mới được Archive. Số dư mở ví phải được hạch toán đối ứng vốn chủ sở hữu khi backend triển khai.</div>`;

}
function categoryPage() {
  return `${heading('categories', btn('Tạo danh mục', 'category-add'))}${panel('Danh mục cá nhân',
    'Dùng để phân loại, lọc giao dịch và lập ngân sách',
    `<div class="card-grid">\
${categories.map((c, i) => `<div class="mini-panel">\
<div class="status-row">\
<div class="goal-top">\
<span class="goal-emoji" style="height:35px;width:35px">\
${i % 2 ? '◈' : '◎'}\
</span>\
<div>\
<h3>\
${c.name}\
</h3>\
<p>Danh mục ${c.type.toLowerCase()}\
</p>\
</div>\
</div>\
<span class="badge ${c.type === 'Thu nhập' ? 'blue' : 'neutral'}">${c.type}\
</span>\
</div>\
<div style="margin-top:18px">\
<button class="text-action" data-action="category-edit">Xem / chỉnh sửa demo →</button>\
</div>\
</div>`).join('')}</div>`)}\
<div class="hint-box" style="margin-top:16px">Danh mục cá nhân có thể đổi tên, lưu trữ; danh mục mặc định do Admin quản lý riêng, không xem được dữ liệu tài chính người dùng.</div>`;

}
function budgetPage() {
  return `${heading('budgets', btn('Tạo ngân sách', 'budget-add') + btn('Chỉnh sửa / lưu trữ', 'budget-manage', 'outline', 'gear'))}\
<div class="kpi-grid">\
${kpi('Tổng ngân sách', money(7700000), '5 danh mục tháng 10', 'pie')}${kpi('Chi tiêu thuộc ngân sách', money(5770000), 'Mẫu theo danh mục', 'wallet', 'blue')}${kpi('Cảnh báo vàng', '1 danh mục', 'Đạt từ 80% hạn mức', 'bell', 'orange')}${kpi('Vượt ngân sách', '1 danh mục', 'Đạt từ 100% hạn mức', 'info', 'orange')}</div>${panel('Hạn mức theo danh mục', 'Kỳ ngân sách: 10/2026', budgetsUI(), `<span class="panel-pill">Tháng 10/2026</span>`)}\
<div class="info-grid" style="margin-top:17px">\
<div class="mini-panel">\
<h3>Ngưỡng cảnh báo</h3>\
<p>\
<span class="badge warn">Từ 80%</span> Ngân sách chuyển trạng thái cần chú ý.<br>\
<br>\
<span class="badge danger">Từ 100%</span> Cảnh báo vượt ngưỡng; ứng dụng không chặn giao dịch thật.</p>\
</div>\
<div class="mini-panel">\
<h3>Giữ ngân sách chính xác</h3>\
<p>Ngân sách tính chi tiêu ròng, không tính chuyển ví nội bộ hoặc trả nợ gốc. Backend phải xử lý chống thông báo trùng theo BR08.</p>\
</div>\
</div>`;

}
function recurringPage() {
  return `${heading('recurring', btn('Tạo lịch định kỳ', 'recurring-add'))}\
<div class="kpi-grid">\
${kpi('Định kỳ hoạt động', '3 lịch', 'Đang theo dõi', 'repeat')}${kpi('Sắp đến hạn', money(369000), 'Trong tháng 10', 'calendar', 'orange')}${kpi('Tạo bản nháp', '2 lịch', 'Chế độ mặc định', 'file', 'blue')}${kpi('Tự động ghi sổ', '1 lịch', 'Người dùng chủ động bật', 'check', 'green')}</div>${panel('Các khoản định kỳ',
    'Hỗ trợ nhắc tạo Draft / tự động ghi sổ (sẽ xử lý bằng PHP scheduler)',
    `<div class="data-table-wrap">\
<table class="data-table">\
<thead>\
<tr>\
<th>Khoản định kỳ</th>\
<th>Ví</th>\
<th>Hạn kế tiếp</th>\
<th>Số tiền</th>\
<th>Chế độ</th>\
<th>Trạng thái</th>\
<th>\
</th>\
</tr>\
</thead>\
<tbody>\
${recurring.map(r => `<tr>\
<td>\
<div class="table-name">\
<span class="table-icon">↻</span>\
<span>\
${r.name}<small>${r.category}</small></span></div></td><td>${r.wallet}</td><td>${r.date}</td><td class="amount">${money(r.amount)}</td><td>${r.mode}\
</td>\
<td>\
<span class="badge ${r.active ? '' : 'neutral'}">${r.active ? 'Hoạt động' : 'Tạm dừng'}\
</span>\
</td>\
<td>\
<button class="text-action" data-action="recurring-toggle">\
${r.active ? 'Tạm dừng' : 'Kích hoạt'}\
</button> · <button class="text-action" data-action="recurring-delete">Xóa</button>\
</td>\
</tr>`).join('')}</tbody></table></div>`)}\
<div class="hint-box" style="margin-top:17px">SYS02 dự kiến quét lịch mỗi 5 phút, tránh sinh trùng theo <code>(subscriptionId, dueDate)</code> và lùi về cuối tháng nếu tháng thiếu ngày. Chưa có scheduler thực thi ở bản demo.</div>`;

}
function loanPage() {
  return `${heading('loans', btn('Tạo khoản vay / nợ', 'loan-add') + btn('Ghi trả nợ', 'loan-pay', 'outline', 'arrows'))}\
<div class="kpi-grid">\
${kpi('Dư nợ phải trả', money(15100000), '2 khoản đi vay', 'coin', 'orange')}${kpi('Khoản cho vay còn lại', money(1200000), 'Theo dõi riêng tài sản cho vay', 'arrows', 'blue')}${kpi('Đã thanh toán gốc', money(9900000), 'Chưa tính lãi/phí', 'check', 'green')}${kpi('Sắp đến hạn', '20/10/2026', 'Khoản vay mua laptop', 'calendar')}\
</div>\
<div class="card-grid section-gap">\
${loans.map(l => `<article class="loan-card">\
<div class="status-row">\
<span class="badge ${l.kind === 'Đi vay' ? 'warn' : 'blue'}">${l.kind}\
</span>\
<span class="description">\
${l.id.toUpperCase()}\
</span>\
</div>\
<div>\
<h3>\
${l.name}\
</h3>\
<p>Đối tác: ${l.partner}\
</p>\
</div>\
<div>\
<span class="description">Dư gốc còn lại</span>\
<div class="loan-amount">\
${money(l.remaining)}\
</div>\
<div class="budget-foot" style="margin:7px 0">Đã thanh toán ${money(l.paid)} / ${money(l.principal)}\
</div>\
<div class="bar-track">\
<div class="bar-fill" style="width:${percent(l.paid, l.principal)}%">\
</div>\
</div>\
</div>\
<div class="status-row">\
<span class="description">Hạn tiếp: ${l.due}\
</span>\
<button class="text-action" data-action="loan-view">Xem lịch trả →</button>\
</div>\
</article>`).join('')}\
</div>\
<div class="info-grid">\
${panel('Quy tắc trả nợ',
    'Phân biệt phần gốc và lãi/phí',
    `<div class="metric-list">\
<div class="metric-item">\
<span>Trả gốc</span>\
<strong>Giảm dư nợ và ví</strong>\
</div>\
<div class="metric-item">\
<span>Trả lãi / phí</span>\
<strong>Hạch toán chi phí</strong>\
</div>\
<div class="metric-item">\
<span>Nhận tiền đi vay</span>\
<strong>Không tính thu nhập</strong>\
</div>\
</div>`)}${panel('Nhắc lịch trả nợ',
      'Tác vụ nền SYS03',
      `<div class="hint-box">\
<strong>Dự kiến:</strong> nhắc trước hạn 3 ngày, ghi nhận tiến độ thanh toán và chuyển trạng thái Đã trả xong khi dư gốc về 0.</div>`)}</div>`;

}
function goldPage() {
  return `${heading('gold', btn('Thêm tài sản vàng', 'gold-add') + btn('Cập nhật định giá', 'gold-revalue', 'outline', 'chart'))}\
<div class="kpi-grid">\
${kpi('Giá trị tài sản vàng', money(52000000), 'Tài sản tích lũy ngoài ví', 'diamond', '', '+4,1%')}${kpi('Số loại vàng', '2 loại', 'Nhẫn 9999 · SJC', 'layers', 'blue')}${kpi('Tổng khối lượng', '5 chỉ', 'Đơn vị chỉ vàng', 'coin', 'orange')}${kpi('Ngày định giá', '12/10/2026', 'Giá nhập thủ công trong demo', 'calendar', 'green')}\
</div>\
<div class="card-grid section-gap">\
${gold.map(g => `<article class="gold-card">\
<div class="status-row">\
<span class="goal-emoji">◇</span>\
<span class="badge blue">Đang sở hữu</span>\
</div>\
<div>\
<h3>\
${g.name}\
</h3>\
<p>Số lượng: ${g.weight}\
</p>\
</div>\
<div>\
<span class="description">Giá trị định giá</span>\
<div class="loan-amount">\
${money(g.value)}\
</div>\
<p>\
${money(g.price)} / chỉ · Cập nhật ${g.date}\
</p>\
</div>\
<button class="text-action" style="text-align:left" data-action="gold-liquidate">Thanh lý / xóa tài sản →</button>\
</article>`).join('')}\
</div>\
<div class="hint-box">Chỉ theo dõi vàng nhẫn và vàng miếng SJC theo FR08. Không bao gồm nhà đất, xe cộ hay khấu hao. Định giá vàng có thể biến động và cần dữ liệu thị giá đáng tin cậy khi tích hợp thực tế.</div>`;

}
function goalsPage() {
  return `${heading('goals', btn('Tạo mục tiêu', 'goal-add'))}\
<div class="kpi-grid">\
${kpi('Mục tiêu đang hoạt động', '3 mục tiêu', 'Đang tích lũy', 'target')}${kpi('Tổng cần tích lũy', money(55000000), 'Tổng giá trị đích', 'coin', 'blue')}${kpi('Đã phân bổ', money(32400000), 'Không cộng thêm vào tổng tài sản', 'check', 'green')}${kpi('Tiến độ tổng', '58,9%', 'Dữ liệu minh họa', 'chart', 'orange')}</div>${goalsUI()}\
<div class="hint-box" style="margin-top:17px">\
<strong>BR10:</strong> phân bổ tiền cho mục tiêu chỉ gắn nhãn khoản tích lũy, không làm tăng tổng tài sản ròng. Hệ thống dự kiến gợi ý số tiền cần để dành mỗi tháng theo thời hạn.</div>`;

}
function reportPage() {
  return `${heading('reports', btn('Xuất CSV mẫu', 'export', 'outline', 'download'))}\
<div class="filter-row">\
<div class="tabs">\
<button class="tab active" type="button">Tổng quan báo cáo</button>\
<button class="tab" type="button" data-page="reconciliation">Đối soát</button>\
</div>\
<span class="date-filter">Kỳ minh họa: Tháng 5 – 10/2026</span>\
</div>\
<div class="kpi-grid">\
${kpi('Net Worth', money(128450000), 'Tổng tài sản trừ nợ', 'chart')}${kpi('Net Cash Flow', money(10260000), 'Thu nhập trừ chi tiêu', 'arrows', 'green')}${kpi('Saving Rate', '55,46%', '(18,5 − 8,24) / 18,5 × 100%', 'pie', 'blue')}${kpi('Tài sản tích lũy', money(117000000), 'Tiết kiệm + vàng', 'diamond', 'orange')}\
</div>\
<div class="panel-grid">\
${panel('Dòng tiền 6 tháng', 'Biểu đồ so sánh thu nhập và chi phí', reportBarChart())}${panel('Phân tích tiết kiệm',
    'Tháng 10/2026',
    `${monthlySummary()}\
<div class="hint-box" style="margin-top:18px">Saving Rate = (Income − Expense) / Income. Nếu Income ≤ 0, hiển thị “N/A”, không chia cho 0. Âm khi chi tiêu vượt thu nhập.</div>`)}</div>${panel('Biến động tài sản ròng', 'Theo lịch sử snapshot hàng ngày / theo kỳ', lineChart())}\
<div class="foot-source">Báo cáo chưa lấy dữ liệu MySQL và không đại diện cho lịch sử giao dịch thật.</div>`;

}
function reconciliationPage() {
  return `${heading('reconciliation', btn('Tải CSV mẫu', 'export', 'outline', 'download') + btn('Kiểm tra đối soát', 'reconcile', 'primary', 'check'))}\
<div class="kpi-grid">\
${kpi('Ví đang theo dõi', '5 ví', 'Bao gồm ví USD', 'wallet')}${kpi('Ví khớp sổ (demo)', '5 / 5', 'Chỉ là trạng thái giả lập UI', 'check', 'green')}${kpi('Giao dịch Posted', '7 bản ghi mẫu', 'Đã loại trừ bản nháp', 'file', 'blue')}${kpi('Xuất dữ liệu', 'CSV UTF-8 BOM', 'Có bảo vệ công thức', 'download', 'orange')}</div>${panel('Đối chiếu số dư ví',
    'Số dư cache ↔ số dư kết xuất từ ledger (hiển thị minh họa)',
    `<div class="data-table-wrap">\
<table class="data-table">\
<thead>\
<tr>\
<th>Ví</th>\
<th>Số dư cache mẫu</th>\
<th>Sổ cái mẫu</th>\
<th>Chênh lệch</th>\
<th>Trạng thái</th>\
</tr>\
</thead>\
<tbody>\
${wallets.filter(w => w.active).map(w => `<tr>\
<td>\
<strong>\
${w.name}</strong></td><td>${w.currency === 'USD' ? w.balance + ' USD' : money(w.balance)}</td><td>${w.currency === 'USD' ? w.balance + ' USD' : money(w.balance)}</td><td>0 ${w.currency}\
</td>\
<td>\
<span class="badge">Mô phỏng khớp</span>\
</td>\
</tr>`).join('')}</tbody></table></div>`)}\
<div class="hint-box" style="margin-top:18px">\
<strong>Chưa chạy phép đối soát:</strong> khi triển khai, PHP phải tính lại số dư từ ledger entries của từng ví, so sánh với số dư cache và phát hiện sai khác. Không sử dụng bảng demo này làm bằng chứng kiểm toán.</div>`;

}
function notificationsPage() {
  return `${heading('notifications', btn('Cấu hình thông báo', 'notification-settings', 'outline', 'gear'))}${panel('Hộp thư thông báo',
    'Cảnh báo ngân sách, nhắc nợ và broadcast từ hệ thống',
    notifications.map(n => `<div class="notification-item">\
<span class="notification-icon">\
${n.type === 'danger' ? '!' : n.type === 'warn' ? '◷' : '✧'}\
</span>\
<div class="notification-copy">\
<strong>\
${n.title}\
</strong>\
<p>\
${n.content}</p><small>${n.time}\
</small>\
</div>\
<span class="badge ${n.type === 'danger' ? 'danger' : n.type === 'warn' ? 'warn' : 'blue'}">${n.type === 'danger' ? 'Khẩn' : n.type === 'warn' ? 'Cần chú ý' : 'Thông tin'}</span></div>`).join(''))}\
<div class="hint-box" style="margin-top:17px">Thông báo sử dụng dữ liệu mẫu. SRS yêu cầu tránh gửi trùng cảnh báo ngân sách và nhắc khoản vay trước hạn.</div>`;

}
function settingsPage() {
  return `${heading('settings')}\
<div class="info-grid">\
${panel('Hồ sơ cá nhân',
    'Thông tin đăng ký và ưu tiên hiển thị',
    `<div class="settings-row">\
<div>\
<strong>Họ và tên</strong>\
<p>Người dùng demo</p>\
</div>\
<button class="btn btn--outline btn-sm" data-action="profile-edit">Chỉnh sửa</button>\
</div>\
<div class="settings-row">\
<div>\
<strong>Email</strong>\
<p>demo@wealthflow.local</p>\
</div>\
<span class="badge neutral">Minh họa</span>\
</div>\
<div class="settings-row">\
<div>\
<strong>Tiền cơ sở</strong>\
<p>VND · Việt Nam đồng</p>\
</div>\
<button class="btn btn--outline btn-sm" data-action="currency-setting">Chi tiết</button>\
</div>\
<div class="settings-row">\
<div>\
<strong>Ngôn ngữ</strong>\
<p>Tiếng Việt (UTF-8) · cấu trúc sẵn sàng mở rộng i18n</p>\
</div>\
<span class="badge blue">vi-VN</span>\
</div>`)}${panel('Tài khoản & bảo mật',
      'Các chức năng sẽ triển khai ở pha backend',
      `<div class="settings-row">\
<div>\
<strong>Đổi mật khẩu</strong>\
<p>Xác nhận mật khẩu cũ, thu hồi phiên khác.</p>\
</div>\
<button class="btn btn--outline btn-sm" data-action="password">Thiết lập</button>\
</div>\
<div class="settings-row">\
<div>\
<strong>Phiên đăng nhập</strong>\
<p>JWT và Refresh Token HttpOnly sẽ triển khai theo SRS.</p>\
</div>\
<span class="badge warn">Chưa kết nối</span>\
</div>\
<div class="settings-row">\
<div>\
<strong>Yêu cầu xóa dữ liệu</strong>\
<p>Quyền quản lý dữ liệu cá nhân theo NFR07.</p>\
</div>\
<button class="btn btn--outline btn-sm" data-action="account-delete">Xem quy trình</button>\
</div>\
<div class="settings-row">\
<div>\
<strong>Thoát bản demo</strong>\
<p>Quay về trang giới thiệu, không có phiên thực để thu hồi.</p>\
</div>\
<a class="btn btn--outline btn-sm" href="index.html">Thoát</a>\
</div>`)}</div>`;

}
function adminOverviewPage() {
  return `${heading('admin-overview', btn('Soạn thông báo', 'broadcast-add'))}\
<div class="kpi-grid">\
${kpi('Tổng người dùng', '1.284', 'Dữ liệu thống kê giả lập', 'users')}${kpi('Tài khoản Active', '1.237', 'Không bao gồm tài khoản khóa', 'check', 'green')}${kpi('Đăng ký mới tháng', '86', 'Tháng 10/2026', 'chart', 'blue', '+9,4%')}${kpi('Tài khoản Locked', '14', 'Cần xử lý theo quy trình', 'lock', 'orange')}\
</div>\
<div class="panel-grid">\
${panel('Hoạt động tài khoản', 'Thống kê người dùng theo tháng', reportBarChart())}${panel('Phân bố tiền cơ sở',
    'Thống kê tổng hợp, không có số dư',
    `<div class="doughnut-wrap">\
<div class="donut" style="background:conic-gradient(#197c96 0 85%,#aac7e5 85% 96%,#e3bb83 96%)">\
<div class="donut-center">\
<strong>1.284</strong>\
<span>Người dùng</span>\
</div>\
</div>\
<div class="donut-legend">\
<span>\
<i style="background:#197c96">\
</i>VND<strong>85%</strong>\
</span>\
<span>\
<i style="background:#aac7e5">\
</i>USD<strong>11%</strong>\
</span>\
<span>\
<i style="background:#e3bb83">\
</i>Khác<strong>4%</strong>\
</span>\
</div>\
</div>`)}\
</div>\
<div class="hint-box">\
<strong>BR11 – bảo mật dữ liệu:</strong> trang Admin không được cung cấp số dư, tài sản, khoản nợ hoặc lịch sử giao dịch tài chính của bất kỳ người dùng nào. Các số trên màn hình chỉ là dữ liệu giả để minh họa bố cục thống kê vĩ mô.</div>`;

}
function adminUsersPage() {
  return `${heading('admin-users', btn('Xem nhật ký Admin', 'admin-logs', 'outline', 'file'))}${panel('Danh sách người dùng',
    'Theo dõi trạng thái, tìm kiếm và khóa/mở khóa khi có căn cứ',
    `<div class="data-table-wrap">\
<table class="data-table">\
<thead>\
<tr>\
<th>Tài khoản</th>\
<th>Ngày tạo</th>\
<th>Tiền cơ sở</th>\
<th>Trạng thái</th>\
<th>Thao tác</th>\
</tr>\
</thead>\
<tbody>\
${adminUsers.map(u => `<tr>\
<td>\
<div class="table-name">\
<span class="table-icon">\
${icon('user')}\
</span>\
<span>\
${u.name}<small>${u.email} · ${u.id}</small></span></div></td><td>${u.created}</td><td>${u.currency}\
</td>\
<td>\
<span class="badge ${u.status === 'Active' ? '' : 'danger'}">${u.status}\
</span>\
</td>\
<td>\
<button class="text-action" data-action="admin-user-${u.status === 'Active' ? 'lock' : 'unlock'}">${u.status === 'Active' ? 'Khóa tài khoản' : 'Mở khóa'}</button></td></tr>`).join('')}</tbody></table></div>`)}\
<div class="hint-box" style="margin-top:18px">Thao tác khóa thật phải có lý do, audit log và thu hồi phiên ngay lập tức. Không cho khóa Admin duy nhất còn lại. Các nút trên không thay đổi dữ liệu.</div>`;

}
function adminCategoriesPage() {
  return `${heading('admin-categories', btn('Thêm danh mục mẫu', 'admin-category-add'))}${panel('Bộ danh mục mặc định',
    'Sẽ được khởi tạo cho người dùng mới',
    `<div class="card-grid">\
${categories.map(c => `<div class="mini-panel">\
<div class="status-row">\
<h3>\
${c.name}\
</h3>\
<span class="badge ${c.type === 'Thu nhập' ? 'blue' : 'neutral'}">${c.type}\
</span>\
</div>\
<p>Danh mục gốc của hệ thống · dữ liệu mẫu</p>\
<div style="margin-top:15px">\
<button class="text-action" data-action="admin-category-edit">Điều chỉnh →</button>\
</div>\
</div>`).join('')}</div>`)}\
<div class="hint-box" style="margin-top:17px">Danh mục mặc định chỉ dùng làm mẫu khi tạo tài khoản mới. Không được tự ý làm thay đổi các danh mục hoặc giao dịch cá nhân đã tồn tại.</div>`;

}
function adminBroadcastPage() {
  return `${heading('admin-broadcasts', btn('Tạo broadcast', 'broadcast-add'))}${panel('Thông báo toàn hệ thống',
    'Nội dung gửi đến trung tâm thông báo của người dùng',
    `<div class="notification-item">\
<span class="notification-icon">✧</span>\
<div class="notification-copy">\
<strong>Chào mừng đến WealthFlow</strong>\
<p>Thông báo mẫu dành cho giao diện demo Construction.</p>\
<small>10/10/2026 · Thông tin</small>\
</div>\
<span class="badge blue">Minh họa</span>\
</div>\
<div class="notification-item">\
<span class="notification-icon">◷</span>\
<div class="notification-copy">\
<strong>Thông báo bảo trì dự kiến</strong>\
<p>Ví dụ loại cảnh báo vận hành có thể phát đến nhiều người dùng khi backend hoàn thiện.</p>\
<small>Bản nháp mẫu</small>\
</div>\
<span class="badge warn">Nháp</span>\
</div>`)}\
<div class="hint-box" style="margin-top:17px">Chỉ Admin được tạo broadcast. Cần kiểm tra tiêu đề, nội dung, mức ưu tiên và lưu dấu vết tác giả khi triển khai.</div>`;

}
function adminSettingsPage() {
  return `${heading('admin-settings', btn('Điều chỉnh tham số', 'admin-config'))}\
<div class="info-grid">\
${panel('Tham số vận hành',
    'Mặc định theo tài liệu SRS',
    `<div class="metric-list">\
<div class="metric-item">\
<span>Cảnh báo ngân sách</span>\
<strong>80%</strong>\
</div>\
<div class="metric-item">\
<span>Vượt ngân sách</span>\
<strong>100%</strong>\
</div>\
<div class="metric-item">\
<span>TTL access token tối đa</span>\
<strong>60 phút</strong>\
</div>\
<div class="metric-item">\
<span>Nhắc khoản vay</span>\
<strong>Trước hạn 3 ngày</strong>\
</div>\
<div class="metric-item">\
<span>Hạn ngạch tệp xuất</span>\
<strong>Chưa chốt ADR</strong>\
</div>\
</div>`)}${panel('Admin Audit Log',
      'Ghi nhận hoạt động quản trị (chưa kết nối)',
      `<div class="notification-item">\
<span class="notification-icon">◎</span>\
<div class="notification-copy">\
<strong>UI demo được khởi tạo</strong>\
<p>Đây không phải log máy chủ. Audit log phải do backend tạo.</p>\
<small>Phiên trình diễn</small>\
</div>\
</div>\
<div class="hint-box">Các hành động khóa tài khoản, mở khóa, cập nhật cấu hình và phát broadcast cần có nhật ký với thời gian và Admin thực hiện.</div>`)}</div>`;

}
const PAGES = {
  overview,
  transactions: transactionPage,
  wallets: walletPage,
  categories: categoryPage,
  budgets: budgetPage,
  recurring: recurringPage,
  loans: loanPage,
  gold: goldPage,
  goals: goalsPage,
  reports: reportPage,
  reconciliation: reconciliationPage,
  notifications: notificationsPage,
  settings: settingsPage,
  'admin-overview': adminOverviewPage,
  'admin-users': adminUsersPage,
  'admin-categories': adminCategoriesPage,
  'admin-broadcasts': adminBroadcastPage,
  'admin-settings': adminSettingsPage

};
const FIELDS = {
  'wallet-archive': {
    title: 'Quản lý & lưu trữ ví',
    desc: 'FR02.03 · Chỉ lưu trữ ví số dư bằng 0',
    fields: [],
    note: 'Khi có backend, chỉ cho lưu trữ ví 0 đồng, không có lịch định kỳ đang hoạt động. Nếu ví còn tiền phải chuyển hết trước. Ví lưu trữ vẫn có mặt trong lịch sử báo cáo.'

  },
  'budget-manage': {
    title: 'Chỉnh sửa / lưu trữ ngân sách',
    desc: 'FR05.01 · Quản lý hạn mức',
    fields: [
      [
        'category',
        'Danh mục',
        'select',
        [
          'Ăn uống',
          'Mua sắm',
          'Giải trí',
          'Di chuyển',
          'Hóa đơn'

        ]
      ],
      ['limit', 'Hạn mức mới (VND)', 'number'],
      ['status', 'Trạng thái', 'select', ['Hoạt động', 'Lưu trữ']]

    ]

  },
  'recurring-delete': {
    title: 'Xóa lịch định kỳ',
    desc: 'FR06.01 · Xóa lịch và giữ dấu vết kỳ cũ',
    fields: [],
    note: 'Xóa chỉ ngừng phát sinh kỳ tiếp theo; các giao dịch đã POSTED vẫn phải giữ nguyên để phục vụ kiểm toán.'

  },
  'goal-complete': {
    title: 'Hoàn thành mục tiêu',
    desc: 'FR07.01 · Đánh dấu đã hoàn thành',
    fields: [],
    note: 'Mục tiêu hoàn thành không phát sinh thêm tiền trong tài sản. Cần kiểm tra tiến độ và quyền sở hữu bằng backend.'

  },
  'goal-cancel': {
    title: 'Hủy mục tiêu tài chính',
    desc: 'FR07.01 · Hủy nhưng giữ lịch sử phân bổ',
    fields: [],
    note: 'Các khoản tiền phân bổ không tự tạo tài sản mới và lịch sử phải được giữ để tra soát.'

  },
  'gold-liquidate': {
    title: 'Thanh lý / xóa tài sản vàng',
    desc: 'FR08.01 · Ngừng theo dõi vàng tích lũy',
    fields: [],
    note: 'Thanh lý vàng bằng dòng tiền thật cần nghiệp vụ hạch toán; thao tác demo chỉ mô phỏng màn hình, không trừ vàng hay cộng ví.'

  },
  'rate-refresh': {
    title: 'Cập nhật tỷ giá',
    desc: 'FR04.01 · Dự kiến đồng bộ qua SYS01',
    fields: [],
    note: 'API tỷ giá ngoài và lịch chạy SYS01 chưa triển khai. Tỷ giá 26.000 VND/USD đang là con số minh họa, không dùng để quy đổi thật.'

  },
  'tx-add': {
    title: 'Ghi nhận giao dịch',
    desc: 'UC03 · Tạo giao dịch Thu / Chi / Bản nháp',
    fields: [
      ['type', 'Loại giao dịch', 'select', ['Chi tiêu', 'Thu nhập']],
      ['amount', 'Số tiền (VND)', 'number'],
      [
        'category',
        'Danh mục',
        'select',
        [
          'Ăn uống',
          'Di chuyển',
          'Mua sắm',
          'Giải trí',
          'Hóa đơn',
          'Lương thưởng',
          'Thu nhập khác'

        ]

      ],
      ['wallet', 'Ví nguồn / ví nhận', 'select', ['Vietcombank', 'Tiền mặt', 'MoMo', 'Tiết kiệm dài hạn']],
      ['date', 'Ngày giao dịch', 'date'],
      ['note', 'Ghi chú', 'text']

    ]

  },
  transfer: {
    title: 'Chuyển giữa các ví',
    desc: 'UC03 · Chuyển nội bộ không tính thu / chi',
    fields: [
      ['from', 'Từ ví', 'select', ['Vietcombank', 'Tiền mặt', 'MoMo', 'Quỹ ngoại tệ']],
      ['to', 'Đến ví', 'select', ['MoMo', 'Vietcombank', 'Tiền mặt', 'Quỹ ngoại tệ']],
      ['amount', 'Số tiền nguồn', 'number'],
      ['receive', 'Số tiền đích nếu khác tiền tệ', 'number'],
      ['note', 'Ghi chú', 'text']

    ]

  },
  'wallet-add': {
    title: 'Tạo ví / tài khoản',
    desc: 'UC02 · Mở ví, ghi nhận số dư đầu kỳ',
    fields: [
      ['name', 'Tên ví', 'text'],
      [
        'type',
        'Loại ví',
        'select',
        [
          'Tiền mặt',
          'Ngân hàng',
          'Ví điện tử',
          'Tiết kiệm',
          'Thẻ tín dụng (ví nợ)'

        ]
      ],
      ['currency', 'Tiền tệ', 'select', ['VND', 'USD', 'EUR']],
      ['balance', 'Số dư đầu kỳ', 'number']

    ]

  },
  'category-add': {
    title: 'Tạo danh mục cá nhân',
    desc: 'FR03.05 · Danh mục thu / chi',
    fields: [['name', 'Tên danh mục', 'text'], ['type', 'Loại danh mục', 'select', ['Chi tiêu', 'Thu nhập']]]

  },
  'category-edit': {
    title: 'Chỉnh sửa danh mục',
    desc: 'Giao diện thao tác theo FR03.05',
    fields: [['name', 'Tên danh mục mới', 'text'], ['state', 'Trạng thái', 'select', ['Đang hoạt động', 'Lưu trữ']]]

  },
  'budget-add': {
    title: 'Thiết lập ngân sách',
    desc: 'UC05 · Hạn mức theo danh mục và tháng',
    fields: [
      [
        'category',
        'Danh mục',
        'select',
        [
          'Ăn uống',
          'Mua sắm',
          'Giải trí',
          'Di chuyển',
          'Hóa đơn'

        ]
      ],
      ['month', 'Tháng áp dụng', 'month'],
      ['amount', 'Hạn mức (VND)', 'number']

    ]

  },
  'recurring-add': {
    title: 'Lập lịch giao dịch định kỳ',
    desc: 'UC04 · Nhắc bản nháp hoặc tự động ghi sổ',
    fields: [
      ['name', 'Tên khoản định kỳ', 'text'],
      ['amount', 'Số tiền', 'number'],
      ['category', 'Danh mục', 'select', ['Hóa đơn', 'Nhà ở', 'Thu nhập khác']],
      ['wallet', 'Ví liên quan', 'select', ['Vietcombank', 'MoMo', 'Tiền mặt']],
      ['day', 'Ngày trong tháng (1–31)', 'number'],
      ['mode', 'Chế độ', 'select', ['Nhắc tạo bản nháp', 'Tự động ghi sổ']]

    ]

  },
  'loan-add': {
    title: 'Thêm khoản vay / nợ',
    desc: 'UC06 · Vay và cho vay vi mô',
    fields: [
      ['name', 'Tên khoản vay', 'text'],
      ['kind', 'Loại', 'select', ['Đi vay', 'Cho vay']],
      ['partner', 'Đối tác', 'text'],
      ['principal', 'Số tiền gốc', 'number'],
      ['interest', 'Lãi suất / phí', 'text'],
      ['date', 'Ngày đáo hạn', 'date']

    ]

  },
  'loan-pay': {
    title: 'Ghi nhận kỳ trả nợ',
    desc: 'UC07 · Bắt buộc tách nợ gốc và lãi / phí',
    fields: [
      ['loan', 'Khoản vay', 'select', ['Vay mua laptop', 'Khoản vay học tập']],
      ['principal', 'Số tiền gốc trả', 'number'],
      ['interest', 'Tiền lãi / phí', 'number'],
      ['wallet', 'Ví thanh toán', 'select', ['Vietcombank', 'Tiền mặt', 'MoMo']],
      ['date', 'Ngày thanh toán', 'date']

    ]

  },
  'gold-add': {
    title: 'Ghi nhận tài sản vàng',
    desc: 'UC08 · Chỉ gồm vàng nhẫn 9999 và SJC',
    fields: [
      ['type', 'Loại vàng', 'select', ['Nhẫn tròn trơn 9999', 'Vàng miếng SJC']],
      ['weight', 'Khối lượng (chỉ)', 'number'],
      ['unit', 'Giá trị mỗi chỉ (VND)', 'number'],
      ['date', 'Ngày định giá', 'date']

    ]

  },
  'gold-revalue': {
    title: 'Cập nhật định giá vàng',
    desc: 'FR08.01 · Lưu ngày định giá',
    fields: [
      ['type', 'Tài sản', 'select', ['Nhẫn tròn trơn 9999', 'Vàng miếng SJC']],
      ['unit', 'Thị giá hiện tại / chỉ', 'number'],
      ['date', 'Ngày định giá', 'date']

    ]

  },
  'goal-add': {
    title: 'Tạo mục tiêu tài chính',
    desc: 'UC09 · Tiến độ và kế hoạch tích lũy',
    fields: [
      ['name', 'Tên mục tiêu', 'text'],
      ['target', 'Giá trị mục tiêu (VND)', 'number'],
      ['deadline', 'Hạn hoàn thành', 'date']

    ]

  },
  'goal-allocate': {
    title: 'Phân bổ vào mục tiêu',
    desc: 'BR10 · Không làm tăng tổng tài sản',
    fields: [
      ['name', 'Mục tiêu', 'select', ['Quỹ khẩn cấp', 'Laptop mới', 'Du lịch cuối năm']],
      ['amount', 'Số tiền phân bổ', 'number'],
      ['note', 'Ghi chú', 'text']

    ]

  },
  'broadcast-add': {
    title: 'Soạn thông báo hệ thống',
    desc: 'UC13 · Admin broadcast (chỉ mô phỏng UI)',
    fields: [
      ['title', 'Tiêu đề', 'text'],
      ['level', 'Mức ưu tiên', 'select', ['Thông tin', 'Cảnh báo', 'Khẩn cấp']],
      ['body', 'Nội dung', 'textarea']

    ]

  },
  'admin-category-add': {
    title: 'Danh mục hệ thống mặc định',
    desc: 'FR13.01 · Dành cho người dùng mới',
    fields: [['name', 'Tên danh mục', 'text'], ['type', 'Loại', 'select', ['Chi tiêu', 'Thu nhập']]]

  },
  'admin-category-edit': {
    title: 'Điều chỉnh danh mục mặc định',
    desc: 'FR13.01 · Không ảnh hưởng giao dịch cũ',
    fields: [['name', 'Tên danh mục', 'text']]

  },
  'admin-config': {
    title: 'Tham số hệ thống',
    desc: 'FR13.02 · Cần quyền Admin thật khi triển khai',
    fields: [['budget', 'Ngưỡng cảnh báo (%)', 'number'], ['upload', 'Giới hạn dung lượng xuất (MB)', 'number']]

  },
  'profile-edit': { title: 'Cập nhật hồ sơ', desc: 'UC01 · Hồ sơ người dùng', fields: [['name', 'Tên hiển thị', 'text']] },
  'password': {
    title: 'Đổi mật khẩu',
    desc: 'FR01.04 · Chỉ hiển thị form, không nhận/gửi mật khẩu thực',
    fields: [],
    note: 'Mẫu thao tác đổi mật khẩu không có input trong phiên bản UI vì backend chưa có xác thực an toàn. Chỉ bổ sung form thật sau khi hoàn thiện HTTPS, hash mật khẩu, quản lý JWT và thu hồi phiên.'

  },
  'currency-setting': {
    title: 'Tiền cơ sở của tài khoản',
    desc: 'FR04 · Cấu hình định dạng tiền',
    fields: [['currency', 'Tiền cơ sở', 'select', ['VND', 'USD', 'EUR']]]

  },
  'notification-settings': {
    title: 'Tùy chọn nhắc nhở',
    desc: 'FR05 · FR09 · FR13',
    fields: [
      ['budget', 'Cảnh báo ngân sách', 'select', ['Bật', 'Tắt']],
      ['loan', 'Nhắc khoản vay', 'select', ['Bật', 'Tắt']]
    ]

  },
  'account-delete': {
    title: 'Quyền quản lý dữ liệu cá nhân',
    desc: 'NFR07 · Quy trình yêu cầu xóa',
    fields: [],
    note: 'Khi có backend, cần xác thực lại chủ sở hữu, xác nhận yêu cầu và thiết kế chính sách lưu trữ phù hợp pháp luật. Bản demo không thao tác dữ liệu tài khoản.'

  },
  'admin-user-lock': {
    title: 'Khóa tài khoản người dùng',
    desc: 'UC12 · Lý do bắt buộc; cần revoke token khi thực thi',
    fields: [['reason', 'Lý do khóa', 'textarea']]

  },
  'admin-user-unlock': {
    title: 'Mở khóa tài khoản',
    desc: 'UC12 · Có audit log khi thực thi',
    fields: [['reason', 'Ghi chú xử lý', 'textarea']]

  },
  'recurring-toggle': {
    title: 'Thay đổi trạng thái định kỳ',
    desc: 'UC04 · Tạm dừng / kích hoạt lịch',
    fields: [],
    note: 'Đây là hành động hiển thị demo. Dữ liệu định kỳ không được cập nhật cho đến khi hoàn thiện API và SYS02.'

  },
  'loan-view': {
    title: 'Lịch thanh toán',
    desc: 'UC06 · UC07 · Kỳ hạn gốc, lãi / phí',
    fields: [],
    note: 'Bảng kỳ thanh toán chi tiết sẽ được lấy từ API sau khi có schema LoanPayment và tính toán dư nợ trong backend.'

  },
  'admin-logs': {
    title: 'Admin Audit Log',
    desc: 'FR13 · FR14',
    fields: [],
    note: 'Nhật ký phải được backend ghi nhận bất biến và tuyệt đối không chứa số dư / giao dịch cá nhân. Hiện chưa có dữ liệu audit thật.'

  },


};
function toast(message) {
  const stack = document.querySelector('#toast-stack');
  const item = document.createElement('div');
  item.className = 'toast';
  const glyph = document.createElement('span');
  glyph.className = 'toast-symbol';
  glyph.textContent = 'ⓘ';
  const txt = document.createElement('span');
  txt.textContent = message;
  item.append(glyph, txt);
  stack.append(item);
  setTimeout(() => item.remove(), 4700);

}
function fieldHTML([name, label, type, options]) {
  const required = !['note', 'receive', 'interest'].includes(name);
  const constraint = required ? 'required' : '';
  let control;
  if (type === 'select') control = `<select id="demo-${safe(name)}" name="${safe(name)}" ${constraint}>${options.map(o => `<option>${safe(o)}</option>`).join('')}</select>`; else if (type === 'textarea') control = `<textarea id="demo-${safe(name)}" name="${safe(name)}" rows="3" ${constraint} placeholder="Nhập ${safe(label.toLowerCase())}..."></textarea>`; else control = `<input id="demo-${safe(name)}" name="${safe(name)}" type="${type}" ${type === 'number' ? 'min="0" step="any"' : ''} ${constraint} placeholder="${type === 'number' ? '0' : safe(label)}">`;
  return `<div class="field">\
<label for="demo-${safe(name)}">${safe(label)}${required ? ' *' : ''}</label>${control}</div>`;

}
function openAction(action) {
  const def = FIELDS[action];
  if (!def) {
    toast('Tính năng này sẽ được nối với API ở bước Construction tiếp theo.');
    return;

  }
  document.querySelector('#modal-overline').textContent = def.desc;
  document.querySelector('#modal-title').textContent = def.title;
  const content = document.querySelector('#modal-content');
  content.innerHTML = `<p class="form-hint">\
${safe(def.note || 'Form mô phỏng đúng trường nghiệp vụ. Chưa lưu hoặc gửi dữ liệu lên máy chủ MySQL/PHP.')}</p>${def.fields.length ? `<form id="demo-form">\
<div class="form-grid">\
${def.fields.map(fieldHTML).join('')}\
</div>\
<div class="modal-footer">\
<button type="button" class="btn btn--outline btn-sm" data-close-modal>Hủy</button>\
<button type="submit" class="btn btn--primary btn-sm">Xem trước thao tác</button>\
</div>\
</form>` : `<div class="modal-footer">\
<button type="button" class="btn btn--primary btn-sm" data-close-modal>Đã hiểu</button>\
</div>`}`;
  dialog.showModal();

}
function showDetails(id) {
  const t = transactions.find(row => row.id === id);
  if (!t) return;
  document.querySelector('#modal-overline').textContent = 'FR03 · FR04 · Chi tiết giao dịch';
  document.querySelector('#modal-title').textContent = t.name;
  document.querySelector('#modal-content').innerHTML = `<dl class="detail-grid">\
<div>\
<dt>Mã giao dịch</dt>\
<dd>\
${safe(t.id)}\
</dd>\
</div>\
<div>\
<dt>Trạng thái</dt>\
<dd>\
${t.status === 'DRAFT' ? 'Bản nháp (chưa ghi sổ)' : 'Posted (đã ghi sổ)'}\
</dd>\
</div>\
<div>\
<dt>Danh mục</dt>\
<dd>\
${safe(t.category)}\
</dd>\
</div>\
<div>\
<dt>Ngày giao dịch</dt>\
<dd>\
${safe(t.date)}\
</dd>\
</div>\
<div>\
<dt>Ví liên quan</dt>\
<dd>\
${safe(t.wallet)}\
</dd>\
</div>\
<div>\
<dt>Số tiền</dt>\
<dd>\
${money(Math.abs(t.amount))}\
</dd>\
</div>\
<div>\
<dt>Tiền tệ</dt>\
<dd>\
${safe(t.currency)}\
</dd>\
</div>\
<div>\
<dt>Tỷ giá snapshot</dt>\
<dd>\
${safe(t.rate)}\
</dd>\
</div>\
</dl>\
<hr class="modal-rule">\
<p class="description">\
${safe(t.details)}\
</p>\
<div class="hint-box">\
<strong>Bút toán Nợ/Có:</strong> dữ liệu ghi sổ thực tế chưa tồn tại. Màn hình chi tiết đầy đủ sẽ tải ledger entries và liên kết reversal/original transaction từ API.</div>\
<div class="modal-footer">\
<button class="btn btn--outline btn-sm" data-close-modal>Đóng</button>\
${t.status === 'POSTED' ? `<button class="btn btn--primary btn-sm" data-action="reversal">Xem quy trình đảo giao dịch</button>` : ''}</div>`;
  dialog.showModal();

}
function exportDemoCSV() {
  const rows = [
    [
      'Mã giao dịch',
      'Tên giao dịch',
      'Danh mục',
      'Ví',
      'Ngày',
      'Số tiền VND',
      'Trạng thái'

    ],
    ...transactions.map(t => [
      t.id,
      t.name,
      t.category,
      t.wallet,
      t.date,
      String(t.amount),
      t.status

    ])

  ];
  const neutralize = v => {
    const text = String(v).replace(/^[\s\u0000-\u001f]+/, '');
    return /^[=+\-@]/.test(text) ? "'" + String(v) : String(v);

  };
  const csv = rows.map(row => row.map(col => '"' + neutralize(col).replaceAll('"', '""') + '"').join(',')).join('\r\n');
  const blob = new Blob(['\ufeff' + csv], { type: 'text/csv;charset=utf-8;' });
  const url = URL.createObjectURL(blob);
  const anchor = document.createElement('a');
  anchor.href = url;
  anchor.download = 'wealthflow_giao_dich_DEMO.csv';
  anchor.click();
  setTimeout(() => URL.revokeObjectURL(url), 2000);
  toast('Đã tải CSV từ dữ liệu mẫu, không phải dữ liệu sổ cái thật.');

}
function applyTransactionSearch() {
  const filtered = transactions.filter(t => (state.txFilter === 'all' || t.type === state.txFilter) && [t.name, t.id, t.category, t.wallet].join(' ').toLocaleLowerCase('vi').includes(state.search.toLocaleLowerCase('vi')));
  const target = document.querySelector('#transaction-table');
  if (target) target.innerHTML = txTable(filtered);

}
function handleAction(action) {
  if (action === 'switch-role') {
    state.admin = !state.admin;
    navigate(state.admin ? 'admin-overview' : 'overview');
    toast(state.admin ? 'Đang xem giao diện Admin demo – không cấp quyền máy chủ.' : 'Đã quay về giao diện người dùng demo.');
    return;

  }
  if (action === 'wallet-toggle') {
    state.onlyActiveWallets = !state.onlyActiveWallets;
    navigate('wallets');
    return;

  }
  if (action === 'export') {
    exportDemoCSV();
    return;

  }
  if (action === 'reconcile') {
    toast('Đối soát backend chưa triển khai; bảng đang hiển thị số liệu minh họa.');
    return;

  }
  if (action === 'reversal') {
    dialog.close();
    toast('Đảo bút toán thật cần backend tạo reversal + transaction thay thế trong một DB transaction.');
    return;

  }
  openAction(action);

}
document.addEventListener('click', event => {
  const page = event.target.closest('[data-page]');
  if (page) {
    navigate(page.dataset.page);
    return;

  }
  const detail = event.target.closest('[data-detail]');
  if (detail) {
    showDetails(detail.dataset.detail);
    return;

  }
  const filter = event.target.closest('[data-tx-filter]');
  if (filter) {
    state.txFilter = filter.dataset.txFilter;
    navigate('transactions');
    return;

  }
  const action = event.target.closest('[data-action]');
  if (action) {
    handleAction(action.dataset.action);
    return;

  }
  if (event.target.closest('[data-close-modal]')) dialog.close();

});
document.addEventListener('input', event => {
  if (event.target.id === 'transaction-search') {
    state.search = event.target.value;
    applyTransactionSearch();

  }

});
document.addEventListener('submit', event => {
  if (event.target.id !== 'demo-form') return;
  event.preventDefault();
  dialog.close();
  toast('Đã xem trước form demo. Chưa lưu, chưa gửi API và chưa làm thay đổi dữ liệu.');

});
dialog.addEventListener('click', event => {
  if (event.target === dialog) dialog.close();

});
document.querySelector('#modal-close').addEventListener('click', () => dialog.close());
document.querySelector('#sidebar-toggle').addEventListener('click', () => {
  const open = !sidebar.classList.contains('is-open');
  sidebar.classList.toggle('is-open', open);
  document.querySelector('#mobile-overlay').classList.toggle('is-open', open);
  document.querySelector('#sidebar-toggle').setAttribute('aria-expanded', String(open));

});
document.querySelector('#mobile-overlay').addEventListener('click', closeSidebar);
document.querySelector('#top-notifications').addEventListener('click', () => {
  if (state.admin) {
    toast('Admin không truy cập thông báo tài chính cá nhân; đây chỉ là trang demo.');
    return;

  }
  navigate('notifications');

});
document.querySelector('#user-menu').addEventListener('click', () => navigate(state.admin ? 'admin-settings' : 'settings'));
const globalSearch = document.querySelector('#global-search'); globalSearch.addEventListener('keydown', event => {
  if (event.key === 'Enter') {
    event.preventDefault();
    if (state.admin) {
      toast('Tìm kiếm giao dịch không khả dụng ở chế độ Admin.');
      return;

    }
    state.search = globalSearch.value;
    state.txFilter = 'all';
    navigate('transactions');

  }

});
document.querySelector('#top-search-icon').innerHTML = icon('search'); document.querySelector('#bell-icon').innerHTML = icon('bell');
window.addEventListener('hashchange', () => {
  const page = decodeURIComponent(location.hash.slice(1));
  if (META[page]) {
    const targetAdmin = page.startsWith('admin-');
    if (targetAdmin !== state.admin) state.admin = targetAdmin;
    navigate(page);

  }

});
const initial = decodeURIComponent(location.hash.slice(1)); if (initial.startsWith('admin-')) state.admin = true; navigate(META[initial] ? initial : 'overview');
