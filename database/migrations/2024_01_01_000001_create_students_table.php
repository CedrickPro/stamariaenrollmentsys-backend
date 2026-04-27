<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('lrn', 12)->unique();
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('suffix')->nullable();
            $table->date('birth_date');
            $table->string('birth_place');
            $table->enum('gender', ['Male', 'Female']);
            $table->string('religion');
            $table->string('mother_tongue');
            $table->string('house_no')->nullable();
            $table->string('street')->nullable();
            $table->string('barangay');
            $table->string('city');
            $table->string('province');
            $table->string('country')->default('Philippines');
            $table->string('zip_code')->nullable();
            $table->foreignId('parent_id')->constrained('users')->onDelete('cascade');
            $table->enum('status', ['pending', 'enrolled', 'rejected', 'dropped', 'transferred_in', 'transferred_out', 'not_active'])->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
