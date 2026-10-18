/**
 * WealthFlow Admin & System API Service
 * Interacts with /api/admin and /api/notifications endpoints
 */
import { httpClient } from './http-client.js';

export const AdminApi = {
  getUsers(params = {}) {
    return httpClient('admin/users', { params });
  },

  lockUser(userId, reason) {
    return httpClient(`admin/users/${userId}/lock`, {
      method: 'PUT',
      data: { reason }
    });
  },

  unlockUser(userId) {
    return httpClient(`admin/users/${userId}/unlock`, {
      method: 'PUT'
    });
  },

  sendAnnouncement(data) {
    return httpClient('admin/announcements', {
      method: 'POST',
      data
    });
  },

  getAuditLogs(params = {}) {
    return httpClient('admin/audit-logs', { params });
  },

  getSystemConfig() {
    return httpClient('admin/system-config');
  },

  updateSystemConfig(data) {
    return httpClient('admin/system-config', {
      method: 'PUT',
      data
    });
  },

  getNotifications() {
    return httpClient('notifications');
  },

  markNotificationRead(id) {
    return httpClient(`notifications/${id}/read`, {
      method: 'POST'
    });
  }
};
