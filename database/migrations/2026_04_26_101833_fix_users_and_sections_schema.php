<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Make email nullable and add missing user columns
        Schema::table('users', function (Blueprint $table) {
            // Make email nullable (users can be created without email)
            $table->string('email')->nullable()->change();

            // Add profile picture support
            if (!Schema::hasColumn('users', 'profile_pic')) {
                $table->longText('profile_pic')->nullable()->after('status');
            }

            // Add online status
            if (!Schema::hasColumn('users', 'online_status')) {
                $table->enum('online_status', ['online', 'offline'])->default('offline')->after('profile_pic');
            }

            // Add first/last name columns if missing
            if (!Schema::hasColumn('users', 'first_name')) {
                $table->string('first_name')->nullable()->after('name');
            }
            if (!Schema::hasColumn('users', 'last_name')) {
                $table->string('last_name')->nullable()->after('first_name');
            }
            if (!Schema::hasColumn('users', 'middle_name')) {
                $table->string('middle_name')->nullable()->after('last_name');
            }
            if (!Schema::hasColumn('users', 'suffix')) {
                $table->string('suffix')->nullable()->after('middle_name');
            }
        });

        // 2. Make school_year_id nullable in sections (so sections can be created without one)
        Schema::table('sections', function (Blueprint $table) {
            $table->unsignedBigInteger('school_year_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable(false)->change();
        });
        Schema::table('sections', function (Blueprint $table) {
            $table->unsignedBigInteger('school_year_id')->nullable(false)->change();
        });
    }
};
