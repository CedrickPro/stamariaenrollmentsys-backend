<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Clean up sections table: Unify 'name' and 'section_name'
        if (Schema::hasColumn('sections', 'name') && Schema::hasColumn('sections', 'section_name')) {
            // Copy data from name to section_name if section_name is null
            DB::table('sections')->whereNull('section_name')->update([
                'section_name' => DB::raw('name')
            ]);
            
            Schema::table('sections', function (Blueprint $table) {
                $table->dropColumn('name');
            });
        }

        // 2. Add reply support to feedbacks
        Schema::table('feedbacks', function (Blueprint $table) {
            if (!Schema::hasColumn('feedbacks', 'reply')) {
                $table->text('reply')->nullable();
            }
            if (!Schema::hasColumn('feedbacks', 'replied_at')) {
                $table->timestamp('replied_at')->nullable();
            }
        });

        // 3. Add type support to notifications
        Schema::table('notifications', function (Blueprint $table) {
            if (!Schema::hasColumn('notifications', 'type')) {
                $table->string('type')->nullable()->after('message');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sections', function (Blueprint $table) {
            $table->string('name')->nullable();
        });
        
        Schema::table('feedbacks', function (Blueprint $table) {
            $table->dropColumn(['reply', 'replied_at']);
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
