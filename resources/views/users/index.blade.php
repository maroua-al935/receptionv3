@extends('President.layouts.master')

@section('body')
    <div class="space-y-6">
        <div class="visitx-hero">
            <div>
                <p class="visitx-eyebrow">Administration</p>
                <h1 class="page-title">Utilisateurs</h1>
                <p class="page-subtitle">Gestion des comptes, profils, services et antennes sans changer la logique backend.</p>
            </div>
            <div class="visitx-hero-side">
                <div class="visitx-hero-chip">
                    <span class="visitx-hero-dot"></span>
                    {{ $users->total() }} compte(s)
                </div>
                <a href="{{ route('users.create') }}" class="primary-action inline-flex w-fit items-center gap-2">
                    <svg class="h-5 w-5" viewBox="0 0 24 24"><path fill="currentColor" d="M11 13H5v-2h6V5h2v6h6v2h-6v6h-2z"/></svg>
                    Ajouter un utilisateur
                </a>
            </div>
        </div>

        <section class="panel panel-pad">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <form action="{{ route('users.index') }}" method="GET" class="flex w-full flex-col gap-3">
                    <div class="visitx-search flex w-full xl:flex">
                        <svg class="h-4 w-4" viewBox="0 0 24 24"><path fill="currentColor" d="M9.5 3a6.5 6.5 0 0 1 5.176 10.435l4.445 4.444l-1.414 1.414l-4.444-4.445A6.5 6.5 0 1 1 9.5 3m0 2a4.5 4.5 0 1 0 0 9a4.5 4.5 0 0 0 0-9"/></svg>
                        <input placeholder="Rechercher un utilisateur..." name="search" value="{{ request('search') }}" />
                    </div>
                    <div class="flex flex-col gap-3 lg:flex-row">
                        <select name="head_status" class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-600">
                            <option value="">Tous les utilisateurs</option>
                            <option value="yes" @selected(request('head_status') === 'yes')>Chefs uniquement</option>
                            <option value="no" @selected(request('head_status') === 'no')>Non-chefs uniquement</option>
                        </select>
                        <select name="head_type" class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-600">
                            <option value="">Chef de quoi ?</option>
                            <option value="service" @selected(request('head_type') === 'service')>Chef de service</option>
                            <option value="antenne" @selected(request('head_type') === 'antenne')>Chef d'antenne</option>
                        </select>
                        <button type="submit" class="rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-600 transition hover:border-violet-200 hover:bg-violet-50 hover:text-violet-700">
                            Filtrer
                        </button>
                    </div>
                </form>

                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-500">
                    Page {{ $users->currentPage() }} sur {{ $users->lastPage() }}
                </div>
            </div>
        </section>

        <section class="panel overflow-hidden">
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-5">
                <div>
                    <h2 class="text-xl font-semibold text-slate-900">Liste des utilisateurs</h2>
                    <p class="text-sm text-slate-500">Profils, affectations siege et antennes.</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Utilisateur</th>
                            <th>Email</th>
                            <th>Profil</th>
                            <th>Services</th>
                            <th>Antennes</th>
                            <th>Chef de quoi</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($users as $user)
                            <tr>
                                <td class="px-5 py-4 text-sm font-semibold text-slate-700">{{ $user->id }}</td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-violet-100 text-sm font-bold text-violet-700">
                                            {{ strtoupper(substr($user->username ?? $user->name ?? 'U', 0, 1)) }}
                                        </div>
                                        <div>
                                            <div class="font-medium text-slate-900">{{ $user->username ?? $user->name }}</div>
                                            <div class="text-sm text-slate-500">{{ trim(($user->firstname ?? '') . ' ' . ($user->lastname ?? '')) ?: 'Nom complet non renseigne' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-sm text-slate-500">{{ $user->email }}</td>
                                <td class="px-5 py-4">
                                    <span class="rounded-full bg-violet-100 px-3 py-1 text-xs font-semibold text-violet-700">
                                        {{ $user->profile_info->name ?? 'N/A' }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-sm text-slate-500">{{ $user->service_labels ?: 'N/A' }}</td>
                                <td class="px-5 py-4 text-sm text-slate-500">{{ $user->antenne_labels ?: 'N/A' }}</td>
                                <td class="px-5 py-4 text-sm text-slate-500">{{ $user->head_labels ?: 'Non' }}</td>
                                <td class="px-5 py-4">
                                    <div class="flex justify-end gap-2">
                                        <a href="{{ route('users.edit', $user->id) }}" class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-600 transition hover:border-violet-200 hover:bg-violet-50 hover:text-violet-700">
                                            Modifier
                                        </a>
                                        <form action="{{ route('users.destroy', $user->id) }}" method="POST" class="inline-block">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rounded-xl border border-rose-200 px-3 py-2 text-xs font-semibold text-rose-600 transition hover:bg-rose-50" onclick="return confirm('Are you sure?')">
                                                Supprimer
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="border-t border-slate-100 bg-white px-6 py-4">
                {{ $users->links() }}
            </div>
        </section>
    </div>
@endsection
