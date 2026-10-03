document.getElementById('delivery-menu').addEventListener('click', event => {
  const collapsed = document.getElementById('delivery-sidebar').classList.toggle('collapsed');
  event.currentTarget.setAttribute('aria-expanded', String(!collapsed));
});

document.querySelectorAll('form[data-delivery-action]').forEach(form => {
  form.addEventListener('submit', event => {
    if (form.dataset.submitting) {
      event.preventDefault();
      return;
    }
    form.dataset.submitting = 'true';
    const button = form.querySelector('button[type="submit"]');
    button.disabled = true;
    button.textContent = 'Procesando…';
  });
});

window.addEventListener('pageshow', event => {
  if (event.persisted) window.location.reload();
});
