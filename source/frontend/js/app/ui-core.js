/** Shared UI primitives, labels, navigation and formatting. */
const paths = {
  grid: '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
  wallet: '<path d="M20 7V5a2 2 0 0 0-2-2H5a3 3 0 0 0-3 3v12a3 3 0 0 0 3 3h15a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2H5a3 3 0 0 1-3-3"/><path d="M16 14h6"/>',
  cash: '<rect x="3" y="6" width="18" height="12" rx="2"/><path d="M7 10h.01M17 14h.01"/><circle cx="12" cy="12" r="2.5"/>',
  bank: '<path d="m3 10 9-6 9 6M5 10h14M6 10v7M10 10v7M14 10v7M18 10v7M4 20h16"/>',
  savings: '<path d="M5 11a7 6 0 0 1 13-2h2a1 1 0 0 1 1 1v4a1 1 0 0 1-1 1h-2.2a7 6 0 0 1-2.8 2.3V20M8 17.3V20M3 10v3"/><path d="M9 8h4M16 9h.01"/>',
  card: '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18M7 15h4"/>',
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
  clock: '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
  trash: '<path d="M3 6h18M8 6V4h8v2M19 6l-1 15H6L5 6M10 11v6M14 11v6"/>'

};
export function icon(key) {
  return `<svg class="nav-icon" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">\
${paths[key] || paths.grid}</svg>`;

}
export function safe(value) {
  return String(value).replace(/[&<>"']/g, x => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#39;'

  }[x]));

}
export const money = value => new Intl.NumberFormat('vi-VN', { maximumFractionDigits: 0 }).format(value) + ' đ';
export const shortMoney = value => Math.abs(value) >= 1e6 ? (value / 1e6).toLocaleString('vi-VN', { maximumFractionDigits: 2 }) + ' triệu' : money(value);
export const percent = (a, b) => b > 0 ? Math.round(a / b * 100) : 0;
export const USER_NAV = [
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
export const ADMIN_NAV = [
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
export const META = {
  overview: ['Tổng quan', 'Bức tranh tài chính trong một màn hình'],
  transactions: ['Giao dịch', 'Quản lý thu chi, chuyển ví và lịch sử bút toán'],
  wallets: ['Ví & tài khoản', 'Mọi nguồn tiền của bạn tại một nơi'],
  categories: ['Danh mục thu chi', 'Tổ chức thu nhập và chi tiêu có hệ thống'],
  budgets: ['Ngân sách', 'Theo dõi hạn mức và nhận diện rủi ro bội chi'],
  recurring: ['Giao dịch định kỳ', 'Không bỏ lỡ khoản thanh toán hoặc thu nhập thường xuyên'],
  loans: ['Khoản vay & nợ', 'Theo dõi gốc, lãi/phí và lịch trả nợ'],
  gold: ['Tài sản vàng', 'Đánh giá tài sản tích lũy ngoài ví'],
  goals: ['Mục tiêu tài chính', 'Chuyển kế hoạch tích lũy thành hành động'],
  reports: ['Báo cáo tài chính', 'Hiểu dòng tiền và tỷ lệ tiết kiệm'],
  reconciliation: ['Đối soát & xuất CSV', 'Kiểm tra tính nhất quán và lưu trữ báo cáo'],
  notifications: ['Trung tâm thông báo', 'Theo dõi các cảnh báo và nhắc nhở quan trọng'],
  settings: ['Cài đặt cá nhân', 'Hồ sơ, tiền cơ sở, bảo mật phiên đăng nhập'],
  'admin-overview': ['Tổng quan hệ thống', 'Thống kê vĩ mô, không bao gồm dữ liệu tài chính cá nhân'],
  'admin-users': ['Quản lý người dùng', 'Trạng thái tài khoản và quyền truy cập'],
  'admin-categories': ['Danh mục mặc định', 'Thiết lập danh mục mẫu cho người dùng mới'],
  'admin-broadcasts': ['Thông báo hệ thống', 'Soạn và gửi broadcast announcement'],
  'admin-settings': ['Cấu hình hệ thống', 'Tham số vận hành và nhật ký quản trị']

};
