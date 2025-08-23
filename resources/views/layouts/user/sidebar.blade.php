@auth
<div id="sidebar" class="h-auto min-h-screen z-40 fixed inset-y-0 left-0 transform -translate-x-full md:translate-x-0 md:relative bg-gray-800 text-white w-64 p-4 md:flex-shrink-0 transition-all duration-300 ease-in-out">
    <h2 class="text-2xl font-bold sidebar-text">{{__('messages.global.site_name')}}</h2>
    <hr class="h-px my-4 bg-gray-700 border-0 dark:bg-gray-700">
    <nav>
        <ul>
            <li class="mb-2">
                <x-nav-link href="{{route('home')}}" :active="request()->routeIs('home')" :sub="false">
                    <x-slot:icon>
                        <i class="fas fa-home me-3"></i>
                    </x-slot:icon>
                    <span class="sidebar-text">{{__('links.home')}}</span>
                </x-nav-link>
            </li>
            
            <li class="mb-2">
                <x-nav-link href="{{route('competitions')}}" :active="request()->routeIs('competitions')" :sub="false">
                    <x-slot:icon>
                        <i class="fas fa-trophy me-3"></i>
                    </x-slot:icon>
                    <span class="sidebar-text">{{__('links.competition.competitions')}}</span>
                </x-nav-link>
            </li>
            
            <li class="mb-2">
                <x-nav-link href="{{route('user.competitions')}}" :active="request()->routeIs('user.competitions*')" :sub="false">
                    <x-slot:icon>
                        <i class="fas fa-user-trophy me-3"></i>
                    </x-slot:icon>
                    <span class="sidebar-text">{{__('competition.info.user_auth')}}</span>
                </x-nav-link>
            </li>
            
            <li class="mb-2">
                <x-nav-link href="{{route('global_questions.index')}}" :active="request()->routeIs('global_questions.index')" :sub="false">
                    <x-slot:icon>
                        <i class="fas fa-question-circle me-3"></i>
                    </x-slot:icon>
                    <span class="sidebar-text">{{__('links.global_user.global_questions')}}</span>
                </x-nav-link>
            </li>
            
            <li class="mb-2">
                <x-nav-link href="{{route('user.global_questions.responses')}}" :active="request()->routeIs('user.global_questions.responses')" :sub="false">
                    <x-slot:icon>
                        <i class="fas fa-list-check me-3"></i>
                    </x-slot:icon>
                    <span class="sidebar-text">{{__('links.global_user.global_responses')}}</span>
                </x-nav-link>
            </li>
            
            <li class="mb-2">
                <x-nav-link href="{{route('payment.create')}}" :active="request()->routeIs('user.payment.*')" :sub="false">
                    <x-slot:icon>
                        <i class="fas fa-credit-card me-3"></i>
                    </x-slot:icon>
                    <span class="sidebar-text">{{__('payment.nav.create')}}</span>
                </x-nav-link>
            </li>
            
            <li class="mb-2">
                <x-nav-link href="{{route('global_questions.global_order')}}" :active="request()->routeIs('global_questions.global_order')" :sub="false">
                    <x-slot:icon>
                        <i class="fas fa-chart-line me-3"></i>
                    </x-slot:icon>
                    <span class="sidebar-text">{{__('links.global_user.global_order')}}</span>
                </x-nav-link>
            </li>
            
            <!-- Language Selection Dropdown -->
            <x-nav-dropdown :title="__('messages.global.select_lang')"
                            :active="false" :sub="false"
                            :links="collect(LaravelLocalization::getSupportedLocales())->map(function($properties, $localeCode) {
                                return [
                                    'url' => LaravelLocalization::getLocalizedURL($localeCode, null, [], true),
                                    'title' => $properties['native'],
                                    'active' => $localeCode == LaravelLocalization::getCurrentLocale(),
                                    'subnav' => true
                                ];
                            })->toArray()">
                <x-slot:icon>
                    <i class="fas fa-globe me-3"></i>
                </x-slot:icon>
                <x-slot:titleUi>
                    <span class="sidebar-text">{{__('messages.global.select_lang')}}</span>
                </x-slot:titleUi>
            </x-nav-dropdown>
        </ul>
    </nav>
</div>
@endauth 