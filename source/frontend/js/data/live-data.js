/**
 * WealthFlow frontend live-data adapter.
 * NO demo records or localStorage financial data. A successful authenticated API
 * response (including []) is the only source of account financial information.
 * 404/invalid JSON are explicitly marked unavailable, NOT converted into fake zeros.
 */
export const wallets = [];
export const transactions = [];
export const budgets = [];
export const recurring = [];
export const goals = [];
export const loans = [];
export const gold = [];
export const notifications = [];
export const categories = [];
export const adminUsers = [];
export const trend = [];
export const incomeTrend = [];
export const expenseTrend = [];

const collections = { wallets, transactions, budgets, recurring, goals, loans, gold, notifications, categories, adminUsers };
const candidates = {
  wallets: ['/api/wallets', '/api/finance/wallets'],
  transactions: ['/api/transactions', '/api/finance/transactions'],
  budgets: ['/api/budgets', '/api/finance/budgets'],
  recurring: ['/api/recurring', '/api/finance/recurring'],
  goals: ['/api/goals', '/api/finance/goals'],
  loans: ['/api/loans', '/api/finance/loans'],
  gold: ['/api/gold', '/api/finance/gold'],
  notifications: ['/api/notifications', '/api/finance/notifications'],
  categories: ['/api/categories', '/api/finance/categories'],
  adminUsers: ['/api/admin/users']
};
export const dataStatus = Object.fromEntries(Object.keys(collections).map(k => [k, { state: 'idle', message: '', endpoint: '' }]));
let generation = 0;
const keyVariants = k => [k, k === 'adminUsers' ? 'users' : k, 'items', 'rows', 'results'];
function extractCollection(json, name) {
  if (Array.isArray(json)) return json;
  for (const obj of [json, json?.data, json?.result, json?.payload]) {
    if (Array.isArray(obj)) return obj;
    if (!obj || typeof obj !== 'object') continue;
    for (const key of keyVariants(name)) {
      if (Array.isArray(obj[key])) return obj[key];
    }
  }
  throw new Error('API trả về dữ liệu không đúng định dạng danh sách.');
}
function shape(name, row) {
  if (!row || typeof row !== 'object') return null;
  const r = { ...row };
  if (name === 'transactions') {
    r.id = String(r.id ?? r.transactionId ?? r.transaction_id ?? '');
    r.name = String(r.name ?? r.description ?? r.title ?? r.note ?? 'Giao dịch');
    r.type = String(r.type ?? r.transactionType ?? r.transaction_type ?? '').toLowerCase();
    r.amount = Number(r.amountBase ?? r.amount_base ?? r.baseAmount ?? r.amount ?? 0);
    r.hasBaseAmount = ['amountBase', 'amount_base', 'baseAmount'].some(k => row[k] != null) || String(row.currency ?? 'VND').toUpperCase() === 'VND';
    r.date = String(r.date ?? r.occurredAt ?? r.occurred_at ?? r.createdAt ?? '').slice(0, 10);
    r.category = String(r.categoryName ?? r.category?.name ?? r.category ?? 'Chưa phân loại');
    r.wallet = String(r.walletName ?? r.wallet?.name ?? r.wallet ?? '—');
    r.status = String(r.status ?? 'POSTED').toUpperCase();
    r.currency = String(r.currency ?? 'VND');
    r.icon = r.icon ?? (r.type === 'income' ? '↗' : r.type === 'expense' ? '↘' : '⇄');
    r.details = String(r.details ?? r.note ?? '');
    r.rate = r.rate ?? r.exchangeRate ?? 1;
  }
  if (name === 'wallets') {
    r.id = String(r.id ?? r.walletId ?? '');
    r.name = String(r.name ?? r.walletName ?? 'Ví');
    r.balance = Number(r.balance ?? r.currentBalance ?? r.current_balance ?? 0);
    r.currency = String(r.currency ?? 'VND');
  }
  if (name === 'budgets') {
    r.name = String(r.name ?? r.categoryName ?? r.category?.name ?? 'Ngân sách');
    r.limit = Number(r.limit ?? r.amount ?? r.limitAmount ?? 0);
    r.spent = Number(r.spent ?? r.spentAmount ?? r.used ?? 0);
  }
  if (name === 'goals') {
    r.name = String(r.name ?? r.title ?? 'Mục tiêu');
    r.target = Number(r.target ?? r.targetAmount ?? 0);
    r.current = Number(r.current ?? r.savedAmount ?? r.saved ?? 0);
  }
  return r;
}
function errorText(code, responseText = '') {
  if (code === 401) return 'Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại.';
  if (code === 403) return 'Tài khoản không có quyền truy cập dữ liệu này.';
  if (code === 404) return 'Backend chưa cung cấp API cho phân hệ này.';
  return `API lỗi HTTP ${code}${responseText ? ': ' + responseText.slice(0, 140) : ''}`;
}
async function getOne(name, requestGeneration) {
  // Clear stale values BEFORE fetching: never show previous session's information.
  collections[name].splice(0, collections[name].length);
  dataStatus[name] = { state: 'loading', message: '', endpoint: '' };
  let unsupported = true;
  for (const path of candidates[name]) {
    let response;
    try { response = await fetch(path, { method: 'GET', credentials: 'same-origin', headers: { Accept: 'application/json' }, cache: 'no-store' }); }
    catch (error) {
      if (requestGeneration === generation) dataStatus[name] = { state: 'error', endpoint: path, message: `Không thể kết nối máy chủ: ${error.message}` };
      return;
    }
    if (response.status === 404) continue;
    unsupported = false;
    if (response.status === 401) {
      if (requestGeneration === generation) dataStatus[name] = { state: 'unauthorized', endpoint: path, message: errorText(401) };
      return;
    }
    if (!response.ok) {
      if (requestGeneration === generation) dataStatus[name] = { state: 'error', endpoint: path, message: errorText(response.status) };
      return;
    }
    let json;
    try {
      if (!(response.headers.get('content-type') || '').includes('json')) throw new Error('API không trả JSON (có thể bị chuyển hướng sang trang HTML).');
      json = await response.json();
      if (json?.success === false) throw new Error(String(json.message ?? 'API không thành công.'));
      const rows = extractCollection(json, name).map(r => shape(name, r)).filter(Boolean);
      if (requestGeneration !== generation) return;
      collections[name].splice(0, collections[name].length, ...rows);
      dataStatus[name] = { state: 'ready', message: '', endpoint: path };
      return;
    } catch (error) {
      if (requestGeneration === generation) dataStatus[name] = { state: 'error', endpoint: path, message: error.message };
      return;
    }
  }
  if (unsupported && requestGeneration === generation) dataStatus[name] = { state: 'unavailable', message: 'Backend chưa cung cấp API cho phân hệ này.', endpoint: '' };
}
export async function loadLiveData({ admin = false } = {}) {
  const thisGeneration = ++generation;
  const keys = Object.keys(collections).filter(k => k !== 'adminUsers' || admin);
  await Promise.all(keys.map(k => getOne(k, thisGeneration)));
  return dataStatus;
}
export function getEndpoint(name) { return dataStatus[name]?.state === 'ready' ? dataStatus[name].endpoint : ''; }
export function recordCount(name) { return collections[name]?.length ?? 0; }
export async function mutateLiveData(name, method, record, id = null) {
  const base = getEndpoint(name);
  if (!base) throw new Error('API phân hệ này chưa khả dụng. Chưa lưu dữ liệu nào.');
  const endpoint = id === null ? base : `${base}/${encodeURIComponent(String(id))}`;
  const headers = { 'Content-Type': 'application/json', Accept: 'application/json' };
  // Use the existing backend's CSRF cookie/header scheme when configured.
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
  if (csrf) headers['X-CSRF-Token'] = csrf;
  const response = await fetch(endpoint, { method, credentials: 'same-origin', headers, body: record === null ? undefined : JSON.stringify(record) });
  let result;
  try { result = await response.json(); } catch { throw new Error(`Máy chủ trả về phản hồi không phải JSON (HTTP ${response.status}).`); }
  if (!response.ok || result?.success === false) throw new Error(result?.message || errorText(response.status));
  return result;
}
