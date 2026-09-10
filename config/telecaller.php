<?php

return [
    // Used on the telecaller dashboard's daily progress bar, and as the
    // admin monitoring page's fallback when a telecaller has no
    // per-person target set on their user record (users.daily_call_target).
    'default_daily_call_target' => env('TELECALLER_DEFAULT_DAILY_CALL_TARGET', 20),
];
