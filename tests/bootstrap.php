<?php

require __DIR__.'/../vendor/autoload.php';

// Generate an ephemeral APP_KEY for this test run. Never committed; resolves
// the encryption-service init that route-driven feature tests need (sessions,
// cookies, csrf, etc.) without leaking a real key into the repo.
if (!getenv('APP_KEY')) {
    $key = 'base64:'.base64_encode(random_bytes(32));
    $_SERVER['APP_KEY'] = $key;
    $_ENV['APP_KEY']    = $key;
    putenv('APP_KEY='.$key);
}
