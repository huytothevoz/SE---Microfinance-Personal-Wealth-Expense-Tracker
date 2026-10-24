/**
 * WealthFlow Admin & System Settings Controller (FR13, FR14, UC12, UC13).
 * Manages admin dashboard, user locks, default categories, broadcasts, notifications, and profile settings.
 */
import { loadView } from './view-loader.js';
import { adminUsers, adminCategories, notifications } from '../data/live-data.js?v=wealth-ui-2';
import { icon, safe } from './ui-core.js?v=wealth-ui-2';
import { reportBarChart } from './reports.js';

export async function renderAdminOverviewPage() {
  const rawHtml = await loadView('admin-overview');
  const temp = document.createElement('div');
  temp.innerHTML = rawHtml;

  const chartContainer = temp.querySelector('#admin-activity-chart');
  if (chartContainer) {
    chartContainer.innerHTML = reportBarChart();
  }

  return temp.innerHTML;
}

export async function renderAdminUsersPage() {
  const rawHtml = await loadView('admin-users');
  const temp = document.createElement('div');
  temp.innerHTML = rawHtml;

  const tbody = temp.querySelector('#admin-users-table-body');
  if (tbody) {
    tbody.innerHTML = adminUsers.map(u => {
      const isLocked = String(u.status).toUpperCase() === 'LOCKED';
      const idEnc = encodeURIComponent(String(u.id));
      return `
      <tr>
        <td>
          <div class="table-name">
            <span class="table-icon">${icon('user')}</span>
            <span>${safe(u.fullName || u.name)}<small>${safe(u.email)} · #${safe(u.id)}</small></span>
          </div>
        </td>
        <td>${safe(String(u.createdAt || '').slice(0, 10))}</td>
        <td>${safe(u.baseCurrency || 'VND')}</td>
        <td>
          <span class="badge ${isLocked ? 'danger' : 'success'}">${safe(u.status)}</span>
        </td>
        <td>
          <button class="text-action" data-action="live:${isLocked ? 'unlock' : 'lock'}:adminUsers:${idEnc}">
            ${isLocked ? 'Mở khóa' : 'Khóa tài khoản'}
          </button>
        </td>
      </tr>
    `;
    }).join('');
  }

  return temp.innerHTML;
}

export async function renderAdminCategoriesPage() {
  const rawHtml = await loadView('admin-categories');
  const temp = document.createElement('div');
  temp.innerHTML = rawHtml;

  const cardList = temp.querySelector('#admin-category-card-list');
  if (cardList) {
    cardList.innerHTML = adminCategories.map(c => {
      const idEnc = encodeURIComponent(String(c.id));
      return `
      <div class="mini-panel">
        <div class="status-row">
          <h3>${safe(c.name)}</h3>
          <span class="badge ${c.type === 'INCOME' ? 'blue' : 'neutral'}">${safe(c.type)}</span>
        </div>
        <p>${c.isActive ? 'Đang kích hoạt cho người dùng mới' : 'Đang tạm ngưng'}</p>
        <div style="margin-top:15px;display:flex;gap:12px">
          <button class="text-action" data-action="live:edit:adminCategories:${idEnc}">Điều chỉnh →</button>
          <button class="text-action" data-action="live:toggle:adminCategories:${idEnc}">${c.isActive ? 'Tạm ngưng' : 'Kích hoạt'}</button>
        </div>
      </div>
    `;
    }).join('');
  }

  return temp.innerHTML;
}

export async function renderAdminBroadcastPage() {
  return loadView('admin-broadcasts');
}

export async function renderAdminSettingsPage() {
  return loadView('admin-settings');
}

export async function renderNotificationsPage() {
  const rawHtml = await loadView('notifications');
  const temp = document.createElement('div');
  temp.innerHTML = rawHtml;

  const inbox = temp.querySelector('#notification-inbox');
  if (inbox) {
    inbox.innerHTML = notifications.map(n => `
      <div class="notification-item">
        <span class="notification-icon">${n.type === 'danger' ? '!' : n.type === 'warn' ? '◷' : '✧'}</span>
        <div class="notification-copy">
          <strong>${safe(n.title)}</strong>
          <p>${safe(n.body || n.content || '')}</p>
          <small>${safe(n.createdAt || n.time || '')}</small>
        </div>
        <span class="badge ${n.type === 'danger' ? 'danger' : n.type === 'warn' ? 'warn' : 'blue'}">
          ${n.type === 'danger' ? 'Khẩn' : n.type === 'warn' ? 'Cần chú ý' : 'Thông tin'}
        </span>
      </div>
    `).join('');
  }

  return temp.innerHTML;
}

export async function renderSettingsPage({ currentUser }) {
  const rawHtml = await loadView('settings');
  const temp = document.createElement('div');
  temp.innerHTML = rawHtml;

  const fullnameEl = temp.querySelector('#settings-fullname');
  if (fullnameEl && currentUser) fullnameEl.textContent = safe(currentUser.fullName);

  const emailEl = temp.querySelector('#settings-email');
  if (emailEl && currentUser) emailEl.textContent = safe(currentUser.email);

  const currencyEl = temp.querySelector('#settings-currency');
  if (currencyEl && currentUser) currencyEl.textContent = safe(currentUser.baseCurrency);

  return temp.innerHTML;
}
