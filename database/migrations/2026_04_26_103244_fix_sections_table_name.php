<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sections', function (Blueprint $table) {
            // Make original 'name' column nullable because we are using 'section_name'
            // or we could just use 'name' and drop 'section_name'.
            // For now, let's just make 'name' nullable to avoid the 1364 error.
            $table->string('name')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('sections', function (Blueprint $table) {
            $table->string('name')->nullable(false)->change();
        });
    }
};
