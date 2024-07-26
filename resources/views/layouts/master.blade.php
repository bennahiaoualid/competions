<!DOCTYPE html>

@if(App::isLocale('en'))
    <html lang="en" dir="ltr">
@else
    <html lang="ar" dir="rtl">
@endif

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="keywords" content="HTML5 Template" />
    <meta name="description" content="Webmin - Bootstrap 4 & Angular 5 Admin Dashboard Template" />
    <meta name="author" content="potenzaglobalsolutions.com" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
    @include('layouts.head')
</head>

<body class="hold-transition sidebar-mini layout-fixed">

    <div class="wrapper" style="font-family: 'Cairo', sans-serif">

        @include('layouts.main-header')
        @extends('layouts.main-sidebar')

    <!--================================= Main content -->
        <div class="content-wrapper">
            <!-- Content Header (Page header) -->
            <div class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1 class="m-0 text-dark">@yield('PageTitle')</h1>
                        </div><!-- /.col -->
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item"><a href="#">{{trans('main_trans.dashboard')}}</a></li>
                                <li class="breadcrumb-item active">@yield('PageTitle')</li>
                            </ol>
                        </div><!-- /.col -->
                    </div><!-- /.row -->
                </div><!-- /.container-fluid -->
            </div>
            <!-- /.content-header -->

            <section class="content position-relative">
                @yield('content')
            </section>
        </div>
        <!-- main content wrapper end-->

        @include('layouts.footer')

    </div>
    <!--================================= footer -->

    @include('layouts.footer-scripts')

</body>

</html>
