<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('visits:purge-deleted', function () {
    $before = Carbon::now()->subDays(30);
    $visitIds = DB::table('visits')
        ->where('is_deleted', 1)
        ->whereNotNull('deleted_at')
        ->where('deleted_at', '<=', $before)
        ->pluck('id');

    if ($visitIds->isNotEmpty() && DB::getSchemaBuilder()->hasTable('visit_audits')) {
        DB::table('visit_audits')->whereIn('visit_id', $visitIds)->delete();
    }

    $visitsDeleted = DB::table('visits')
        ->where('is_deleted', 1)
        ->whereNotNull('deleted_at')
        ->where('deleted_at', '<=', $before)
        ->delete();
    $antenneVisitsDeleted = DB::table('antenne_visits')
        ->where('is_deleted', 1)
        ->whereNotNull('deleted_at')
        ->where('deleted_at', '<=', $before)
        ->delete();

    $this->info("Visites siege supprimees definitivement : {$visitsDeleted}");
    $this->info("Visites antennes supprimees definitivement : {$antenneVisitsDeleted}");
})->purpose('Purge les visites supprimees depuis plus de 30 jours');
