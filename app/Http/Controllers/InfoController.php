<?php

namespace App\Http\Controllers;

use App\Models\visitors as visitor;
use App\Models\visits as visit;
use App\Models\ant_visits;
use App\Models\attachments as attach;
use App\Models\organisations as organisation;
use Illuminate\Validation\Rules\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;


class InfoController extends Controller
{
    public function info_index($id) {

        $data=visit::leftjoin('visitors','visits.visitor','=','visitors.id')
                    ->leftjoin('organisations','visits.organization','=','organisations.id')
                    ->leftjoin('groups','groups.id','=','visits.service_emp_visited')
                    ->leftjoin('users','users.id','=','visits.emp_visited')
                    ->leftjoin('users as validators','validators.id','=','visits.validation_by')
                    ->leftjoin('attachments','visitors.attachment','=','attachments.id')
                    ->leftjoin('positions','visitors.position','=','positions.id')
                    ->leftjoin('id_types','visitors.id_type','=','id_types.id')
                    ->leftjoin('categories','visits.category','=','categories.id')
                    ->where('visits.id','=',$id)
                    ->get([
                        'visits.id as visit_id',
                        'visits.visitor as visitor_id',
                        'visits.organization as organisation_id',
                        'visits.category as category_id',
                        'visits.emp_visited as emp_visited_id',
                        'visits.service_emp_visited as service_id',
                        'visits.wilaya as wilaya',
                        'visits.status',
                        'visits.hashost',
                        'visits.subject',
                        'visits.is_deleted as visit_is_deleted',
                        'visits.entry_date',
                        'visits.exit_date',
                        'visits.validation_time',
                        'visits.accept_time',
                        'visits.sendup_time',
                        'visits.validation_by',
                        'visits.badge_n',
                        'visits.observations',
                        'visitors.id as visitor_id_ref',
                        'visitors.firstname as firstname',
                        'visitors.lastname as lastname',
                        'visitors.created_at as visitor_created_at',
                        'visitors.updated_at as visitor_updated_at',
                        'visitors.nin',
                        'visitors.position as visitor_position_id',
                        'organisations.name as organisation',
                        'positions.id as position_id',
                        'positions.name as position',
                        'attachments.id as attachment_id',
                        'attachments.filepath as filepath',
                        'cin',
                        'id_types.id as id_type_id',
                        'id_types.name as id_type',
                        'categories.name as category',
                        'users.name as usrname',
                        'users.username as user_username',
                        'users.firstname as user_firstname',
                        'users.lastname as user_lastname',
                        'users.email as user_email',
                        'users.phone as user_phone',
                        'groups.id as group_id',
                        'groups.group_name as service',
                        'groups.group_full_dn as service_full_dn',
                        'groups.group_dn as service_dn',
                        'groups.group_superior as service_superior',
                        'groups.group_superior_alt as service_superior_alt',
                        'validators.name as validation_by_name',
                        'validators.username as validation_by_username',
                        'validators.firstname as validation_by_firstname',
                        'validators.lastname as validation_by_lastname',
                    ]);
        if ($data->isNotEmpty()) {
            $visit = $data[0];
            if (empty($visit->position) && !empty($visit->position_id)) {
                $visit->position = DB::table('positions')->where('id', '=', $visit->position_id)->value('name') ?? $visit->position;
            }
            if (empty($visit->position) && isset($visit->visitor_position_id)) {
                $visit->position = DB::table('positions')->where('id', '=', $visit->visitor_position_id)->value('name') ?? $visit->position;
            }
        }
        if ($data->isEmpty()) {
            abort(404);
        }
        if (in_array((int) Auth::guard('web')->user()->profile, [4, 10], true) && !$this->canServiceUserViewVisit($data[0])) {
            abort(403);
        }

        $audits = collect();
        if (Schema::hasTable('visit_audits')) {
            $audits = DB::table('visit_audits')
                ->leftJoin('users', 'users.id', '=', 'visit_audits.changed_by')
                ->leftJoin('profiles', 'profiles.id', '=', 'visit_audits.profile_id')
                ->where('visit_audits.visit_id', '=', $id)
                ->orderByDesc('visit_audits.created_at')
                ->get([
                    'visit_audits.action',
                    'visit_audits.old_values',
                    'visit_audits.new_values',
                    'visit_audits.created_at',
                    'users.name as changed_by_name',
                    'profiles.name as profile_name',
                ])
                ->map(function ($audit) {
                    $audit->old_values = $audit->old_values ? json_decode($audit->old_values, true) : [];
                    $audit->new_values = $audit->new_values ? json_decode($audit->new_values, true) : [];
                    return $audit;
                });

            $userIds = $audits->flatMap(function ($audit) {
                return [
                    $audit->old_values['emp_visited'] ?? null,
                    $audit->new_values['emp_visited'] ?? null,
                    $audit->old_values['validation_by'] ?? null,
                    $audit->new_values['validation_by'] ?? null,
                ];
            })->filter()->unique()->values();

            $serviceIds = $audits->flatMap(function ($audit) {
                return [
                    $audit->old_values['service_emp_visited'] ?? null,
                    $audit->new_values['service_emp_visited'] ?? null,
                ];
            })->filter()->unique()->values();

            $userNames = $userIds->isEmpty()
                ? collect()
                : DB::table('users')
                    ->whereIn('id', $userIds)
                    ->get(['id', 'name', 'firstname', 'lastname'])
                    ->mapWithKeys(function ($user) {
                        $fullName = trim(($user->firstname ?? '') . ' ' . ($user->lastname ?? ''));
                        return [$user->id => ($fullName !== '' ? $fullName : $user->name)];
                    });

            $serviceNames = $serviceIds->isEmpty()
                ? collect()
                : DB::table('groups')
                    ->whereIn('id', $serviceIds)
                    ->pluck('group_name', 'id');

            $audits = $audits->map(function ($audit) use ($userNames, $serviceNames) {
                $audit->old_values = $this->formatAuditValues($audit->old_values, $userNames, $serviceNames);
                $audit->new_values = $this->formatAuditValues($audit->new_values, $userNames, $serviceNames);
                return $audit;
            });
        }

        $profile = (int) Auth::guard('web')->user()->profile;
        $viewName = 'info_index';
        if (in_array($profile, [1, 2, 3], true)) {
            $viewName = 'President.info_index';
        } elseif (in_array($profile, [4, 8, 9, 10], true)) {
            $viewName = 'Service.info_index';
        } elseif ($profile === 5) {
            $viewName = 'Reception.info_index';
        }

        if (request()->boolean('embed')) {
            return view('partials.visit-info-modern')
                ->with('url', 'guest')
                ->with('data', $data)
                ->with('audits', $audits)
                ->with('title', $data[0]->firstname . ' ' . $data[0]->lastname)
                ->with('subtitle', 'Details du visiteur et informations de passage.')
                ->with('embedded', true);
        }

        return view($viewName)->with('url','guest')->with('data',$data)->with('audits', $audits);
    }

    private function canServiceUserViewVisit($visit): bool
    {
        $user = Auth::guard('web')->user();
        if (!$user) {
            return false;
        }

        if ((int) ($visit->emp_visited_id ?? 0) === (int) $user->id) {
            return true;
        }

        if ((int) $user->profile === 4 && $visit->service_id) {
            return DB::table('user_groups')
                ->where('a_user', '=', $user->id)
                ->where('a_group', '=', $visit->service_id)
                ->exists();
        }

        if ($this->isServiceAssignmentAgent() && (int) $visit->service_id === $this->serviceAssignmentGroupId()) {
            return true;
        }

        if ($this->isFiscalAssignmentAgent() && (int) $visit->service_id === $this->fiscalAssignmentGroupId()) {
            return true;
        }

        if (!$visit->service_id || !Schema::hasColumn('user_groups', 'is_head')) {
            return false;
        }

        return DB::table('user_groups')
            ->where('a_user', '=', $user->id)
            ->where('a_group', '=', $visit->service_id)
            ->where('is_head', '=', 1)
            ->exists();
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

    private function serviceAssignmentGroupId(): int
    {
        return 19;
    }

    private function fiscalAssignmentGroupId(): int
    {
        return (int) (DB::table('groups')->where('group_name', 'like', '%Fiscal%')->value('id')
            ?: DB::table('groups')->where('group_name', 'like', '%DFC%')->value('id'));
    }

    public function ant_info_index($id) {
        $query=ant_visits::leftjoin('antenne_visitors','antenne_visits.visitor','=','antenne_visitors.id')
                    ->leftjoin('organisations','antenne_visits.organization','=','organisations.id')
                    ->leftjoin('antennes','antennes.id','=','antenne_visits.ant_location')
                    ->leftjoin('users','users.id','=','antenne_visits.emp_visited')
                    ->leftjoin('attachments','antenne_visitors.attachment','=','attachments.id')
                    ->leftjoin('positions','antenne_visitors.position','=','positions.id')
                    ->leftjoin('id_types','antenne_visitors.id_type','=','id_types.id')
                    ->leftjoin('categories','antenne_visits.category','=','categories.id')
                    ->where('antenne_visits.id','=',$id);

        if (in_array((int) Auth::guard('web')->user()->profile, [6, 7], true)) {
            $antUser = DB::table('antenne_users')->where('ant_user', '=', Auth::guard('web')->user()->id)->first();
            if (!$antUser) {
                abort(403);
            }
            $query->where('antenne_visits.ant_location', '=', $antUser->ant_group);
        }

        $data=$query->get(['antenne_visitors.firstname as firstname','antenne_visitors.lastname as lastname',
                        'organisations.name as organisation',
                        'positions.name as position',
                        'attachments.filepath as filepath','cin',
                        'id_types.name as id_type',
                        'categories.name as category',
                        'entry_date','exit_date',
                        'users.name as usrname','antennes.antenne_name as service',
                        'status','hashost','subject']);
                    if ($data->isEmpty()) {
                        abort(404);
                    }
                    $data[0]->filepath=preg_replace('/public/','storage',$data[0]->filepath);
        return view('ant_info_index')->with('url','guest')->with('data',$data);
    }
    public function ant_info_index_p($id) {
        $data=ant_visits::leftjoin('antenne_visitors','antenne_visits.visitor','=','antenne_visitors.id')
                    ->leftjoin('organisations','antenne_visits.organization','=','organisations.id')
                    ->leftjoin('antennes','antennes.id','=','antenne_visits.ant_location')
                    ->leftjoin('users','users.id','=','antenne_visits.emp_visited')
                    ->leftjoin('attachments','antenne_visitors.attachment','=','attachments.id')
                    ->leftjoin('positions','antenne_visitors.position','=','positions.id')
                    ->leftjoin('id_types','antenne_visitors.id_type','=','id_types.id')
                    ->leftjoin('categories','antenne_visits.category','=','categories.id')
                    ->where('antenne_visits.id','=',$id)
                    ->get(['antenne_visitors.firstname as firstname','antenne_visitors.lastname as lastname',
                        'organisations.name as organisation',
                        'positions.name as position',
                        'attachments.filepath as filepath','cin',
                        'id_types.name as id_type',
                        'categories.name as category',
                        'entry_date','exit_date',
                        'users.name as usrname','antennes.antenne_name as service',
                        'status','hashost','subject']);
                    $data[0]->filepath=preg_replace('/public/','storage',$data[0]->filepath);
        return view('President.ant_info_index')->with('url','guest_ant')->with('data',$data);
    }

    public function all_ant_info_index($id) {
        $data=ant_visits::leftjoin('antenne_visitors','antenne_visits.visitor','=','antenne_visitors.id')
                    ->leftjoin('organisations','antenne_visits.organization','=','organisations.id')
                    ->leftjoin('antennes','antennes.id','=','antenne_visits.ant_location')
                    ->leftjoin('users','users.id','=','antenne_visits.emp_visited')
                    ->leftjoin('attachments','antenne_visitors.attachment','=','attachments.id')
                    ->leftjoin('positions','antenne_visitors.position','=','positions.id')
                    ->leftjoin('id_types','antenne_visitors.id_type','=','id_types.id')
                    ->leftjoin('categories','antenne_visits.category','=','categories.id')
                    ->where('antenne_visits.id','=',$id)
                    ->get(['antenne_visitors.firstname as firstname','antenne_visitors.lastname as lastname',
                        'organisations.name as organisation',
                        'positions.name as position',
                        'attachments.filepath as filepath','cin',
                        'id_types.name as id_type',
                        'categories.name as category',
                        'entry_date','exit_date',
                        'users.name as usrname','antennes.antenne_name as service',
                        'status','hashost','subject']);
                    $data[0]->filepath=preg_replace('/public/','storage',$data[0]->filepath);
        return view('President.all_ant_info_index')->with('url','history_ant')->with('data',$data);
    }



    private function formatAuditValues(array $values, $userNames, $serviceNames): array
    {
        $labels = [
            'service_emp_visited' => 'Service visite',
            'emp_visited' => 'Personne visitee',
            'validation_by' => 'Valide par',
            'category' => 'Categorie',
            'organization' => 'Societe',
            'badge_n' => 'Numero badge',
            'status' => 'Statut',
            'entry_date' => 'Date entree',
            'exit_date' => 'Date sortie',
            'accept_time' => 'Date acceptation',
            'sendup_time' => 'Date renvoi',
            'visitor' => 'Visiteur',
            'subject' => 'Objet',
            'is_deleted' => 'Supprime',
        ];

        $statusLabels = [
            0 => 'En attente',
            1 => 'En cours',
            2 => 'Terminee',
            3 => 'Renvoyee',
        ];

        $formatted = [];
        foreach ($values as $key => $value) {
            if ($key === 'emp_visited' || $key === 'validation_by') {
                $value = $value ? ($userNames[$value] ?? $value) : null;
            }

            if ($key === 'service_emp_visited') {
                $value = $value ? ($serviceNames[$value] ?? $value) : null;
            }

            if ($key === 'status' && $value !== null && $value !== '') {
                $value = $statusLabels[(int) $value] ?? $value;
            }

            if ($key === 'is_deleted') {
                $value = (int) $value === 1 ? 'Oui' : 'Non';
            }

            $formatted[$labels[$key] ?? $key] = $value;
        }

        return $formatted;
    }
}
