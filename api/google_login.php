<?php
// POST /api/google_login.php  { credential, email, name, google_id }
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../controllers/AuthController.php';

require_method('POST');
respond((new AuthController($conn))->googleLogin(get_input()), 200);

