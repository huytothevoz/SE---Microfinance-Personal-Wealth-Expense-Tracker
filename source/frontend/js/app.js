/**
 * WealthFlow SPA Application Entry Point
 * Bootstraps the application lifecycle and exposes startup failures in the UI.
 */
import('./app/main.js?v=wallet-refactor-20261018-5').catch(error => {
  console.error('Không thể khởi động WealthFlow:', error);
  const app = document.querySelector('#app-content');
  if (!app) return;

  const panel = document.createElement('section');
  panel.className = 'panel page-load-error';
  panel.setAttribute('role', 'alert');
  panel.innerHTML = '<div class="panel-title"><div><h2>Không thể khởi động ứng dụng</h2><p></p></div></div><button type="button" class="btn btn--outline btn-sm">Tải lại trang</button>';
  panel.querySelector('p').textContent = error?.message || 'Đã xảy ra lỗi khi tải các module giao diện.';
  panel.querySelector('button').addEventListener('click', () => location.reload());
  app.replaceChildren(panel);
});
