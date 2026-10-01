"""Ejecutar contra el esquema aislado de integration.php --keep con DEMO_MODE=true."""
import http.cookiejar
import json
import re
import urllib.request

base = 'http://127.0.0.1:8765/Frontend/'
client = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))


def request(path, data=None):
    from urllib.parse import urlencode
    response = client.open(base + path, None if data is None else urlencode(data).encode(), timeout=10)
    return response.geturl(), response.read().decode('utf-8')


def check(condition, message):
    assert condition, message
    print('PASS:', message)


_, body = request('login.php')
check('Modo presentación' in body and 'name="demo_role"' in body, 'Login muestra acceso por rol')
for role, destination in [('administrador', 'admin.php'), ('repartidor', 'mis_pedidos.php'), ('cliente', 'checkout.php')]:
    url, body = request('login.php', {'demo_role': role, 'return': 'checkout.php'})
    check(url.endswith(destination), 'Acceso sin contraseña como ' + role)
check('Rellenar datos de prueba' in body, 'Checkout muestra botón de datos ficticios')
token = re.search(r'name="checkout_token" value="([^"]+)"', body).group(1)
_, body = request('procesar_pedido.php', {'nombre':'Demo', 'direccion':'Av. Demo 123', 'telefono':'999999999',
    'carrito':json.dumps([{'id':1, 'cantidad':1}]), 'checkout_token':token})
data = json.loads(body)
check(data['status'] == 'success', 'Presentación registra pedido')
_, body = request('procesar_pedido.php', {'nombre':'Demo', 'direccion':'Av. Demo 123', 'telefono':'999999999',
    'carrito':json.dumps([{'id':1, 'cantidad':1}]), 'checkout_token':token})
check(json.loads(body)['pedido']['id'] == data['pedido']['id'], 'Modo demo sigue evitando pedidos duplicados')
url, body = request('login.php', {'demo_role':'desconocido'})
check('El acceso de presentación no está disponible.' in body, 'Rol inválido recibe mensaje claro')
print('ALL DEMO CHECKS PASSED')
