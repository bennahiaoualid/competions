<!DOCTYPE html>

@if(App::isLocale('en'))
    <html lang="en" dir="ltr" >
@else
    <html lang="ar" dir="rtl" class="font-arabic">
@endif

<head>
    @if(Auth::guard('web')->check())
        <meta name="user-id" content="{{ Auth::guard('web')->id() }}">
    @endif
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="keywords" content="HTML5 Template" />
    <meta name="description" content="Webmin - Bootstrap 4 & Angular 5 Admin Dashboard Template" />
    <meta name="author" content="potenzaglobalsolutions.com" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
    @include('layouts.user.head')
</head>

<body>
    <div class="wrapper {{ Auth::check() ? 'flex flex-1' : '' }}">
        @auth
            <!-- Sidebar -->
            @include("layouts.user.sidebar")
            <!-- Main Content -->
            <div class="flex-1 flex flex-col">
                <!-- Header -->
                @include("layouts.user.main-header")
                <!-- Main -->
                <main class="flex-1 p-4">
                    @yield('content')
                </main>
            </div>
        @else
            <!-- Guest Layout -->
            <div class="w-full">
                <!-- Header -->
                @include("layouts.user.main-header")
                <!-- Main -->
                <main class="p-4">
                    @yield('content')
                </main>
            </div>
        @endauth
    </div>
    <x-notification-detail-modal />

    @livewireScripts

    {{-- set up auth user global info for notifications --}}
    @auth
        @php
            $userId = auth()->id();
            $broadcastingConfig = [
                'broadcastingChannel' => [
                    'channel' => "notification.user." . $userId,
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
    @include('layouts.user.footer-scripts')
    @include('components.notification')
    @yield("custom_js")
</body>

</html>
