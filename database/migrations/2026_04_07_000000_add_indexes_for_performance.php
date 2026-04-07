<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('civil_servants', function (Blueprint $table) {
            $table->index('department_id', 'cs_department_id_idx');
            $table->index('position_id', 'cs_position_id_idx');
            $table->index('status_type_id', 'cs_status_type_id_idx');
        });

        Schema::table('images', function (Blueprint $table) {
            $table->index('civil_servant_id', 'images_civil_servant_id_idx');
        });
    }

    public function down(): void
    {
        Schema::table('civil_servants', function (Blueprint $table) {
            $table->dropIndex('cs_department_id_idx');
            $table->dropIndex('cs_position_id_idx');
            $table->dropIndex('cs_status_type_id_idx');
        });

        Schema::table('images', function (Blueprint $table) {
            $table->dropIndex('images_civil_servant_id_idx');
        });
    }
};
