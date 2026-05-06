<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment('Find your perfect PG today.');
})->purpose('Display an inspiring quote');
