<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('feedbacks', function (Blueprint $table) {
            if (!Schema::hasColumn('feedbacks', 'rating')) {
                $table->integer('rating')->nullable();
            }
            if (!Schema::hasColumn('feedbacks', 'translation')) {
                $table->text('translation')->nullable();
            }
            if (!Schema::hasColumn('feedbacks', 'role')) {
                $table->string('role')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('feedbacks', function (Blueprint $table) {
            $table->dropColumn(['rating', 'translation', 'role']);
        });
    }
};
