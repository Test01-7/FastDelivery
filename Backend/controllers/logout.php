<?php
require_once __DIR__ . '/../bootstrap.php';
AuthService::logout();
header('Location: login.php');
exit;
