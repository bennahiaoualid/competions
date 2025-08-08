<!DOCTYPE html>

@if(App::isLocale('en'))
    <html lang="en" dir="ltr" >
@else
    <html lang="ar" dir="rtl" class="font-arabic">
@endif

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="keywords" content="Payment System" />
    <meta name="description" content="Payment Management System" />
    <meta name="author" content="oualid bennahia" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
    @include('layouts.payment.head')
</head>

<body>
    <div class="wrapper">
        <!-- Payment Header -->
        @include("layouts.payment.main-header")
        
        <!-- Main Content -->
        <main class="p-2 mt-4 container">
            @yield('content')
        </main>
    </div>
    
    <x-notification-detail-modal />
    @livewireScripts

    {{-- set up auth user global info for notifications --}}
    @auth
        @php
            $userId = auth()->id();
            $broadcastingConfig = [
                'broadcastingChannel' => [
                    'channel' => "notification." . (auth()->guard('admin')->check() ? 'admin' : 'user') . "." . $userId,
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
    
    @include('layouts.payment.footer-scripts')
    @include('components.notification')
    @include('layouts.session_notifications_taoster')
    @yield("custom_js")
</body>
</html> 