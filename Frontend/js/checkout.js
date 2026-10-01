let carrito = leerCarrito();
let enviando = false;
const form = document.getElementById('checkout-form');
const button = document.getElementById('pay-button');
const errorBox = document.getElementById('payment-error');
let carritoInvalido = false;

function sincronizarProductos() {
  carritoInvalido = false;
  carrito.forEach(item => {
    const producto = PRODUCTOS.find(prod => prod.id === item.id);
    if (!producto || producto.stock < item.cantidad) carritoInvalido = true;
    if (producto) Object.assign(item, { nombre: producto.nombre, precio: producto.precio, imagen: producto.imagen, unidad: producto.unidad });
  });
  guardarCarrito(carrito);
}

function mostrarError(message) { errorBox.textContent = message; errorBox.hidden = false; }

function renderCheckout() {
  sincronizarProductos();
  document.getElementById('checkout-count').textContent = carrito.reduce((sum, item) => sum + item.cantidad, 0);
  const list = document.getElementById('checkout-items');
  list.innerHTML = carrito.length ? carrito.map(item => {
    const prod = PRODUCTOS.find(producto => producto.id === item.id);
    const unavailable = !prod || prod.stock < item.cantidad;
    return `<div class="cart-line"><img src="${escapar(item.imagen)}" alt="${escapar(item.nombre)}"><div><strong>${escapar(item.nombre)}</strong><small>${escapar(item.unidad)} · ${dinero(item.precio)}</small>${unavailable ? '<small style="color:#b91c1c">No disponible o stock insuficiente</small>' : ''}<div class="quantity"><button type="button" data-id="${item.id}" data-delta="-1" aria-label="Reducir cantidad">−</button><span>${item.cantidad}</span><button type="button" data-id="${item.id}" data-delta="1" aria-label="Aumentar cantidad">+</button></div><button class="remove-item" type="button" data-remove="${item.id}">Eliminar</button></div><strong>${dinero(item.precio * item.cantidad)}</strong></div>`;
  }).join('') : '<div class="empty-state"><p>Tu carrito está vacío.</p><a class="btn secondary" href="index.php">Explorar productos</a></div>';
  const total = totales(carrito);
  document.getElementById('checkout-subtotal').textContent = dinero(total.subtotal);
  document.getElementById('checkout-envio').textContent = total.envio ? dinero(total.envio) : 'GRATIS';
  document.getElementById('checkout-total').textContent = dinero(total.total);
  button.textContent = `Hacer pago · ${dinero(total.total)}`;
  button.disabled = !carrito.length || carritoInvalido || enviando;
}

document.getElementById('checkout-items').addEventListener('click', event => {
  if (enviando) return;
  const remove = event.target.closest('[data-remove]');
  const change = event.target.closest('[data-delta]');
  if (remove) carrito = carrito.filter(item => item.id !== Number(remove.dataset.remove));
  if (change) {
    const item = carrito.find(item => item.id === Number(change.dataset.id));
    if (!item) return;
    const delta = Number(change.dataset.delta);
    const prod = PRODUCTOS.find(producto => producto.id === item.id);
    if (delta > 0 && (!prod || item.cantidad + delta > prod.stock)) return mostrarError('No hay suficiente stock disponible.');
    item.cantidad += delta;
    carrito = carrito.filter(item => item.cantidad > 0);
  }
  errorBox.hidden = true;
  renderCheckout();
});

const cardNumber = document.getElementById('card-number');
const cardExpiry = document.getElementById('card-expiry');
cardNumber.addEventListener('input', () => {
  cardNumber.setCustomValidity('');
  cardNumber.value = cardNumber.value.replace(/\D/g, '').slice(0, 19).replace(/(.{4})/g, '$1 ').trim();
});
cardExpiry.addEventListener('input', () => {
  cardExpiry.setCustomValidity('');
  const digits = cardExpiry.value.replace(/\D/g, '').slice(0, 4);
  cardExpiry.value = digits.length > 2 ? digits.slice(0, 2) + '/' + digits.slice(2) : digits;
});
document.getElementById('card-holder').addEventListener('input', event => event.target.setCustomValidity(''));

document.getElementById('fill-demo-payment')?.addEventListener('click', () => {
  if (enviando) return;
  for (const [key, value] of Object.entries({ nombre:'Cliente de prueba', telefono:'999999999', direccion:'Av. de Prueba 123, Lima' })) {
    if (!form.elements[key].value.trim()) form.elements[key].value = value;
  }
  document.getElementById('card-holder').value = 'Titular de prueba';
  cardNumber.value = '4111 1111 1111 1111';
  cardExpiry.value = '12/' + String((new Date().getFullYear() + 2) % 100).padStart(2, '0');
  document.getElementById('card-cvv').value = '123';
  for (const field of [cardNumber, cardExpiry, document.getElementById('card-holder'), document.getElementById('card-cvv')]) field.setCustomValidity('');
  errorBox.hidden = true;
});

form.addEventListener('submit', async event => {
  event.preventDefault();
  if (enviando || !carrito.length || carritoInvalido) return;
  const number = cardNumber.value.replace(/\s/g, '');
  cardNumber.setCustomValidity(/^\d{13,19}$/.test(number) ? '' : 'Introduce entre 13 y 19 dígitos.');
  const [month, year] = cardExpiry.value.split('/').map(Number);
  const now = new Date();
  cardExpiry.setCustomValidity(month >= 1 && month <= 12 && year >= 0 &&
    (2000 + year > now.getFullYear() || (2000 + year === now.getFullYear() && month >= now.getMonth() + 1)) ? '' : 'Introduce una fecha de vencimiento válida.');
  const holder = document.getElementById('card-holder');
  holder.setCustomValidity(holder.value.trim() ? '' : 'Introduce el nombre del titular.');
  if (!form.reportValidity()) return;
  enviando = true;
  button.disabled = true;
  button.textContent = 'Procesando pago…';
  errorBox.hidden = true;
  const payload = new FormData();
  // No usar FormData(form): los campos bancarios nunca forman parte de la solicitud.
  for (const key of ['nombre', 'telefono', 'direccion', 'referencia',  'checkout_token']) payload.append(key, form.elements[key].value);
  payload.append('metodo_pago', 'Tarjeta');
  payload.append('carrito', JSON.stringify(carrito.map(item => ({ id:item.id, cantidad:item.cantidad }))));
  try {
    const response = await fetch(form.action, { method:'POST', body:payload, headers:{ Accept:'application/json' } });
    const data = await response.json();
    if (!response.ok || data.status !== 'success') throw new Error(data.mensaje || 'No se pudo registrar el pedido.');
    carrito = [];
    guardarCarrito(carrito);
    form.reset();
    document.getElementById('checkout-content').hidden = true;
    document.getElementById('success-code').textContent = data.pedido.id_pedido;
    document.getElementById('success-total').textContent = `Total: ${dinero(data.pedido.total)}`;
    document.getElementById('success-tracking').href = `seguimiento.php?id=${encodeURIComponent(data.pedido.id_pedido)}`;
    document.getElementById('checkout-success').hidden = false;
    window.scrollTo({ top:0, behavior:'smooth' });
  } catch (error) {
    mostrarError(error instanceof SyntaxError ? 'El servidor no respondió correctamente. Tu carrito se conserva.' :
      error instanceof TypeError ? 'No se pudo conectar con el servidor. Tu carrito se conserva; vuelve a intentar.' : error.message);
    enviando = false;
    renderCheckout();
  }
});

window.addEventListener('storage', event => {
  if (event.key === 'fastdelivery_cart' && !enviando) { carrito = leerCarrito(); renderCheckout(); }
});
renderCheckout();
