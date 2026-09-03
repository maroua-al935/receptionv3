<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\ant_user;
use App\Models\antennes;

class HistoryController extends Controller
{
    public function get_index()
    {
        $profile = (int) Auth::guard('web')->user()->profile;

        if (!in_array($profile, [1, 2, 3], true)) {
            abort(403, 'Acces history reserve aux profils Admin, President et Superviseur.');
        }

        switch($profile)
        {
           case 1:
            return $this->index_2();
            break;
            case 2:
            return $this->index_2();
            break;
           case 3:
            return $this->index_2();
            break;
        }
    }
     public function index_6()
    { 
        $ant_id=ant_user::where('ant_user','=',Auth::guard('web')->user()->id)->get();
        $ant_loc=antennes::where('id','=',$ant_id[0]['ant_group'])->get();

        return view('Antenne_reception.history')->with('url','history')->with('loc',$ant_loc[0]['antenne_name']);
    }   
    public function index_5()
    {
        return view('Reception.history')->with('url','history');
    }
    public function index_4()
    {
        return view('Service.history')->with('url','history');
    }
    public function index_2()
    {
        return view('President.history')->with('url','history');
    }
    public function get_ant_history()
    {
        return view('President.ant_history')->with('url','history_ant');
    }

    private function isServiceAssignmentAgent(): bool
    {
        $user = Auth::guard('web')->user();

        return $user && (
            $user->name === 'agent_accueil_service'
            || $user->email === 'agent.accueil.service@visilog.local'
        );
    }

    private function isFiscalAssignmentAgent(): bool
    {
        $user = Auth::guard('web')->user();

        return $user && (
            $user->name === 'agent_fiscal'
            || $user->email === 'agent_fiscal@visilog.local'
        );
    }

}
