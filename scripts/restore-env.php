<?php
$home = getenv('HOME');
if (!$home) {
    exit(0);
}

$cwd = getcwd();

// Hostinger runs composer in a temporary build folder, so the folder name alone
// can't tell test from live. Also look at which repo this deploy was cloned
// from: test deploys from the "pizi-beta" repo, live from "Pizi".
$remote = '';
$gitConfig = $cwd . '/.git/config';
if (is_readable($gitConfig)) {
    $remote = (string) file_get_contents($gitConfig);
}

$isTest = strpos($cwd, DIRECTORY_SEPARATOR . 'public_html' . DIRECTORY_SEPARATOR . 'test') !== false
    || stripos($remote, 'pizi-beta') !== false;
$source = $home . '/env-store/' . ($isTest ? 'test.env' : 'live.env');

// A line per deploy so a wrong pick can be diagnosed afterwards.
@file_put_contents(
    $home . '/env-store/restore-env.log',
    date('c') . " cwd=$cwd remote_has_beta=" . (stripos($remote, 'pizi-beta') !== false ? 'yes' : 'no') . " -> " . basename($source) . "\n",
    FILE_APPEND
);

if (file_exists($source)) {
    copy($source, $cwd . '/.env');
}
