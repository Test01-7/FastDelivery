function escapar(value) {
  return String(value ?? '').replace(/[&<>"']/g, char => ({ '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;' }[char]));
}

function leerCarrito() {
  try {
    const items = JSON.parse(localStorage.getItem('fastdelivery_cart') || '[]');
    if (!Array.isArray(items)) return [];
    return items.filter(item => item && Number.isInteger(item.id) && item.id > 0 &&
      Number.isInteger(item.cantidad) && item.cantidad > 0 && Number.isFinite(item.precio) && item.precio >= 0);
  } catch { return []; }
}

function guardarCarrito(items) { localStorage.setItem('fastdelivery_cart', JSON.stringify(items)); }
function dinero(value) { return `S/ ${Number(value).toFixed(2)}`; }
function totales(items) {
  const subtotal = Math.round(items.reduce((sum, item) => sum + item.precio * item.cantidad, 0) * 100) / 100;
  const envio = subtotal === 0 || subtotal >= 50 ? 0 : 5;
  return { subtotal, envio, total: subtotal + envio };
}
