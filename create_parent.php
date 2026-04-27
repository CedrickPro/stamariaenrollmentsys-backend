<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
$u = new App\Models\User();
$u->name = 'ParentTest';
$u->username = 'parenttest';
$u->password = bcrypt('admin123');
$u->role = 'parent';
$u->email = 'parent@test.com';
$u->save();
echo "CREATED_PARENT:parenttest\n";
