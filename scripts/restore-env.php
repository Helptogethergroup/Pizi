<?php
$home = getenv('HOME');
if (!$home) {
    exit(0);
}

$cwd = getcwd();
$isTest = strpos($cwd, DIRECTORY_SEPARATOR . 'public_html' . DIRECTORY_SEPARATOR . 'test') !== false;
$source = $home . '/env-store/' . ($isTest ? 'test.env' : 'live.env');

if (file_exists($source)) {
    copy($source, getcwd() . '/.env');
}
