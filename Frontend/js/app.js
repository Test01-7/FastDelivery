/**
 * FAST DELIVERY - Lógica de cliente
 */

let carrito = leerCarrito();
let categoriaActiva = 'todos';
let busquedaTexto = '';
let productoSeleccionadoModal = null;
let cantidadModal = 1;

document.addEventListener('DOMContentLoaded', () => {
  categoriaActiva = new URLSearchParams(location.search).get('categoria') || 'todos';
  busquedaTexto = (new URLSearchParams(location.search).get('buscar') || '').toLowerCase();
  renderProductos();
  actualizarUI();
  setupEvents();
});

function setupEvents() {
  // Filtro Categorías
  const catButtons = document.querySelectorAll('.cat-chip');
  catButtons.forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      catButtons.forEach(b => b.classList.remove('active'));
      const target = e.currentTarget;
      target.classList.add('active');
      categoriaActiva = target.getAttribute('data-cat');
      renderProductos();
    });
  });

  // Buscador
  const searchInput = document.getElementById('search-input');
  if (searchInput) {
    searchInput.addEventListener('input', (e) => {
      busquedaTexto = e.target.value.toLowerCase().trim();
      renderProductos();
    });
  }

  // Drawer Carrito
  const cartBtn = document.getElementById('cart-btn');
  const closeCartBtn = document.getElementById('close-cart-btn');
  const cartOverlay = document.getElementById('cart-overlay');

  if (cartBtn && cartOverlay) {
    cartBtn.addEventListener('click', (e) => {
      e.preventDefault();
      cartOverlay.classList.add('active');
    });
  }

  if (closeCartBtn && cartOverlay) {
    closeCartBtn.addEventListener('click', () => {
      cartOverlay.classList.remove('active');
    });
  }

  if (cartOverlay) {
    cartOverlay.addEventListener('click', (e) => {
      if (e.target === cartOverlay) {
        cartOverlay.classList.remove('active');
      }
    });
  }

  const btnCheckout = document.getElementById('btn-checkout');
  if (btnCheckout) btnCheckout.addEventListener('click', () => {
    if (!carrito.length) return mostrarToast('Agrega productos al carrito para continuar');
    window.location.href = 'checkout.php';
  });
}

// Renderizar Productos en Grid
function renderProductos() {
  const container = document.getElementById('products-grid');
  if (!container) return;

  const lista = PRODUCTOS.filter(item => {
    const coincideCat = categoriaActiva === 'todos' || (item.categorias || [item.categoria]).includes(categoriaActiva);
    const coincideBusqueda = item.nombre.toLowerCase().includes(busquedaTexto) ||
                             item.unidad.toLowerCase().includes(busquedaTexto);
    return coincideCat && coincideBusqueda;
  });

  if (lista.length === 0) {
    container.innerHTML = `
      <div style="grid-column: 1 / -1; text-align: center; padding: 3rem 1rem; color: #64748b;">
        <p style="font-weight: 600; margin-bottom: 0.3rem;">No se encontraron productos en esta categoría o búsqueda</p>
        <p style="font-size: 0.85rem;">Intenta buscar otra palabra clave.</p>
      </div>
    `;
    return;
  }

  container.innerHTML = lista.map(prod => {
    const itemEnCarrito = carrito.find(c => c.id === prod.id);
    const cant = itemEnCarrito ? itemEnCarrito.cantidad : 0;
    const precioActual = prod.precio_oferta || prod.precio;

    return `
      <div class="card-product">
        <div class="product-img-box" onclick="verDetalleProducto(${prod.id})" style="cursor: pointer;">
          <img src="${escapar(prod.imagen)}" alt="${escapar(prod.nombre)}">
        </div>
        <div class="product-unit-tag">${escapar(prod.unidad)}</div>
        <h4 class="product-name" onclick="verDetalleProducto(${prod.id})" style="cursor: pointer;">${escapar(prod.nombre)}</h4>
        <div style="font-size: 0.75rem; color: #16a34a; font-weight: 600; margin-bottom: 0.4rem;">
          Stock: ${prod.stock} unidades
        </div>
        <div class="product-footer">
          <div class="product-price-val">S/ ${precioActual.toFixed(2)}</div>
          
          ${cant > 0 ? `
            <div class="inline-qty-control">
              <button class="inline-qty-btn" onclick="cambiarCantidad(${prod.id}, -1)">-</button>
              <span class="inline-qty-num">${cant}</span>
              <button class="inline-qty-btn" onclick="cambiarCantidad(${prod.id}, 1)">+</button>
            </div>
          ` : `
            <button class="btn-add" onclick="agregarAlCarrito(${prod.id})">+ Agregar</button>
          `}
        </div>
      </div>
    `;
  }).join('');
}

// Modal Ver Producto (Detalle)
function verDetalleProducto(id) {
  const prod = PRODUCTOS.find(p => p.id === id);
  if (!prod) return;

  productoSeleccionadoModal = prod;
  cantidadModal = 1;

  document.getElementById('modal-prod-nombre').textContent = prod.nombre;
  document.getElementById('modal-prod-img').src = prod.imagen;
  document.getElementById('modal-prod-unidad').textContent = prod.unidad;
  document.getElementById('modal-prod-desc').textContent = prod.descripcion;
  
  const precio = prod.precio_oferta || prod.precio;
  document.getElementById('modal-prod-precio').textContent = `S/ ${precio.toFixed(2)}`;
  document.getElementById('modal-prod-stock').textContent = `Stock disponible: ${prod.stock} unidades`;
  document.getElementById('modal-prod-cant').textContent = cantidadModal;

  const btnAgregarModal = document.getElementById('btn-modal-agregar');
  btnAgregarModal.onclick = () => {
    agregarAlCarrito(prod.id, cantidadModal);
    document.getElementById('modal-detalle-producto').classList.remove('active');
  };

  document.getElementById('modal-detalle-producto').classList.add('active');
}

function cambiarCantModal(delta) {
  cantidadModal += delta;
  if (cantidadModal < 1) cantidadModal = 1;
  document.getElementById('modal-prod-cant').textContent = cantidadModal;
}

// Agregar y Editar Carrito
function agregarAlCarrito(id, cantidadSumar = 1) {
  const producto = PRODUCTOS.find(p => p.id === id);
  if (!producto) return;
  const actual = carrito.find(i => i.id === id)?.cantidad || 0;
  if (actual + cantidadSumar > producto.stock) return mostrarToast('No hay suficiente stock disponible');

  const precio = producto.precio_oferta || producto.precio;
  const itemExistente = carrito.find(i => i.id === id);

  if (itemExistente) {
    itemExistente.cantidad += cantidadSumar;
  } else {
    carrito.push({
      id: producto.id,
      nombre: producto.nombre,
      precio: precio,
      imagen: producto.imagen,
      unidad: producto.unidad,
      cantidad: cantidadSumar
    });
  }

  guardarYActualizar();
  mostrarToast(`Añadido: ${producto.nombre}`);
}

function cambiarCantidad(id, delta) {
  const idx = carrito.findIndex(i => i.id === id);
  if (idx > -1) {
    const prod = PRODUCTOS.find(p => p.id === id);
    if (delta > 0 && prod && carrito[idx].cantidad + delta > prod.stock) return mostrarToast('No hay suficiente stock disponible');
    carrito[idx].cantidad += delta;
    if (carrito[idx].cantidad <= 0) {
      carrito.splice(idx, 1);
    }
  }
  guardarYActualizar();
}

function eliminarDelCarrito(id) {
  carrito = carrito.filter(i => i.id !== id);
  guardarYActualizar();
}

function guardarYActualizar() {
  guardarCarrito(carrito);
  renderProductos();
  actualizarUI();
}

function actualizarUI() {
  const cartCountEl = document.getElementById('cart-count');
  const drawerBody = document.getElementById('drawer-body-content');
  const subtotalEl = document.getElementById('cart-subtotal');
  const envioEl = document.getElementById('cart-envio');
  const totalEl = document.getElementById('cart-total');
  const shippingProgressText = document.getElementById('shipping-progress-text');
  const shippingProgressBar = document.getElementById('shipping-progress-bar');

  // Cantidad total de items
  const totalCant = carrito.reduce((sum, i) => sum + i.cantidad, 0);
  if (cartCountEl) cartCountEl.textContent = totalCant;

  // Render listado drawer
  if (drawerBody) {
    if (carrito.length === 0) {
      drawerBody.innerHTML = `
        <div style="text-align: center; padding: 3rem 1rem; color: #94a3b8;">
          <p style="font-weight: 600; color: #475569; margin-bottom: 0.3rem;">Tu carrito está vacío</p>
          <p style="font-size: 0.825rem;">Agrega productos desde el catálogo.</p>
        </div>
      `;
    } else {
      drawerBody.innerHTML = carrito.map(item => `
        <div class="cart-item-row">
          <img src="${escapar(item.imagen)}" alt="${escapar(item.nombre)}" class="cart-item-img">
          <div class="cart-item-details">
            <div class="cart-item-title">${escapar(item.nombre)}</div>
            <div class="cart-item-price">S/ ${(item.precio * item.cantidad).toFixed(2)}</div>
          </div>
          <div class="inline-qty-control">
            <button class="inline-qty-btn" onclick="cambiarCantidad(${item.id}, -1)">-</button>
            <span class="inline-qty-num">${item.cantidad}</span>
            <button class="inline-qty-btn" onclick="cambiarCantidad(${item.id}, 1)">+</button>
          </div>
          <button onclick="eliminarDelCarrito(${item.id})" style="color: #94a3b8; font-size: 0.8rem; margin-left: 0.4rem;">✕</button>
        </div>
      `).join('');
    }
  }

  // Cálculos de totales y barra de envío gratis
  const subtotal = carrito.reduce((sum, i) => sum + (i.precio * i.cantidad), 0);
  const metaEnvioGratis = 50.00;
  const envio = (subtotal >= metaEnvioGratis || subtotal === 0) ? 0.00 : 5.00;
  const total = subtotal + envio;

  if (subtotalEl) subtotalEl.textContent = `S/ ${subtotal.toFixed(2)}`;
  if (envioEl) envioEl.textContent = envio === 0 ? 'GRATIS' : `S/ ${envio.toFixed(2)}`;
  if (totalEl) totalEl.textContent = `S/ ${total.toFixed(2)}`;

  if (shippingProgressText && shippingProgressBar) {
    if (subtotal >= metaEnvioGratis) {
      shippingProgressText.textContent = 'Tienes envío gratis';
      shippingProgressBar.style.width = '100%';
    } else {
      const falta = metaEnvioGratis - subtotal;
      const pct = Math.min((subtotal / metaEnvioGratis) * 100, 100);
      shippingProgressText.textContent = `Te faltan S/ ${falta.toFixed(2)} para envío gratis`;
      shippingProgressBar.style.width = `${pct}%`;
    }
  }


}

// Toast flotante natural
function mostrarToast(mensaje) {
  const toast = document.createElement('div');
  toast.className = 'toast-msg';
  toast.textContent = mensaje;
  document.body.appendChild(toast);

  setTimeout(() => {
    toast.style.opacity = '0';
    setTimeout(() => toast.remove(), 200);
  }, 2500);
}
