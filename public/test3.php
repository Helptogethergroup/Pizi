<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
header('Content-Type: application/json');
echo json_encode(['after_bootstrap' => true]);