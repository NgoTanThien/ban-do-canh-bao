<?php
// POST /api/register.php  { full_name, email, phone, username, password, confirm_password, terms_agree }
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../controllers/AuthController.php';

require_method('POST');
respond((new AuthController($conn))->register(get_input()), 200);
