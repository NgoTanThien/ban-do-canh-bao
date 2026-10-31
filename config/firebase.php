<?php
// File: config/firebase.php
// Cấu hình Firebase Web App cho tính năng Xác thực Google (Firebase Auth)

if (!defined('FIREBASE_CONFIG')) {
    define('FIREBASE_CONFIG', [
        'apiKey'            => 'AIzaSyA_bTtrkXNsKC6yj2SlbRMvOn2HJWTmz64',
        'authDomain'        => 'ban-do-canh-bao.firebaseapp.com',
        'projectId'         => 'ban-do-canh-bao',
        'storageBucket'     => 'ban-do-canh-bao.firebasestorage.app',
        'messagingSenderId' => '934944876968',
        'appId'             => '1:934944876968:web:7f6c4cfda16bebd23bd59d',
        'measurementId'     => 'G-RBMWR912JY'
    ]);
}

if (!defined('FIREBASE_API_KEY')) {
    define('FIREBASE_API_KEY', 'AIzaSyA_bTtrkXNsKC6yj2SlbRMvOn2HJWTmz64');
}

if (!defined('FIREBASE_PROJECT_ID')) {
    define('FIREBASE_PROJECT_ID', 'ban-do-canh-bao');
}

