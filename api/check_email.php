<?php
// GET|POST /api/check_email.php?email=...
// Kiểm tra trùng lặp email realtime
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../controllers/AuthController.php';

require_method('GET', 'POST');
$input = get_input();
$email = $_GET['email'] ?? $input['email'] ?? '';
respond((new AuthController($conn))->checkEmail(['email' => $email]));

