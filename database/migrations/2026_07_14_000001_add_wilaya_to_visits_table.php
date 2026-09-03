<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('visits', function (Blueprint $table) {
            if (!Schema::hasColumn('visits', 'wilaya')) {
                $table->string('wilaya')->nullable()->after('subject');
            }
        });
    }

    public function down()
    {
        Schema::table('visits', function (Blueprint $table) {
            if (Schema::hasColumn('visits', 'wilaya')) {
                $table->dropColumn('wilaya');
            }
        });
    }
};
