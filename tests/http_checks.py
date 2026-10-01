"""Pruebas HTTP contra el servidor local conectado al esquema aislado de integration.php --keep."""
import html
import http.cookiejar
import json
import re
import sys
import time
import urllib.error
import urllib.parse
import urllib.request

BASE = sys.argv[1] if len(sys.argv) > 1 else 'http://127.0.0.1:8765/Frontend/'


class Client:
    def __init__(self):
        self.opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))

    def get(self, path, values=None):
        payload = urllib.parse.urlencode(values).encode() if values is not None else None
        try:
            response = self.opener.open(BASE + path, payload, timeout=10)
        except urllib.error.HTTPError as error:
            response = error
        return response.status, response.geturl(), response.read().decode('utf-8')

    def login(self, email):
        _, _, body = self.get('login.php')
        return self.get('login.php', {'email': email, 'password': 'prueba123', 'return': 'checkout.php'})


def field(body, name):
    match = re.search(r'name="' + re.escape(name) + r'" value="([^"]*)"', body)
    assert match, name
    return html.unescape(match.group(1))


def check(condition, message):
    assert condition, message
    print('PASS:', message)


guest = Client()
status, url, body = guest.get('checkout.php')
check(status == 200 and 'login.php?return=checkout.php' in url, 'Checkout exige sesión y conserva destino')
status, _, body = guest.get('procesar_pedido.php', {'carrito': '[]'})
check(status == 401 and json.loads(body)['status'] == 'error', 'Endpoint rechaza compra sin sesión')
_, _, body = guest.get('register.php?return=checkout.php')
public_email = 'publico' + str(time.time_ns()) + '@test.local'
signup = {'return': 'checkout.php', 'nombre':'Nuevo', 'apellido':'Cliente',
          'telefono':'999999999', 'email':public_email, 'password':'prueba123', 'rol':'administrador'}
status, url, body = guest.get('register.php', signup)
check(status == 200 and 'login.php?return=checkout.php' in url and 'Usuario registrado exitosamente' in body, 'Registro redirige al login con confirmación')
status, url, _ = guest.login(public_email)
check(status == 200 and url.endswith('checkout.php'), 'Cuenta registrada puede iniciar sesión y continuar compra')
status, url, _ = guest.get('admin.php')
check(status == 200 and 'admin.php' not in url, 'Registro público ignora un rol administrativo enviado')

client = Client()
status, url, body = client.login('cliente@test.local')
check(status == 200 and url.endswith('checkout.php'), 'Login del cliente regresa al checkout')
status, url, _ = client.get('admin.php?panel=usuarios')
check(status == 200 and 'admin.php' not in url, 'Cliente no accede a administración')
_, _, body = client.get('checkout.php')
payload = {'checkout_token': field(body, 'checkout_token'),
           'nombre': 'Cliente Prueba', 'direccion': 'Av. HTTP 123', 'telefono': '999999999',
           'carrito': json.dumps([{'id': 1, 'cantidad': 1}])}
status, _, body = client.get('procesar_pedido.php', payload)
pedido = json.loads(body)['pedido']
check(status == 200 and pedido['total'] == 25, 'Compra HTTP registra pedido y total')
status, _, body = client.get('procesar_pedido.php', payload)
check(status == 200 and json.loads(body)['pedido']['id'] == pedido['id'], 'Repetir envío devuelve el mismo pedido')
other = Client()
other.login('otro@test.local')
_, _, body = other.get('seguimiento.php?id=' + pedido['id_pedido'])
check('No se encontró el pedido' in body, 'Un cliente no consulta el pedido de otro')

driver = Client()
status, url, _ = driver.login('repartidor@test.local')
check(status == 200 and 'mis_pedidos.php' in url, 'Login del repartidor conserva destino por rol')
status, url, _ = driver.get('admin.php?panel=pedidos')
check(status == 200 and 'admin.php' not in url, 'Repartidor no accede a administración')

admin = Client()
status, url, _ = admin.login('admin@test.local')
check(status == 200 and url.endswith('admin.php'), 'Administrador accede a su panel')
for panel in ['productos', 'usuarios', 'pedidos']:
    status, _, body = admin.get('admin.php?panel=' + panel)
    check(status == 200 and 'Panel de ' + panel in body, 'Panel ' + panel + ' responde')
_, _, body = admin.get('admin.php?panel=usuarios&rol=cliente&orden=nombre&direccion=asc')
check('rol=cliente&amp;orden=nombre&amp;direccion=asc&amp;page=2' in body, 'Paginación conserva filtros y orden')
_, _, body = admin.get('admin.php?panel=pedidos')
status, _, body = admin.get('admin.php?panel=pedidos', {'action': 'delete', 'id': pedido['id']})
check(status == 200 and 'Pedido cancelado.' in body, 'Administración cancela pedido con historial')
_, _, body = client.get('seguimiento.php?id=' + pedido['id_pedido'])
check('Este pedido fue cancelado.' in body, 'Seguimiento refleja cancelación del administrador')
_, _, body = client.get('mis_pedidos.php')
check('Cancelado' in body, 'Mis pedidos refleja cancelación')
_, _, body = admin.get('admin.php?panel=usuarios')
status, _, body = admin.get('admin.php?panel=usuarios', {'action': 'deactivate', 'id': 1})
check(status == 200 and 'No puedes eliminar, desactivar' in body, 'Administración impide desactivar la cuenta conectada')
print('ALL HTTP CHECKS PASSED')
