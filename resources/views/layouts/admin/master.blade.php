<!DOCTYPE html>

@if(App::isLocale('en'))
    <html lang="en" dir="ltr" >
@else
    <html lang="ar" dir="rtl" class="font-arabic">
@endif

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="keywords" content="HTML5 Template" />
    <meta name="description" content="Webmin - Bootstrap 4 & Angular 5 Admin Dashboard Template" />
    <meta name="author" content="potenzaglobalsolutions.com" />
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
    @livewireScripts
    @include('layouts.admin.footer-scripts')
    @include('components.notification')
    @yield("custom_js")
</body>

</html>
