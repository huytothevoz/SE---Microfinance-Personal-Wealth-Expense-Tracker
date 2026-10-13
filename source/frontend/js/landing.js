const dialog = document.querySelector('#auth-dialog');
const tabs = [...document.querySelectorAll('[data-auth-tab]')];
const title = document.querySelector('#auth-title');
const nameField = document.querySelector('#register-name');
const nameInput = document.querySelector('#auth-name');
const password = document.querySelector('#auth-password');
const menu = document.querySelector('#landing-menu');
let currentTab = 'login';
function setTab(tab) {
  currentTab = tab;
  tabs.forEach(button => {
    const active = button.dataset.authTab === tab;
    button.classList.toggle('active', active);
    button.setAttribute('aria-selected', String(active));
  });
  title.textContent = tab === 'register' ? 'Tạo tài khoản WealthFlow' : 'Đăng nhập tài khoản';
  nameField.hidden = tab !== 'register';
  nameInput.required = tab === 'register';
  password.autocomplete = tab === 'register' ? 'new-password' : 'current-password';

}
document.querySelectorAll('[data-open-auth]').forEach(button => button.addEventListener('click', () => {
  setTab(button.dataset.openAuth);
  dialog.showModal();
}));
document.querySelector('[data-close-dialog]').addEventListener('click', () => dialog.close());
dialog.addEventListener('click', event => {
  if (event.target === dialog) dialog.close();
});
tabs.forEach(tab => tab.addEventListener('click', () => setTab(tab.dataset.authTab)));
document.querySelector('#auth-form').addEventListener('submit', event => {
  event.preventDefault();
  if (!event.currentTarget.reportValidity()) return;
 /* UI-only demo: intentionally never read or persist the password. */ window.location.assign('app.html');
});
menu.addEventListener('click', () => {
  const expanded = menu.getAttribute('aria-expanded') === 'true';
  menu.setAttribute('aria-expanded', String(!expanded));
  document.querySelector('.landing-nav').classList.toggle('is-open', !expanded)
});
document.querySelectorAll('.landing-nav a').forEach(link => link.addEventListener('click', () => {
  document.querySelector('.landing-nav').classList.remove('is-open');
  menu.setAttribute('aria-expanded', 'false')
}));
