<header>
    <nav class="bg-white border-gray-200 shadow-card">
        <div class="max-w-screen-xl flex flex-wrap items-center justify-between mx-auto p-4">
            <a href="{{route('home')}}" class="flex items-center space-x-3 rtl:space-x-reverse">
                <img src="{{asset('assets/images/logo.png')}}" class="w-8 md:w-12" alt="site Logo" />
                <span class="self-center text-xl md:text-2xl font-semibold whitespace-nowrap capitalize">{{__('messages.global.site_name')}}</span>
            </a>

            <div class="flex items-center gap-4 md:order-2">
                {{-- language select dropdown --}}
                <div class="hidden md:block">
                    <x-dropdown alignment="right" >
                        <x-slot name="trigger">
                            <button class="w-fit py-1 px-4  text-gray-600 rounded-md border  font-semibold focus:outline-none focus:shadow-outline text-sm overflow-hidden">
                                <i class="fa-solid fa-globe"></i> {{ LaravelLocalization::getCurrentLocaleNative() }}
                            </button>
                        </x-slot>

                        <div class="text-gray-600 text-sm truncate px-4 py-2">{{ __('messages.global.select_lang') }}</div>
                        @foreach(LaravelLocalization::getSupportedLocales() as $localeCode => $properties)
                            <x-dropdown-item
                                :active="$localeCode == LaravelLocalization::getCurrentLocale()"
                                class="flex items-center"
                                href="{{ LaravelLocalization::getLocalizedURL($localeCode, null, [], true) }}">
                                {{ $properties['native'] }}
                            </x-dropdown-item>
                        @endforeach
                    </x-dropdown>
                </div>

                {{-- user links drop down--}}
                @auth("web")
                    <div class="relative flex items-center md:order-2 space-x-3 md:space-x-0 rtl:space-x-reverse"
                        x-data="{ open: false, open_menu: false }" @click.outside="open = false" @close.stop="open = false">
                        <button type="button" class="flex text-sm bg-gray-800 rounded-full md:me-0 focus:ring-4 focus:ring-gray-300 " id="user-menu-button" aria-expanded="false" data-dropdown-toggle="user-dropdown" data-dropdown-placement="bottom"
                                @click="open = ! open">
                            <span class="sr-only">Open user menu</span>
                            <img src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name ?? 'User') }}" alt="User Avatar" class="h-8 w-8 rounded-full">
                        </button>
                        <!-- Dropdown menu -->
                        <div class="z-50 absolute end-4 top-[100%] my-4 text-base list-none bg-white divide-y divide-gray-100 rounded-lg shadow" id="user-dropdown"
                            x-show="open">
                            <div class="px-4 py-3">
                                <span class="block text-sm text-gray-900 ">{{\Illuminate\Support\Facades\Auth::user()->name}}</span>
                                <span class="block text-sm  text-gray-500 truncate ">{{\Illuminate\Support\Facades\Auth::user()->email}}</span>
                            </div>
                            <ul class="py-2" aria-labelledby="user-menu-button">
                                <li>
                                    <a href="{{route('user.profile.edit')}}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 capitalize">
                                        {{ __('user.profile.yours') }}
                                    </a>
                                </li>
                                <li>
                                    <form class="w-full" action="{{route('logout')}}" method="post">
                                        @csrf
                                        @method('post')
                                        <button class="block w-full text-start px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 " type="submit">
                                            {{__('links.log_out')}}
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    </div>
                @else
                    <a class="py-1 px-2 border border-gray-300 hidden md:block" href="{{route('login')}}">{{__('form.actions.login')}}</a>
                @endauth
                <button id="main-navbar-toggle" type="button" class="inline-flex items-center p-2 w-10 h-10 justify-center text-sm text-gray-500 rounded-lg md:hidden hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-200" aria-controls="main-navbar" aria-expanded="false">
                    <span class="sr-only">Open main menu</span>
                    <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 17 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 1h15M1 7h15M1 13h15"/>
                    </svg>
                </button>
            </div>


            @php
                $active_class = 'block py-2 px-3 text-white bg-primary md:bg-transparent md:text-primary md:p-0 capitalize';
                $inactive_classes = 'block py-2 px-3 text-gray-900 rounded hover:bg-gray-100 md:hover:bg-transparent md:hover:text-primary md:p-0 capitalize';
            @endphp

            <div class="items-center justify-between hidden w-full md:flex md:w-auto md:order-1" id="main-navbar">
                <ul class="flex flex-col font-medium p-4 md:p-0 mt-4 border border-gray-100 rounded-lg bg-gray-50 md:space-x-8 rtl:space-x-reverse md:flex-row md:mt-0 md:border-0 md:bg-white">
                    <li>
                        <a href="{{route('home')}}" class="{{request()->routeIs('home') ? $active_class : $inactive_classes}}" aria-current="page">
                            {{__('links.home')}}
                        </a>
                    </li>
                    <li>
                        <a href="{{route('competitions')}}" class="{{request()->routeIs('competitions') ? $active_class : $inactive_classes}}">
                            {{__('links.competition.competitions')}}
                        </a>
                    </li>
                    @if(\Illuminate\Support\Facades\Auth::guard('web')->check() && ! \Illuminate\Support\Facades\Auth::user()->guest)
                        <li>
                            <a href="{{route('user.competitions')}}" class="{{request()->routeIs('user.competitions*') ? $active_class : $inactive_classes}}">
                                {{__('competition.info.user_auth')}}
                            </a>
                        </li>
                    @endif
                    <li>
                        <a href="{{route('global_questions.index')}}" class="{{request()->routeIs('global_questions.index') ? $active_class : $inactive_classes}}">
                            {{__('links.global_user.global_questions')}}
                        </a>
                    </li>
                    @if(\Illuminate\Support\Facades\Auth::guard('web')->check() && ! \Illuminate\Support\Facades\Auth::user()->guest)
                        <li>
                            <a href="{{route('user.global_questions.responses')}}" class="{{request()->routeIs('user.global_questions.responses') ? $active_class : $inactive_classes}}">
                                {{__('links.global_user.global_responses')}}
                            </a>
                        </li>
                    @endif
                    <li>
                        <a href="{{route('global_questions.global_order')}}" class="{{request()->routeIs('global_questions.global_order') ? $active_class : $inactive_classes}}">
                            {{__('links.global_user.global_order')}}
                        </a>
                    </li>
                    @guest()
                    <li class="md:hidden">
                        <a class="py-1 px-2 bg-primary rounded-sm block my-2 mx-auto text-white text-center uppercase" href="{{route('login')}}">{{__('form.actions.login')}}</a>
                    </li>
                    @endguest
                    <li class="md:hidden  mt-4">
                        {{-- language select dropdown --}}
                        <x-dropdown alignment="right">
                            <x-slot name="trigger">
                                <button class="w-full py-1 px-4  text-gray-600 rounded-md border  font-semibold focus:outline-none focus:shadow-outline text-sm overflow-hidden">
                                    <i class="fa-solid fa-globe"></i> {{ LaravelLocalization::getCurrentLocaleNative() }}
                                </button>
                            </x-slot>

                            <div class="text-gray-600 text-sm truncate px-4 py-2">{{ __('messages.global.select_lang') }}</div>
                            @foreach(LaravelLocalization::getSupportedLocales() as $localeCode => $properties)
                                <x-dropdown-item
                                    :active="$localeCode == LaravelLocalization::getCurrentLocale()"
                                    class="flex items-center"
                                    href="{{ LaravelLocalization::getLocalizedURL($localeCode, null, [], true) }}">
                                    {{ $properties['native'] }}
                                </x-dropdown-item>
                            @endforeach
                        </x-dropdown>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
</header>
