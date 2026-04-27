<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('section_id')->constrained('sections')->onDelete('cascade');
            $table->foreignId('school_year_id')->constrained('school_years')->onDelete('cascade');
            $table->enum('status', ['pending', 'enrolled', 'rejected', 'dropped', 'transferred_in', 'transferred_out', 'not_active'])->default('pending');
            $table->timestamp('enrolled_at')->nullable();
            $table->timestamps();
        });

        Schema::create('attendance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('section_id')->constrained('sections')->onDelete('cascade');
            $table->date('date');
            $table->enum('status', ['present', 'absent', 'late'])->default('present');
            $table->timestamps();
        });

        Schema::create('grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');
            $table->foreignId('school_year_id')->constrained('school_years')->onDelete('cascade');
            $table->integer('q1')->nullable();
            $table->integer('q2')->nullable();
            $table->integer('q3')->nullable();
            $table->integer('q4')->nullable();
            $table->integer('final_rating')->nullable();
            $table->string('remarks')->nullable(); // PROMOTED / RETAINED
            $table->timestamps();
        });

        Schema::create('behavior_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('school_year_id')->constrained('school_years')->onDelete('cascade');
            $table->string('core_value');
            $table->string('behavior_statement');
            $table->enum('q1', ['AO', 'SO', 'RO', 'NO'])->nullable();
            $table->enum('q2', ['AO', 'SO', 'RO', 'NO'])->nullable();
            $table->enum('q3', ['AO', 'SO', 'RO', 'NO'])->nullable();
            $table->enum('q4', ['AO', 'SO', 'RO', 'NO'])->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('behavior_ratings');
        Schema::dropIfExists('grades');
        Schema::dropIfExists('attendance');
        Schema::dropIfExists('enrollments');
    }
};
