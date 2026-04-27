<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add missing columns to users
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'contact')) {
                $table->string('contact')->nullable()->after('email');
            }
            if (!Schema::hasColumn('users', 'status')) {
                $table->enum('status', ['active', 'inactive', 'locked'])->default('active');
            }
            if (!Schema::hasColumn('users', 'is_locked')) {
                $table->boolean('is_locked')->default(false);
            }
            if (!Schema::hasColumn('users', 'gender')) {
                $table->string('gender')->nullable();
            }
        });

        // Add year_label and is_active to school_years if not present
        Schema::table('school_years', function (Blueprint $table) {
            if (!Schema::hasColumn('school_years', 'year_label')) {
                $table->string('year_label')->nullable();
            }
            if (!Schema::hasColumn('school_years', 'is_active')) {
                $table->boolean('is_active')->default(false);
            }
        });

        // Add section_name + status to sections
        Schema::table('sections', function (Blueprint $table) {
            if (!Schema::hasColumn('sections', 'section_name')) {
                $table->string('section_name')->nullable();
            }
            if (!Schema::hasColumn('sections', 'status')) {
                $table->enum('status', ['available', 'unavailable', 'full'])->default('available');
            }
        });

        // Add extra fields to students
        Schema::table('students', function (Blueprint $table) {
            if (!Schema::hasColumn('students', 'grade_level')) {
                $table->string('grade_level')->nullable();
            }
            if (!Schema::hasColumn('students', 'previous_average')) {
                $table->decimal('previous_average', 5, 2)->nullable();
            }
            if (!Schema::hasColumn('students', 'preferred_adviser')) {
                $table->string('preferred_adviser')->nullable();
            }
        });

        // Feedbacks
        if (!Schema::hasTable('feedbacks')) {
            Schema::create('feedbacks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->string('subject');
                $table->text('message');
                $table->enum('status', ['pending', 'read', 'resolved'])->default('pending');
                $table->timestamps();
            });
        }

        // Notifications
        if (!Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->string('title');
                $table->text('message');
                $table->boolean('is_read')->default(false);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('feedbacks');
    }
};
