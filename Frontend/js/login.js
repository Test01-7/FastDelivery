const demoAccounts = {
  administrador: { email: 'admin@fastdelivery.com', password: 'admin123' },
  cliente: { email: 'cliente@fastdelivery.com', password: 'cliente123' },
  repartidor: { email: 'repartidor@fastdelivery.com', password: 'repartidor123' }
};

document.querySelectorAll('[data-demo-account]').forEach(button => {
  button.addEventListener('click', () => {
    const account = demoAccounts[button.dataset.demoAccount];
    const form = document.getElementById('login-form');
    form.elements.email.value = account.email;
    form.elements.password.value = account.password;
    document.getElementById('demo-account-status').textContent = `Datos de ${button.textContent.trim()} rellenados. Pulsa «Iniciar sesión» para continuar.`;
    form.querySelector('button[type="submit"]').focus();
  });
});
