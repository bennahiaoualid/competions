<div id="sidebar" class=" h-auto min-h-screen z-40 fixed inset-y-0 left-0 transform -translate-x-full md:translate-x-0  md:relative bg-gray-800 text-white w-64 p-4 md:flex-shrink-0">
    <h2 class="text-2xl font-bold">Dashboard</h2>
    <hr class="h-px my-4 bg-gray-700 border-0 dark:bg-gray-700">
    <nav>
        <ul>
            <li class="mb-2">
                <x-nav-link href="{{route('admin.index')}}" :active="request()->routeIs('admin.index')" :sub="false">
                    <x-slot:icon>
                        <i class="fas fa-home me-3"></i>
                    </x-slot:icon>
                    {{__("links.admin.dashboard")}}
                </x-nav-link>
            </li>
            <x-nav-dropdown :title="__('links.admin.admins')"
                            :active="request()->is(App::currentLocale() . '/admin/admins/*')
                                     or request()->is(App::currentLocale() . '/admin/admins')" :sub="false"
                            :links="[
                    ['url' => route('admin.list'), 'title' => __('links.admin.list') , 'active' => request()->routeIs('admin.list'), 'subnav' => true],
                    ['url' => '#', 'title' => 'Tool 2', 'active' => false, 'subnav' => true],
                    ['url' => '#', 'title' => 'Tool 3', 'active' => false, 'subnav' => true],
                ]">
                <x-slot:icon>
                    <i class="fas fa-home me-3"></i>
                </x-slot:icon>
            </x-nav-dropdown>

            <li class="mb-2">
                <x-nav-link href="{{route('admin.users')}}" :active="request()->routeIs('admin.users')" :sub="false">
                    <x-slot:icon>
                        <i class="fas fa-home me-3"></i>
                    </x-slot:icon>
                    {{__("links.user.list")}}
                </x-nav-link>
            </li>

            <li class="mb-2">
                <x-nav-link href="{{route('admin.competitions')}}" :active="request()->routeIs('admin.competitions')" :sub="false">
                    <x-slot:icon>
                        <i class="fas fa-home me-3"></i>
                    </x-slot:icon>
                    {{__("links.competition.competitions")}}
                </x-nav-link>
            </li>


        </ul>
    </nav>
</div>

