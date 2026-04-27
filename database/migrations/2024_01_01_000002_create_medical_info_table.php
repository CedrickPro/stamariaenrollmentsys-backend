<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medical_info', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->json('diagnosis')->nullable(); // Array of conditions
            $table->json('manifestations')->nullable();
            $table->boolean('pwd_id')->default(false);
            $table->string('pwd_details')->nullable();
            $table->decimal('weight', 5, 2)->nullable();
            $table->decimal('height', 5, 2)->nullable();
            $table->decimal('height_squared', 5, 2)->nullable();
            $table->decimal('bmi_result', 5, 2)->nullable();
            $table->string('bmi_category')->nullable();
            $table->string('hfa')->nullable(); // Height-for-Age
            $table->text('medical_remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medical_info');
    }
};
