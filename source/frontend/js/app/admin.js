/**
 * WealthFlow Admin & System Settings Controller
 * Manages admin dashboard, user locks, default categories, broadcasts, notifications, and profile settings.
 */
import { loadView } from './view-loader.js';
import { adminUsers, categories, notifications } from '../data/fixtures.js';
import { icon, safe } from './ui-core.js';
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
    tbody.innerHTML = adminUsers.map(u => `
      <tr>
        <td>
          <div class="table-name">
            <span class="table-icon">${icon('user')}</span>
            <span>${u.name}<small>${u.email} · ${u.id}</small></span>
          </div>
        </td>
        <td>${u.created}</td>
        <td>${u.currency}</td>
        <td>
          <span class="badge ${u.status === 'Active' ? '' : 'danger'}">${u.status}</span>
        </td>
        <td>
          <button class="text-action" data-action="admin-user-${u.status === 'Active' ? 'lock' : 'unlock'}">
            ${u.status === 'Active' ? 'Khóa tài khoản' : 'Mở khóa'}
          </button>
        </td>
      </tr>
    `).join('');
  }

  return temp.innerHTML;
}

export async function renderAdminCategoriesPage() {
  const rawHtml = await loadView('admin-categories');
  const temp = document.createElement('div');
  temp.innerHTML = rawHtml;

  const cardList = temp.querySelector('#admin-category-card-list');
  if (cardList) {
    cardList.innerHTML = categories.map(c => `
      <div class="mini-panel">
        <div class="status-row">
          <h3>${c.name}</h3>
          <span class="badge ${c.type === 'Thu nhập' ? 'blue' : 'neutral'}">${c.type}</span>
        </div>
        <p>Danh mục gốc của hệ thống</p>
        <div style="margin-top:15px">
          <button class="text-action" data-action="admin-category-edit">Điều chỉnh →</button>
        </div>
      </div>
    `).join('');
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
          <strong>${n.title}</strong>
          <p>${n.content}</p>
          <small>${n.time}</small>
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
