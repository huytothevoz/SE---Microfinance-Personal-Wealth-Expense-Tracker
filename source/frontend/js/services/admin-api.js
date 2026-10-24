/**
 * WealthFlow System Administration API Client (FR13, FR14, UC12, UC13, SQ08).
 */
import { httpClient } from './http-client.js';

export const AdminApi = {
  /**
   * Fetch macro system overview stats without personal financial data (FR14.01, BR11).
   */
  getOverview() {
    return httpClient('admin/overview', { method: 'GET' });
  },

  /**
   * List system users with macro status/role metadata (FR14.01, BR11).
   */
  getUsers(params = {}) {
    return httpClient('admin/users', { method: 'GET', params });
  },

  /**
   * Lock a user account with mandatory reason >= 10 chars and revoke sessions (FR14.03, SQ08).
   */
  lockUser(userId, reason) {
    return httpClient(`admin/users/${encodeURIComponent(String(userId))}/lock`, {
      method: 'PUT',
      data: { reason }
    });
  },

  /**
   * Unlock a locked user account (FR14.04).
   */
  unlockUser(userId, reason = '') {
    return httpClient(`admin/users/${encodeURIComponent(String(userId))}/unlock`, {
      method: 'PUT',
      data: { reason }
    });
  },

  /**
   * List default system categories (FR13.01).
   */
  getCategories(params = {}) {
    return httpClient('admin/categories', { method: 'GET', params });
  },

  /**
   * Create a new default system category (FR13.01).
   */
  createCategory(data) {
    return httpClient('admin/categories', {
      method: 'POST',
      data
    });
  },

  /**
   * Update or toggle active status of a default system category (FR13.01).
   */
  updateCategory(id, data) {
    return httpClient(`admin/categories/${encodeURIComponent(String(id))}`, {
      method: 'PUT',
      data
    });
  },

  /**
   * Fetch all operational system configuration parameters and bounds (FR13.02).
   */
  getSystemConfig() {
    return httpClient('admin/system-config', { method: 'GET' });
  },

  /**
   * Update one or multiple operational system configuration parameters (FR13.02).
   */
  updateSystemConfig(data) {
    return httpClient('admin/system-config', {
      method: 'PUT',
      data
    });
  },

  /**
   * Update a single operational configuration parameter by key (FR13.02).
   */
  updateSystemConfigKey(key, value) {
    return httpClient(`admin/system-config/${encodeURIComponent(String(key))}`, {
      method: 'PUT',
      data: { value }
    });
  },

  /**
   * List broadcast announcement history (FR13.03).
   */
  getAnnouncements(params = {}) {
    return httpClient('admin/announcements', { method: 'GET', params });
  },

  /**
   * Broadcast a system announcement to all ACTIVE users via SQL Batch Fan-out (FR13.03, SQ08).
   */
  broadcastAnnouncement(data) {
    return httpClient('admin/announcements', {
      method: 'POST',
      data
    });
  },

  /**
   * List administrative audit logs (FR13.02, FR13.03, FR14.03, FR14.04).
   */
  getAuditLogs(params = {}) {
    return httpClient('admin/audit-logs', { method: 'GET', params });
  }
};

export const adminApi = AdminApi;
