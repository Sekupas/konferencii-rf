'use strict';
const menuButton = document.querySelector('.menu-toggle');
menuButton?.addEventListener('click', () => {
  const opened = menuButton.getAttribute('aria-expanded') === 'true';
  menuButton.setAttribute('aria-expanded', String(!opened));
  document.querySelector('#main-nav')?.classList.toggle('open', !opened);
});
document.querySelectorAll('[data-toggle-password]').forEach(button => {
  button.addEventListener('click', () => {
    const field = document.getElementById(button.dataset.togglePassword);
    const show = field.type === 'password';
    field.type = show ? 'text' : 'password';
    button.textContent = show ? 'Скрыть' : 'Показать';
    button.setAttribute('aria-label', show ? 'Скрыть пароль' : 'Показать пароль');
    button.setAttribute('aria-pressed', String(show));
  });
});
// Форматируем после ввода, чтобы не мешать удалению цифр и перемещению курсора.
document.querySelector('[data-phone]')?.addEventListener('blur', event => {
  let digits = event.target.value.replace(/\D/g, '');
  if (digits.length === 11 && ['7', '8'].includes(digits[0])) {
    event.target.value = `8(${digits.slice(1,4)})${digits.slice(4,7)}-${digits.slice(7,9)}-${digits.slice(9,11)}`;
  }
});
const room = document.querySelector('#room_id');
function updateRoom() {
  const chosen = room?.selectedOptions[0];
  if (!chosen) return;
  document.querySelector('[data-room-title]').textContent = chosen.value ? chosen.textContent.split(' · ')[0] : 'Место для ваших идей';
  document.querySelector('[data-room-description]').textContent = chosen.dataset.description || 'Выберите помещение, чтобы узнать о нём больше.';
  document.querySelector('[data-room-capacity]').textContent = chosen.dataset.capacity ? `Вместимость — до ${chosen.dataset.capacity} человек` : '';
}
room?.addEventListener('change', updateRoom);
updateRoom();
document.querySelectorAll('form[method="post"]').forEach(form => {
  form.addEventListener('submit', () => {
    const button = form.querySelector('button[type="submit"]');
    if (button) { button.disabled = true; button.setAttribute('aria-busy', 'true'); }
  });
});
window.addEventListener('pageshow', () => {
  document.querySelectorAll('button[aria-busy="true"]').forEach(button => { button.disabled = false; button.removeAttribute('aria-busy'); });
});
document.querySelector('[data-notice]')?.focus();
