<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
$user = User::where('username', 'admin')->first();
if ($user) {
    echo "User: " . $user->username . "\n";
    echo "Profile Pic Length: " . strlen($user->profile_pic) . "\n";
    echo "First 50 chars: " . substr($user->profile_pic, 0, 50) . "\n";
} else {
    echo "User not found\n";
}
