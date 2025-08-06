<!DOCTYPE html>

@if(App::isLocale('en'))
    <html lang="en" dir="ltr" >
@else
    <html lang="ar" dir="rtl" class="font-arabic">
@endif

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="keywords" content="Competition Management System" />
    <meta name="author" content="oualid bennahia" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
    @include('layouts.admin.head')
</head>

<body>
    <div class="wrapper flex flex-1">
        <!-- Sidebar -->
        @include("layouts.admin.sidebar")
        <!-- Main Content -->
        <div class="flex-1 flex flex-col">
            <!-- Header -->
            @include("layouts.admin.main-header")

            <!-- Main -->
            <main class="flex-1 p-4">
                @yield('content')
            </main>
        </div>
    </div>
    <x-notification-detail-modal />
    @livewireScripts

    {{-- set up auth user global info for notifications --}}
    @auth
        @php
            $userId = auth()->id();
            $broadcastingConfig = [
                'broadcastingChannel' => [
                    'channel' => "notification.admin." . $userId,
                    'cluster' => config('broadcasting.connections.pusher.options.cluster')
                ],
                'userId' => $userId,
                'csrfToken' => csrf_token()
            ];
        @endphp
        <script>
            window.Laravel = @json($broadcastingConfig);
        </script>
    @endauth
    @include('layouts.admin.footer-scripts')
    @include('layouts.admin.session_notifications_taoster')
    {{-- Include notification scripts --}}
    @vite(['resources/js/notifications/NotificationManager.js', 'resources/js/notifications/init.js'])
    @yield("custom_js")
</body>
</html>
