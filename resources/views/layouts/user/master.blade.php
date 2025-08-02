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

    <div class="wrapper">
        <!-- Header -->
        @include("layouts.user.main-header")
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
