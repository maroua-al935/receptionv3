<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('visits', 'deleted_at')) {
            Schema::table('visits', function (Blueprint $table) {
                $table->timestamp('deleted_at')->nullable()->index()->after('is_deleted');
            });
        }

        if (!Schema::hasColumn('antenne_visits', 'deleted_at')) {
            Schema::table('antenne_visits', function (Blueprint $table) {
                $table->timestamp('deleted_at')->nullable()->index()->after('is_deleted');
            });
        }

        DB::table('visits')->where('is_deleted', 1)->whereNull('deleted_at')->update(['deleted_at' => now()]);
        DB::table('antenne_visits')->where('is_deleted', 1)->whereNull('deleted_at')->update(['deleted_at' => now()]);
    }

    public function down()
    {
        if (Schema::hasColumn('visits', 'deleted_at')) {
            Schema::table('visits', fn (Blueprint $table) => $table->dropColumn('deleted_at'));
        }

        if (Schema::hasColumn('antenne_visits', 'deleted_at')) {
            Schema::table('antenne_visits', fn (Blueprint $table) => $table->dropColumn('deleted_at'));
        }
    }
};
