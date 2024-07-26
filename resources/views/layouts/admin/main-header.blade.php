<header class="bg-white shadow p-4 flex justify-between items-center">
    <div class="flex gap-4 items-center">
        <button id="toggleSidebarBtn" class="md:hidden text-gray-600 focus:outline-none">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>
        <h1 class="text-xl font-bold capitalize">@yield("page_title",'Dashboard')</h1>
    </div>

    <div class="flex items-center space-x-4 relative">
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
                    <img src="https://via.placeholder.com/150" alt="User Avatar" class="h-8 w-8 rounded-full">
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
