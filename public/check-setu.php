<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$exists = class_exists('App\Services\SetuKycService');
echo $exists ? 'SetuKycService EXISTS ✅' : 'SetuKycService NOT FOUND ❌';

$exists2 = class_exists('App\Services\SetuESignService');
echo '<br>';
echo $exists2 ? 'SetuESignService EXISTS ✅' : 'SetuESignService NOT FOUND ❌';