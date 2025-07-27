<!DOCTYPE html>

@if(App::isLocale('en'))
    <html lang="en" dir="ltr">
@else
    <html lang="ar" dir="rtl">
    @endif

        <head>
            <meta charset="UTF-8">
            <title>Welcome to Our Platform</title>
            <!-- tailwind complied -->
            @vite(['resources/css/app.css', 'resources/js/app.js'])

        </head>
        <body>
            <div class="text-center space-y-4">
                <h1 class="bg-primary py-2 uppercase text-center text-white">
                    @if(isset($data['user']) && !empty($data['user']))
                        {{__('messages.mail.welcome',['user'=>$data['user']])}}
                    @else
                        {{__('messages.mail.welcome_generic')}}
                    @endif
                </h1>
                <p class="capitalize">
                    @if($data['object'] == 'competition')
                        {{__('messages.mail.'.$data['type'],['competition'=>$data['competition']])}}
                    @else
                        {{__('messages.mail.'.$data['type'],['competition'=>$data['competition'], 'level' =>$data['level']])}}
                    @endif
                </p>
                @isset($data['link'])
                    <a href="{{$data['link']}}" class="px-2 py-1 block bg-primary text-white w-fit rounded-md mx-auto">
                        {{__('messages.global.see_more')}}
                    </a>
                @endisset
            </div>

        </body>
    </html>
