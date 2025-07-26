<header class="bg-white shadow p-4 flex justify-between items-center">
    <div class="flex gap-2 items-center">
        <button id="toggleSidebarBtn" class="md:hidden text-gray-600 focus:outline-none">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>
        <button id="toggleSidebarDesktopBtn" class="hidden md:block text-gray-600 focus:outline-none">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>
        <h1 class="md:text-xl font-bold capitalize ">@yield("page_title",'Dashboard')</h1>
    </div>

    <div class="flex items-center space-x-2 relative min-w-36">
        <!-- Notification Dropdown -->
        <x-dropdown alignment="right" width="80">
            <x-slot name="trigger">
                <button class="flex items-center text-gray-600 hover:text-gray-800 focus:outline-none focus:shadow-outline p-2 rounded-md transition-colors duration-200 relative">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                    <span id="notification-badge" class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center font-bold" style="display: none;">0</span>
                </button>
            </x-slot>
            
            <div id="notification-list" class="max-h-96 overflow-y-auto">
                <div class="p-4 text-center text-gray-500">
                    <svg class="mx-auto h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                    <p class="mt-2 text-sm">No notifications yet</p>
                </div>
            </div>
            
            <div class="border-t border-gray-200 p-2">
                <x-dropdown-item href="/admin/notifications" class="text-center text-sm">
                    View all notifications
                </x-dropdown-item>
            </div>
        </x-dropdown>
        
        <x-dropdown alignment="right">
            <x-slot name="trigger">
                <button class="w-fit py-1 px-4  text-gray-600 rounded-md border  font-semibold focus:outline-none focus:shadow-outline text-sm overflow-hidden">
                    <i class="fa-solid fa-globe"></i> {{ LaravelLocalization::getCurrentLocaleNative() }}
                </button>
            </x-slot>

            <div class="text-gray-600 text-sm truncate px-4 py-2">{{ "select lang" }}</div>
            @foreach(LaravelLocalization::getSupportedLocales() as $localeCode => $properties)
                <x-dropdown-item
                    :active="$localeCode == LaravelLocalization::getCurrentLocale()"
                    class="flex items-center"
                    href="{{ LaravelLocalization::getLocalizedURL($localeCode, null, [], true) }}">
                    {{ $properties['native'] }}
                </x-dropdown-item>
            @endforeach
        </x-dropdown>

        <x-dropdown alignment="right">
            <x-slot name="trigger">
                <button id="userMenuButton" class="flex items-center text-gray-600 focus:outline-none">
                    <img src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name ?? 'User') }}" alt="User Avatar" class="h-8 w-8 rounded-full">
                </button>
            </x-slot>
            <x-dropdown-item :active="false" class="flex items-center" href="#">
                {{ __('user.profile.yours') }}
            </x-dropdown-item>
            <x-dropdown-item :active="false" class="flex items-center" href="#">
                Settings
            </x-dropdown-item>
            <x-dropdown-item :active="false" class="flex items-center" href="#">
               <i class="fa-solid fa-power-off fa-fw me-2"></i> Sign out
            </x-dropdown-item>
            <x-dropdown-item :active="false" tag="button" formAction="{{ route('admin.logout') }}" class="flex items-center w-full">
                <i class="fa-solid fa-power-off fa-fw me-2"></i> Sign out
            </x-dropdown-item>
        </x-dropdown>
    </div>
</header>
