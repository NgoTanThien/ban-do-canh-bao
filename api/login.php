<?php
// POST /api/login.php  { username_or_email, password, remember_me }
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../controllers/AuthController.php';

require_method('POST');
respond((new AuthController($conn))->login(get_input()));
