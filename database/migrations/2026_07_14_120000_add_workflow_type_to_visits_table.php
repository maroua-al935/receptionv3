<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visits', function (Blueprint $table) {
            if (!Schema::hasColumn('visits', 'workflow_type')) {
                $table->string('workflow_type', 20)->nullable()->after('category');
            }
        });
    }

    public function down(): void
    {
        Schema::table('visits', function (Blueprint $table) {
            if (Schema::hasColumn('visits', 'workflow_type')) {
                $table->dropColumn('workflow_type');
            }
        });
    }
};
