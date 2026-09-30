<?php
/**
 * Fast Delivery - Cierre de Sesión Seguro
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../Backend/services/auth.php';

AuthService::logout();
header("Location: index.php?logout=1");
exit;
