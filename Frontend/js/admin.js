const dialog = document.getElementById('confirm-dialog');
let pendingForm = null;
let submitting = false;
document.querySelectorAll('form[data-confirm]').forEach(form => {
  form.addEventListener('submit', event => {
    event.preventDefault();
    if (submitting) return;
    pendingForm = form;
    document.getElementById('confirm-text').textContent = form.dataset.confirm;
    dialog.showModal();
  });
});
document.getElementById('confirm-cancel').addEventListener('click', () => { pendingForm = null; dialog.close(); });
document.getElementById('confirm-accept').addEventListener('click', () => {
  if (!pendingForm || submitting) return;
  submitting = true;
  document.getElementById('confirm-accept').disabled = true;
  pendingForm.submit();
});
document.getElementById('toggle-menu').addEventListener('click', event => {
  const collapsed = document.getElementById('admin-sidebar').classList.toggle('collapsed');
  event.currentTarget.setAttribute('aria-expanded', String(!collapsed));
});
document.querySelectorAll('form:not([data-confirm])').forEach(form => {
  if (form.method !== 'post') return;
  form.addEventListener('submit', event => {
    const state = form.elements.estado;
    if (state && state.value === 'cancelado' && !form.dataset.cancelConfirmed) {
      event.preventDefault();
      pendingForm = form;
      document.getElementById('confirm-text').textContent = 'Se cancelará el pedido y se devolverá el stock. No se realizará un reembolso.';
      dialog.showModal();
      return;
    }
    form.querySelector('button[type=submit]').disabled = true;
  });
});
