<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->date('birth_date')->nullable()->change();
            $table->string('birth_place')->nullable()->change();
            $table->string('gender')->nullable()->change();
            $table->string('religion')->nullable()->change();
            $table->string('mother_tongue')->nullable()->change();
            $table->string('barangay')->nullable()->change();
            $table->string('city')->nullable()->change();
            $table->string('province')->nullable()->change();
            $table->string('father_name')->nullable()->change();
            $table->string('mother_name')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->date('birth_date')->nullable(false)->change();
            // etc
        });
    }
};
