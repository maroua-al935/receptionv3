<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    public function up()
    {
        DB::table('profiles')->updateOrInsert(
            ['id' => 10],
            ['name' => "Agent Accueil fiscal", 'privilege' => 10]
        );

        DB::table('users')->updateOrInsert(
            ['username' => 'agent_fiscal'],
            [
                'name' => 'agent_fiscal',
                'username' => 'agent_fiscal',
                'email' => 'agent_fiscal@visilog.local',
                'firstname' => 'Agent',
                'lastname' => 'Accueil fiscal',
                'profile' => 10,
                'password' => Hash::make('Visilog@2026'),
            ]
        );

        $fiscalGroupId = DB::table('groups')
            ->where('group_name', 'like', '%Fiscal%')
            ->value('id');

        if ($fiscalGroupId) {
            $user = DB::table('users')->where('username', 'agent_fiscal')->first();
            if ($user) {
                DB::table('user_groups')->updateOrInsert(
                    ['a_user' => $user->id, 'a_group' => $fiscalGroupId],
                    ['a_user' => $user->id, 'a_group' => $fiscalGroupId]
                );
            }
        }
    }

    public function down()
    {
        DB::table('users')->where('username', 'agent_fiscal')->delete();
        DB::table('profiles')->where('id', 10)->delete();
    }
};
