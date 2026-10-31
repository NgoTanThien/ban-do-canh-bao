<?php
// POST /api/change_password.php  { old_password, new_password, confirm_password }
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../controllers/AuthController.php';

require_method('POST');
$user = require_login();
respond((new AuthController($conn))->changePassword((int)$user['id'], get_input()));

