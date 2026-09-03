<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Models\User;
use App\Models\profiles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = User::query()->search($request->search);

        if (Schema::hasColumn('user_groups', 'is_head') && Schema::hasColumn('antenne_users', 'is_head')) {
            $query->when($request->head_status === 'yes', function ($query) {
                $query->where(function ($query) {
                    $query->whereExists(function ($subQuery) {
                        $subQuery->select(DB::raw(1))
                            ->from('user_groups')
                            ->whereColumn('user_groups.a_user', 'users.id')
                            ->where('user_groups.is_head', 1);
                    })->orWhereExists(function ($subQuery) {
                        $subQuery->select(DB::raw(1))
                            ->from('antenne_users')
                            ->whereColumn('antenne_users.ant_user', 'users.id')
                            ->where('antenne_users.is_head', 1);
                    });
                });
            })->when($request->head_status === 'no', function ($query) {
                $query->whereNotExists(function ($subQuery) {
                    $subQuery->select(DB::raw(1))
                        ->from('user_groups')
                        ->whereColumn('user_groups.a_user', 'users.id')
                        ->where('user_groups.is_head', 1);
                })->whereNotExists(function ($subQuery) {
                    $subQuery->select(DB::raw(1))
                        ->from('antenne_users')
                        ->whereColumn('antenne_users.ant_user', 'users.id')
                        ->where('antenne_users.is_head', 1);
                });
            })->when($request->head_type === 'service', function ($query) {
                $query->whereExists(function ($subQuery) {
                    $subQuery->select(DB::raw(1))
                        ->from('user_groups')
                        ->whereColumn('user_groups.a_user', 'users.id')
                        ->where('user_groups.is_head', 1);
                });
            })->when($request->head_type === 'antenne', function ($query) {
                $query->whereExists(function ($subQuery) {
                    $subQuery->select(DB::raw(1))
                        ->from('antenne_users')
                        ->whereColumn('antenne_users.ant_user', 'users.id')
                        ->where('antenne_users.is_head', 1);
                });
            });
        }

        $users = $query
            ->paginate(10)
            ->withQueryString();
        $userIds = $users->getCollection()->pluck('id');
        $serviceRows = DB::table('user_groups')
            ->leftJoin('groups', 'groups.id', '=', 'user_groups.a_group')
            ->whereIn('user_groups.a_user', $userIds)
            ->orderBy('groups.group_name')
            ->get(['user_groups.a_user', 'groups.group_name', 'user_groups.is_head'])
            ->groupBy('a_user');
        $antenneRows = DB::table('antenne_users')
            ->leftJoin('antennes', 'antennes.id', '=', 'antenne_users.ant_group')
            ->whereIn('antenne_users.ant_user', $userIds)
            ->orderBy('antennes.antenne_name')
            ->get(['antenne_users.ant_user', 'antennes.antenne_name', 'antenne_users.is_head'])
            ->groupBy('ant_user');

        $users->getCollection()->transform(function ($user) use ($serviceRows, $antenneRows) {
            $user->service_labels = ($serviceRows[$user->id] ?? collect())->map(function ($row) {
                return $row->group_name . ((int) ($row->is_head ?? 0) === 1 ? ' (Chef)' : '');
            })->implode(', ');
            $user->antenne_labels = ($antenneRows[$user->id] ?? collect())->map(function ($row) {
                return $row->antenne_name . ((int) ($row->is_head ?? 0) === 1 ? ' (Chef)' : '');
            })->implode(', ');
            $user->head_labels = collect([
                ($serviceRows[$user->id] ?? collect())->filter(fn ($row) => (int) ($row->is_head ?? 0) === 1)->map(fn ($row) => 'Service: ' . $row->group_name),
                ($antenneRows[$user->id] ?? collect())->filter(fn ($row) => (int) ($row->is_head ?? 0) === 1)->map(fn ($row) => 'Antenne: ' . $row->antenne_name),
            ])->flatten()->implode(', ');

            return $user;
        });
        $url = 'users';
        $groups = DB::table('groups')->orderBy('group_name')->get(['id', 'group_name']);
        $antennes = DB::table('antennes')->orderBy('antenne_name')->get(['id', 'antenne_name']);

        return view('users.index', compact('users', 'url', 'groups', 'antennes'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $url = 'users';
        $profiles = profiles::all();
        $groups = DB::table('groups')->orderBy('group_name')->get(['id', 'group_name']);
        $antennes = DB::table('antennes')->orderBy('antenne_name')->get(['id', 'antenne_name']);
        $selectedGroups = collect();
        $headGroups = collect();
        $selectedAntennes = collect();
        $headAntennes = collect();

        return view('users.form', compact('profiles', 'url', 'groups', 'antennes', 'selectedGroups', 'headGroups', 'selectedAntennes', 'headAntennes'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\UserRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(UserRequest $request)
    {
        $data = $request->validated();
        $data['name'] = trim(($data['firstname'] ?? '') . ' ' . ($data['lastname'] ?? ''));
        $services = collect($data['services'] ?? [])->map(fn ($id) => (int) $id)->unique();
        $headServices = collect($data['head_services'] ?? [])->map(fn ($id) => (int) $id)->intersect($services)->unique();
        $antennes = collect($data['antennes'] ?? [])->map(fn ($id) => (int) $id)->unique();
        $headAntennes = collect($data['head_antennes'] ?? [])->map(fn ($id) => (int) $id)->intersect($antennes)->unique();
        unset($data['services'], $data['head_services'], $data['antennes'], $data['head_antennes']);
        $data['password'] = Hash::make($data['password']);

        $user = User::create($data);
        $this->syncUserServices($user, $services, $headServices);
        $this->syncUserAntennes($user, $antennes, $headAntennes);

        return redirect()->route('users.index')->with('success', 'User created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function edit(User $user)
    {
        $profiles = profiles::all();
        $url = 'users';
        $groups = DB::table('groups')->orderBy('group_name')->get(['id', 'group_name']);
        $antennes = DB::table('antennes')->orderBy('antenne_name')->get(['id', 'antenne_name']);
        $selectedGroups = DB::table('user_groups')->where('a_user', '=', $user->id)->pluck('a_group')->map(fn ($id) => (int) $id);
        $headGroups = Schema::hasColumn('user_groups', 'is_head')
            ? DB::table('user_groups')->where('a_user', '=', $user->id)->where('is_head', '=', 1)->pluck('a_group')->map(fn ($id) => (int) $id)
            : collect();
        $selectedAntennes = DB::table('antenne_users')->where('ant_user', '=', $user->id)->pluck('ant_group')->map(fn ($id) => (int) $id);
        $headAntennes = Schema::hasColumn('antenne_users', 'is_head')
            ? DB::table('antenne_users')->where('ant_user', '=', $user->id)->where('is_head', '=', 1)->pluck('ant_group')->map(fn ($id) => (int) $id)
            : collect();

        return view('users.form', compact('user', 'profiles', 'url', 'groups', 'antennes', 'selectedGroups', 'headGroups', 'selectedAntennes', 'headAntennes'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UserRequest  $request
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function update(UserRequest $request, User $user)
    {
        $data = $request->validated();
        $data['name'] = trim(($data['firstname'] ?? '') . ' ' . ($data['lastname'] ?? ''));
        $services = collect($data['services'] ?? [])->map(fn ($id) => (int) $id)->unique();
        $headServices = collect($data['head_services'] ?? [])->map(fn ($id) => (int) $id)->intersect($services)->unique();
        $antennes = collect($data['antennes'] ?? [])->map(fn ($id) => (int) $id)->unique();
        $headAntennes = collect($data['head_antennes'] ?? [])->map(fn ($id) => (int) $id)->intersect($antennes)->unique();
        unset($data['services'], $data['head_services'], $data['antennes'], $data['head_antennes']);

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);
        $this->syncUserServices($user, $services, $headServices);
        $this->syncUserAntennes($user, $antennes, $headAntennes);

        return redirect()->route('users.index')->with('success', 'User updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $user)
    {
        DB::table('user_groups')->where('a_user', '=', $user->id)->delete();
        DB::table('antenne_users')->where('ant_user', '=', $user->id)->delete();
        $user->delete();
        return redirect()->route('users.index')->with('success', 'User deleted successfully.');
    }

    private function syncUserServices(User $user, $services, $headServices): void
    {
        DB::table('user_groups')->where('a_user', '=', $user->id)->delete();

        foreach ($services as $serviceId) {
            $isHead = $headServices->contains((int) $serviceId);

            if ($isHead && Schema::hasColumn('user_groups', 'is_head')) {
                DB::table('user_groups')
                    ->where('a_group', '=', $serviceId)
                    ->update(['is_head' => 0]);
            }

            $data = [
                'a_user' => $user->id,
                'a_group' => $serviceId,
            ];

            if (Schema::hasColumn('user_groups', 'is_head')) {
                $data['is_head'] = $isHead ? 1 : 0;
            }

            DB::table('user_groups')->insert($data);
        }
    }

    private function syncUserAntennes(User $user, $antennes, $headAntennes): void
    {
        DB::table('antenne_users')->where('ant_user', '=', $user->id)->delete();

        foreach ($antennes as $antenneId) {
            $isHead = $headAntennes->contains((int) $antenneId);

            if ($isHead && Schema::hasColumn('antenne_users', 'is_head')) {
                DB::table('antenne_users')
                    ->where('ant_group', '=', $antenneId)
                    ->update(['is_head' => 0]);
            }

            $data = [
                'ant_user' => $user->id,
                'ant_group' => $antenneId,
            ];

            if (Schema::hasColumn('antenne_users', 'is_head')) {
                $data['is_head'] = $isHead ? 1 : 0;
            }

            DB::table('antenne_users')->insert($data);
        }
    }
}
