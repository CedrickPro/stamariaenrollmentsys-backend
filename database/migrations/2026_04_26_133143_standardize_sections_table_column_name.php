<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sections', function (Blueprint $table) {
            if (Schema::hasColumn('sections', 'section_name')) {
                $table->dropColumn('section_name');
            }
            // Ensure 'name' is not nullable and is the primary name column
            $table->string('name')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('sections', function (Blueprint $table) {
            $table->string('section_name')->nullable();
            $table->string('name')->nullable()->change();
        });
    }
};
