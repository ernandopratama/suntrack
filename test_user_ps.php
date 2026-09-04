<?php

use App\Models\User;
use Illuminate\Contracts\Console\Kernel;

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

var_dump(User::first()?->toArray());
