<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('civil_servants', function (Blueprint $table) {
            // Index for the most common filter (status_type_id = 1)
            $table->index('status_type_id');
            // Index for department filtering
            $table->index('department_id');
            // Index for position filtering
            $table->index('position_id');
            // Index for gender aggregation
            $table->index('gender_id');
            // Composite index for common filter combination
            $table->index(['status_type_id', 'department_id']);
            // Index for name search
            $table->index('last_name_kh');
            $table->index('first_name_kh');
        });

        Schema::table('departments', function (Blueprint $table) {
            // Index for parent lookups and active filtering
            $table->index(['parent_id', 'active']);
            $table->index('active');
        });

        Schema::table('positions', function (Blueprint $table) {
            $table->index('active');
            $table->index('sort');
        });

        Schema::table('images', function (Blueprint $table) {
            // Index for the civil_servant_id FK lookup
            $table->index('civil_servant_id');
        });
    }

    public function down(): void
    {
        Schema::table('civil_servants', function (Blueprint $table) {
            $table->dropIndex(['status_type_id']);
            $table->dropIndex(['department_id']);
            $table->dropIndex(['position_id']);
            $table->dropIndex(['gender_id']);
            $table->dropIndex(['status_type_id', 'department_id']);
            $table->dropIndex(['last_name_kh']);
            $table->dropIndex(['first_name_kh']);
        });

        Schema::table('departments', function (Blueprint $table) {
            $table->dropIndex(['parent_id', 'active']);
            $table->dropIndex(['active']);
        });

        Schema::table('positions', function (Blueprint $table) {
            $table->dropIndex(['active']);
            $table->dropIndex(['sort']);
        });

        Schema::table('images', function (Blueprint $table) {
            $table->dropIndex(['civil_servant_id']);
        });
    }
};
