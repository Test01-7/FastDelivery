<?php
function requireScalarPost(): void {
    foreach ($_POST as $value) {
        if (!is_string($value)) {
            http_response_code(400);
            exit('Los datos del formulario no son válidos.');
        }
    }
}

function returnPage($value): string {
    return in_array($value, ['index.php', 'checkout.php', 'mis_pedidos.php'], true) ? $value : 'index.php';
}
