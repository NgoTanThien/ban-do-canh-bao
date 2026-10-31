<?php
// POST /api/update_profile.php  { full_name, phone }
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../controllers/AuthController.php';

require_method('POST');
$user = require_login();
respond((new AuthController($conn))->updateProfile((int)$user['id'], get_input()));
