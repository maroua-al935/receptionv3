<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasColumn('visits', 'wilaya')) {
            DB::statement('ALTER TABLE `visits` MODIFY `wilaya` TEXT NULL');
        }
    }

    public function down()
    {
        if (Schema::hasColumn('visits', 'wilaya')) {
            DB::statement('ALTER TABLE `visits` MODIFY `wilaya` VARCHAR(255) NULL');
        }
    }
};
