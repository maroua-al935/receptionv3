<!DOCTYPE html>
<html lang="{{ $page->language ?? 'en' }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <meta http-equiv="x-ua-compatible" content="ie=edge">
        <meta name="referrer" content="always">

        <title>VisiLog Service</title>
        
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <style>
            [x-cloak] { display: none !important; }
        </style>

    @livewireStyles
    </head>
    <body class="visitx-theme">
        <div x-data="{ sidebarOpen: false }" class="app-shell visitx-shell">
            @include('Service.layouts.sidebar')

            <div class="relative flex flex-1 flex-col overflow-hidden">
                @include('Service.layouts.header')

                <main class="app-main">
                    <div class="app-container">
                        @yield('body')
                    </div>
                </main>
            
            </div>
        </div>
        @livewireScripts
    </body>
</html>
