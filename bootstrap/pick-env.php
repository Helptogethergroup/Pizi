<?php

/**
 * Which env file this copy of the app must run with, decided at run time from
 * the folder it is actually running in — not from anything known at deploy time.
 *
 *   /home/<user>/domains/<domain>/public_html        -> ~/env-store/live.env
 *   /home/<user>/domains/<domain>/public_html/test   -> ~/env-store/test.env
 *
 * Anything else (local machine, a build folder, an unreadable env-store)
 * returns null and the normal .env is used, exactly as before.
 */
return function (string $basePath, ?callable $readable = null): ?string {
    $readable ??= fn (string $file): bool => @is_readable($file);
    $path = rtrim(str_replace('\\', '/', $basePath), '/');

    if (!preg_match('#^(/home/[^/]+)/domains/[^/]+/public_html(/test)?$#', $path, $m)) {
        return null;
    }

    $file = $m[1] . '/env-store/' . (empty($m[2]) ? 'live.env' : 'test.env');

    return $readable($file) ? $file : null;
};
