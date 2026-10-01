const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

function checkout(fetch) {
  const stored = new Map([['fastdelivery_cart', JSON.stringify([{ id:1, nombre:'Arroz', cantidad:1, precio:20 }])]]);
  const nodes = new Map();
  function node(id) {
    if (!nodes.has(id)) nodes.set(id, { value:'', hidden:false, disabled:false, textContent:'', innerHTML:'', listeners:{},
      addEventListener(name, fn) { this.listeners[name] = fn; },
      setCustomValidity(message) { this.validityMessage = message; } });
    return nodes.get(id);
  }
  const form = node('checkout-form');
  form.action = 'procesar_pedido.php';
  form.elements = Object.fromEntries(['nombre','telefono','direccion','referencia','checkout_token'].map(key => [key, { value:'test-' + key }]));
  form.reportValidity = () => [...nodes.values()].every(element => !element.validityMessage);
  form.reset = () => { for (const element of nodes.values()) element.value = ''; };
  node('card-number').value = '4111 1111 1111 1111';
  node('card-holder').value = 'Cliente Demo';
  node('card-expiry').value = '12/' + String((new Date().getFullYear() + 2) % 100).padStart(2,'0');
  node('card-cvv').value = '123';
  const context = vm.createContext({ PRODUCTOS:[{ id:1, nombre:'Arroz', precio:20, stock:10, imagen:'arroz.svg', unidad:'bolsa' }],
    localStorage:{ getItem:key => stored.get(key) ?? null, setItem:(key,value) => stored.set(key,value) },
    document:{ getElementById:node }, window:{ addEventListener(){}, scrollTo(){} }, FormData, fetch, TypeError, SyntaxError });
  for (const file of ['cart.js','checkout.js']) vm.runInContext(fs.readFileSync(path.join(__dirname,'../Frontend/js',file),'utf8'),context);
  return { node, form, stored, submit:() => form.listeners.submit({ preventDefault(){} }) };
}

(async () => {
  let requests = 0;
  let payload;
  let finish;
  const pending = new Promise(resolve => { finish = resolve; });
  const page = checkout(async (_url, options) => { requests++; payload = options.body; return pending; });
  const first = page.submit();
  await page.submit();
  assert.equal(requests, 1, 'El doble clic no duplica la solicitud');
  assert.equal(page.node('pay-button').disabled, true);
  assert.deepEqual([...payload.keys()].sort(), ['carrito','checkout_token','direccion','metodo_pago','nombre','referencia','telefono'].sort());
  for (const [, value] of payload.entries()) {
    assert(!value.includes('4111') && !value.includes('Cliente Demo'), 'Los datos bancarios no forman parte de la solicitud');
  }
  finish({ ok:true, json:async () => ({ status:'success', pedido:{ id_pedido:'FD-00001', total:25 } }) });
  await first;
  assert.equal(page.stored.get('fastdelivery_cart'), '[]');
  assert.equal(page.node('checkout-success').hidden, false);
  assert.equal(page.node('checkout-content').hidden, true);
  assert.equal(page.node('card-number').value, '');
  assert.equal(page.node('success-code').textContent, 'FD-00001');
  console.log('PASS: pago, privacidad bancaria y doble clic');

  for (const failure of [async () => { throw new TypeError('Failed to fetch'); },
    async () => ({ ok:false, json:async () => ({ status:'error', mensaje:'Stock insuficiente.' }) })]) {
    const failed = checkout(failure);
    await failed.submit();
    assert.equal(JSON.parse(failed.stored.get('fastdelivery_cart')).length, 1);
    assert.equal(failed.node('payment-error').hidden, false);
    assert.equal(failed.node('pay-button').disabled, false);
  }
  console.log('PASS: fallo de red y stock preservan carrito y permiten reintentar');
  let invalidRequests = 0;
  const invalid = checkout(async () => { invalidRequests++; });
  invalid.node('card-number').value = '123';
  await invalid.submit();
  assert.equal(invalidRequests, 0);
  invalid.node('card-number').value = '4111111111111111';
  invalid.node('card-expiry').value = '01/20';
  await invalid.submit();
  assert.equal(invalidRequests, 0);
  console.log('PASS: tarjeta incompleta o vencida no se envía');
  invalid.form.elements.direccion.value = '';
  invalid.form.elements.nombre.value = 'Contacto elegido';
  invalid.node('fill-demo-payment').listeners.click();
  assert.equal(invalid.node('card-number').value, '4111 1111 1111 1111');
  assert.equal(invalid.node('card-number').validityMessage, '');
  assert.equal(invalid.node('card-expiry').validityMessage, '');
  assert.equal(invalid.form.elements.nombre.value, 'Contacto elegido');
  assert.equal(invalid.form.elements.direccion.value, 'Av. de Prueba 123, Lima');
  console.log('PASS: rellenar demo completa datos ficticios y conserva contacto existente');
  console.log('ALL CHECKOUT CHECKS PASSED');
})().catch(error => { console.error(error); process.exitCode = 1; });
