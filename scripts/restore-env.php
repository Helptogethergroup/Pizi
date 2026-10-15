<?php
// Runs on every Hostinger deploy (composer post-autoload-dump). It must only
// replace .env when it is CERTAIN whether this is the test or the live deploy;
// a wrong guess puts a site on the other site's database. When unsure it does
// nothing, so the .env that is already in place keeps working.
$home = getenv('HOME');
if (!$home) {
    exit(0);
}

$cwd = getcwd();

// Every "url = ..." line of the clone's git config, credentials stripped.
$urls = [];
$gitConfig = $cwd . '/.git/config';
if (is_readable($gitConfig)) {
    preg_match_all('/^\s*url\s*=\s*(\S+)/mi', (string) file_get_contents($gitConfig), $m);
    foreach ($m[1] as $u) {
        $urls[] = preg_replace('#//[^/@]*@#', '//', $u);
    }
}

// Repo name = last path segment of each remote url.
$repos = array_map(fn ($u) => strtolower(preg_replace('#\.git$#i', '', basename($u))), $urls);
$fromBeta = in_array('pizi-beta', $repos, true);
$fromLive = in_array('pizi', $repos, true);

$target = null;
if (strpos($cwd, DIRECTORY_SEPARATOR . 'public_html' . DIRECTORY_SEPARATOR . 'test') !== false) {
    $target = 'test';
} elseif ($fromBeta && !$fromLive) {
    $target = 'test';
} elseif ($fromLive && !$fromBeta) {
    $target = 'live';
}

@file_put_contents(
    $home . '/env-store/restore-env.log',
    date('c') . " cwd=$cwd remotes=[" . implode(', ', $urls) . "] -> " . ($target ? "$target.env" : 'UNSURE, .env left untouched') . "\n",
    FILE_APPEND
);

if ($target) {
    $source = $home . '/env-store/' . $target . '.env';
    if (file_exists($source)) {
        copy($source, $cwd . '/.env');
    }
}
