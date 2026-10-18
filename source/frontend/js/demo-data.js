/**
 * Dữ liệu hoàn toàn giả lập phục vụ demo giao diện Construction.
 * Không dùng để tính số dư thật, ghi sổ hay ra quyết định tài chính.
 * Dữ liệu minh họa cho giao diện WealthFlow.
 */
export const wallets = [
  {
    id: 'w1',
    name: 'Vietcombank',
    type: 'Ngân hàng',
    number: '•••• 4829',
    currency: 'VND',
    balance: 18400000,
    active: true
  },
  {
    id: 'w2',
    name: 'Tiền mặt',
    type: 'Tiền mặt',
    number: 'Ví cá nhân',
    currency: 'VND',
    balance: 2650000,
    active: true
  },
  {
    id: 'w3',
    name: 'MoMo',
    type: 'Ví điện tử',
    number: '•••• 0978',
    currency: 'VND',
    balance: 1600000,
    active: true
  },
  {
    id: 'w4',
    name: 'Tiết kiệm dài hạn',
    type: 'Tiết kiệm',
    number: 'Kỳ hạn 12 tháng',
    currency: 'VND',
    balance: 65000000,
    active: true
  },
  {
    id: 'w5',
    name: 'Quỹ ngoại tệ',
    type: 'Ngân hàng',
    number: 'Tài khoản USD',
    currency: 'USD',
    balance: 150,
    baseBalance: 3900000,
    active: true
  },
  {
    id: 'w6',
    name: 'Ví du lịch (đã lưu trữ)',
    type: 'Tiền mặt',
    number: 'Lịch sử',
    currency: 'VND',
    balance: 0,
    active: false
  },

];
export const transactions = [
  {
    id: 'TX-0063',
    name: 'Mua thực phẩm',
    category: 'Ăn uống',
    wallet: 'Vietcombank',
    date: '13/10/2026',
    amount: -185000,
    type: 'expense',
    status: 'POSTED',
    icon: '◎',
    currency: 'VND',
    rate: '1',
    details: 'Giao dịch chi tiêu; dòng Nợ chi phí / Có ví (mô phỏng).'
  },
  {
    id: 'TX-0062',
    name: 'Lương tháng 10',
    category: 'Lương thưởng',
    wallet: 'Vietcombank',
    date: '12/10/2026',
    amount: 18500000,
    type: 'income',
    status: 'POSTED',
    icon: '↗',
    currency: 'VND',
    rate: '1',
    details: 'Giao dịch thu nhập; dòng Nợ ví / Có thu nhập (mô phỏng).'
  },
  {
    id: 'TX-0061',
    name: 'Chuyển sang MoMo',
    category: 'Chuyển nội bộ',
    wallet: 'Vietcombank → MoMo',
    date: '11/10/2026',
    amount: -500000,
    type: 'transfer',
    status: 'POSTED',
    icon: '⇄',
    currency: 'VND',
    rate: '1',
    details: 'Chuyển giữa hai ví; không ghi nhận doanh thu hoặc chi phí.'
  },
  {
    id: 'TX-0060',
    name: 'Internet hàng tháng',
    category: 'Hóa đơn',
    wallet: 'Vietcombank',
    date: '10/10/2026',
    amount: -250000,
    type: 'expense',
    status: 'POSTED',
    icon: '▤',
    currency: 'VND',
    rate: '1',
    details: 'Khoản chi cho hóa đơn internet.'
  },
  {
    id: 'TX-0059',
    name: 'Cà phê cuối tuần',
    category: 'Ăn uống',
    wallet: 'Tiền mặt',
    date: '09/10/2026',
    amount: -65000,
    type: 'expense',
    status: 'POSTED',
    icon: '☕',
    currency: 'VND',
    rate: '1',
    details: 'Chi tiêu ăn uống.'
  },
  {
    id: 'TX-0058',
    name: 'Dự kiến mua đồ dùng',
    category: 'Mua sắm',
    wallet: 'MoMo',
    date: '15/10/2026',
    amount: -280000,
    type: 'expense',
    status: 'DRAFT',
    icon: '◈',
    currency: 'VND',
    rate: '1',
    details: 'Bản nháp chưa ghi sổ và chưa ảnh hưởng số dư.'
  },
  {
    id: 'TX-0057',
    name: 'Thu nhập freelance',
    category: 'Thu nhập khác',
    wallet: 'MoMo',
    date: '08/10/2026',
    amount: 1650000,
    type: 'income',
    status: 'POSTED',
    icon: '↗',
    currency: 'VND',
    rate: '1',
    details: 'Thu nhập công việc tự do.'
  },
  {
    id: 'TX-0056',
    name: 'Đi lại',
    category: 'Di chuyển',
    wallet: 'Tiền mặt',
    date: '07/10/2026',
    amount: -98000,
    type: 'expense',
    status: 'POSTED',
    icon: '↗',
    currency: 'VND',
    rate: '1',
    details: 'Chi phí di chuyển.'
  },

];
export const budgets = [
  {
    id: 'b1',
    name: 'Ăn uống',
    spent: 1870000,
    limit: 2500000,
    icon: '◷'
  },
  {
    id: 'b2',
    name: 'Mua sắm',
    spent: 1560000,
    limit: 1800000,
    icon: '▦'
  },
  {
    id: 'b3',
    name: 'Giải trí',
    spent: 1040000,
    limit: 1000000,
    icon: '♧'
  },
  {
    id: 'b4',
    name: 'Di chuyển',
    spent: 610000,
    limit: 1200000,
    icon: '↗'
  },
  {
    id: 'b5',
    name: 'Hóa đơn',
    spent: 690000,
    limit: 1200000,
    icon: '▤'
  },

];
export const recurring = [
  {
    id: 'r1',
    name: 'Internet tại nhà',
    category: 'Hóa đơn',
    date: '20/10/2026',
    amount: 250000,
    wallet: 'Vietcombank',
    mode: 'Nhắc tạo bản nháp',
    active: true
  },
  {
    id: 'r2',
    name: 'Phí điện thoại',
    category: 'Hóa đơn',
    date: '25/10/2026',
    amount: 119000,
    wallet: 'MoMo',
    mode: 'Tự động ghi sổ',
    active: true
  },
  {
    id: 'r3',
    name: 'Tiền phòng',
    category: 'Nhà ở',
    date: '01/11/2026',
    amount: 3000000,
    wallet: 'Vietcombank',
    mode: 'Nhắc tạo bản nháp',
    active: true
  },
  {
    id: 'r4',
    name: 'Đăng ký ứng dụng',
    category: 'Dịch vụ',
    date: '05/11/2026',
    amount: 79000,
    wallet: 'MoMo',
    mode: 'Nhắc tạo bản nháp',
    active: false
  },

];
export const goals = [
  {
    id: 'g1',
    name: 'Quỹ khẩn cấp',
    icon: '✦',
    current: 15000000,
    target: 20000000,
    deadline: '30/06/2027',
    monthly: 625000
  },
  {
    id: 'g2',
    name: 'Laptop mới',
    icon: '▣',
    current: 12000000,
    target: 25000000,
    deadline: '31/08/2027',
    monthly: 1300000
  },
  {
    id: 'g3',
    name: 'Du lịch cuối năm',
    icon: '◇',
    current: 5400000,
    target: 10000000,
    deadline: '30/12/2026',
    monthly: 2300000
  },

];
export const loans = [
  {
    id: 'l1',
    name: 'Vay mua laptop',
    partner: 'Cửa hàng ABC',
    kind: 'Đi vay',
    principal: 20000000,
    remaining: 12000000,
    due: '20/10/2026',
    interest: '7,5%/năm',
    paid: 8000000
  },
  {
    id: 'l2',
    name: 'Khoản vay học tập',
    partner: 'Người thân',
    kind: 'Đi vay',
    principal: 5000000,
    remaining: 3100000,
    due: '30/11/2026',
    interest: '0%',
    paid: 1900000
  },
  {
    id: 'l3',
    name: 'Cho bạn vay',
    partner: 'Bạn A',
    kind: 'Cho vay',
    principal: 2000000,
    remaining: 1200000,
    due: '15/11/2026',
    interest: '0%',
    paid: 800000
  },

];
export const gold = [
  {
    id: 'a1',
    name: 'Vàng nhẫn tròn trơn 9999',
    weight: '3 chỉ',
    price: 9000000,
    value: 27000000,
    date: '12/10/2026'
  },
  {
    id: 'a2',
    name: 'Vàng miếng SJC',
    weight: '2 chỉ',
    price: 12500000,
    value: 25000000,
    date: '12/10/2026'
  },

];
export const notifications = [
  {
    id: 'n1',
    title: 'Ngân sách Giải trí đã vượt ngưỡng',
    content: 'Mức sử dụng đang ở 104%. Hãy xem lại các giao dịch trong tháng.',
    time: 'Hôm nay · 09:18',
    type: 'danger'
  },
  {
    id: 'n2',
    title: 'Khoản vay mua laptop sắp đến hạn',
    content: 'Lịch thanh toán tiếp theo vào ngày 20/10/2026.',
    time: 'Hôm qua · 08:00',
    type: 'warn'
  },
  {
    id: 'n3',
    title: 'Ngân sách Mua sắm đạt 87%',
    content: 'Bạn đã đến ngưỡng cảnh báo 80% hạn mức tháng.',
    time: '11/10/2026',
    type: 'warn'
  },
  {
    id: 'n4',
    title: 'Thông báo từ WealthFlow',
    content: 'Chào mừng bạn đến với bản demo hệ thống quản lý tài chính cá nhân.',
    time: '10/10/2026',
    type: 'info'
  },

];
export const categories = [
  { name: 'Ăn uống', type: 'Chi tiêu' },
  { name: 'Mua sắm', type: 'Chi tiêu' },
  { name: 'Giải trí', type: 'Chi tiêu' },
  { name: 'Di chuyển', type: 'Chi tiêu' },
  { name: 'Hóa đơn', type: 'Chi tiêu' },
  { name: 'Nhà ở', type: 'Chi tiêu' },
  { name: 'Lương thưởng', type: 'Thu nhập' },
  { name: 'Thu nhập khác', type: 'Thu nhập' }
];
export const adminUsers = [
  {
    id: 'USR-0108',
    name: 'Nguyễn Minh Anh',
    email: 'minhanh@example.com',
    created: '12/10/2026',
    status: 'Active',
    currency: 'VND'
  },
  {
    id: 'USR-0107',
    name: 'Trần Khánh Linh',
    email: 'khanhlinh@example.com',
    created: '11/10/2026',
    status: 'Active',
    currency: 'VND'
  },
  {
    id: 'USR-0106',
    name: 'Phạm Anh Tuấn',
    email: 'anhtuan@example.com',
    created: '10/10/2026',
    status: 'Locked',
    currency: 'USD'
  },
  {
    id: 'USR-0105',
    name: 'Lê Hải Nam',
    email: 'hainam@example.com',
    created: '08/10/2026',
    status: 'Active',
    currency: 'VND'
  },

];
export const trend = [
  76,
  81,
  79,
  92,
  101,
  113,
  128.45
];
export const incomeTrend = [
  13.2,
  15.3,
  14.7,
  16.2,
  17.5,
  18.5
];
export const expenseTrend = [
  8.2,
  8.7,
  8.4,
  9.1,
  8.8,
  8.24
];
