<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$rows = Illuminate\Support\Facades\DB::table('groups')->orderBy('id')->get(['id', 'group_name']);
foreach ($rows as $row) echo $row->id . ' | ' . $row->group_name . PHP_EOL;
