@extends('Reception.layouts.master')

@section('body')
    <div class="space-y-5">
        <div class="visitx-hero visitx-dashboard-hero">
           
            <div class="visitx-hero-side">
                @if((int) Auth::guard('web')->user()->profile === 5)
                    <a href="{{ route('i_add_visitors') }}" class="primary-action visitx-dashboard-primary inline-flex w-fit items-center gap-2">
                        <svg class="h-4 w-4" viewBox="0 0 24 24"><path fill="currentColor" d="M11 13H5v-2h6V5h2v6h6v2h-6v6h-2z"/></svg>
                        Ajouter un visiteur
                    </a>
                @endif
            </div>
        </div>

        <div id="visitx-live-dashboard" class="space-y-5" data-endpoint="{{ route('reception_live_dashboard') }}" data-status="{{ $statusFilter ?? 'all' }}">
            @include('Reception.partials.home_live')
        </div>
    </div>

    <script>
        (function () {
            const container = document.getElementById('visitx-live-dashboard');
            if (!container || !container.dataset.endpoint) {
                return;
            }

            let inFlight = false;

            async function refreshDashboard() {
                if (inFlight || document.hidden) {
                    return;
                }

                inFlight = true;
                try {
                    const currentUrl = new URL(window.location.href);
                    const endpoint = new URL(container.dataset.endpoint, window.location.origin);
                    const status = currentUrl.searchParams.get('status') || container.dataset.status || 'all';
                    const search = currentUrl.searchParams.get('search') || '';

                    endpoint.searchParams.set('status', status);
                    if (search) {
                        endpoint.searchParams.set('search', search);
                    }

                    const response = await fetch(endpoint.toString(), {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        cache: 'no-store'
                    });

                    if (response.status === 401 || response.status === 419) {
                        window.location.reload();
                        return;
                    }
                    if (!response.ok) {
                        return;
                    }

                    const body = await response.json();
                    if (body && body.html) {
                        container.innerHTML = body.html;
                        container.dataset.status = status;
                    }
                } catch (error) {
                    // Keep the dashboard usable if a single polling request fails.
                } finally {
                    inFlight = false;
                }
            }

            window.addEventListener('focus', refreshDashboard);
            document.addEventListener('visibilitychange', function () {
                if (!document.hidden) {
                    refreshDashboard();
                }
            });
            window.setInterval(refreshDashboard, 3000);
        })();
    </script>
@endsection
