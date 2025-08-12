<div id="sidebar" class="h-auto min-h-screen z-40 fixed inset-y-0 left-0 transform -translate-x-full md:translate-x-0 md:relative bg-gray-800 text-white w-64 p-4 md:flex-shrink-0 transition-all duration-300 ease-in-out">
    <h2 class="text-2xl font-bold sidebar-text">Dashboard</h2>
    <hr class="h-px my-4 bg-gray-700 border-0 dark:bg-gray-700">
    <nav>
        <ul>
            <li class="mb-2">
                <x-nav-link href="{{route('admin.index')}}" :active="request()->routeIs('admin.index')" :sub="false">
                    <x-slot:icon>
                        <i class="fas fa-tachometer-alt me-3"></i>
                    </x-slot:icon>
                    <span class="sidebar-text">{{__("links.admin.dashboard")}}</span>
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
                    <i class="fas fa-users-cog me-3"></i>
                </x-slot:icon>
                <x-slot:titleUi>
                    <span class="sidebar-text">{{__('links.admin.admins')}}</span>
                </x-slot:titleUi>
            </x-nav-dropdown>

            @role('owner')
                <li class="mb-2">
                    <x-nav-link href="{{route('admin.permissions.index')}}" :active="request()->routeIs('admin.permissions.*')" :sub="false">
                        <x-slot:icon>
                            <i class="fas fa-shield-alt me-3"></i>
                        </x-slot:icon>
                        <span class="sidebar-text">{{__("links.admin.permissions")}}</span>
                    </x-nav-link>
                </li>
            @endrole

            {{-- Monitoring job tracking list --}}
            <li class="mb-2">
                <x-nav-link href="{{route('admin.monitoring.job.tracking')}}" :active="request()->routeIs('admin.monitoring.job.tracking')" :sub="false">
                    <x-slot:icon>
                        <i class="fas fa-tasks me-3"></i>
                    </x-slot:icon>
                    <span class="sidebar-text">{{__("links.monitoring.job_tracking")}}</span>
                </x-nav-link>
            </li>

            {{-- Deletion records list --}}
            <li class="mb-2">
                <x-nav-link href="{{route('admin.monitoring.deletion-records')}}" :active="request()->routeIs('admin.monitoring.deletion-records')" :sub="false">
                    <x-slot:icon>
                        <i class="fas fa-trash me-3"></i>
                    </x-slot:icon>
                    <span class="sidebar-text">{{__("links.monitoring.deletion_records")}}</span>
                </x-nav-link>
            </li>

            {{-- Delayed processes list --}}
            <li class="mb-2">
                <x-nav-link href="{{route('admin.monitoring.delayed-processes')}}" :active="request()->routeIs('admin.monitoring.delayed-processes')" :sub="false">
                    <x-slot:icon>
                        <i class="fas fa-clock me-3"></i>
                    </x-slot:icon>
                    <span class="sidebar-text">{{__("links.monitoring.delayed_processes")}}</span>
                    <span class="block ms-2 py-0.5 px-2 rounded-md bg-warning text-white sidebar-badge" x-text="$store.delayedProcesses.readyCount || 0"></span>
                </x-nav-link>
            </li>

            {{-- Notification table list --}}
            <li class="mb-2">
                <x-nav-link href="{{ route('notifications.index') }}" :active="request()->routeIs('notifications.index*')" :sub="false">
                    <x-slot:icon>
                        <i class="fas fa-bell me-3"></i>
                    </x-slot:icon>
                    <span class="sidebar-text">{{ __("notifications.notifications") }}</span>
                </x-nav-link>
            </li>

            <li class="mb-2">
                <x-nav-link href="{{route('admin.users')}}" :active="request()->routeIs('admin.users')" :sub="false">
                    <x-slot:icon>
                        <i class="fas fa-users me-3"></i>
                    </x-slot:icon>
                    <span class="sidebar-text">{{__("links.user.list")}}</span>
                </x-nav-link>
            </li>

            <li class="mb-2">
                <x-nav-link href="{{route('admin.competitions')}}" :active="request()->routeIs('admin.competitions')" :sub="false">
                    <x-slot:icon>
                        <i class="fas fa-trophy me-3"></i>
                    </x-slot:icon>
                    <span class="sidebar-text">{{__("links.competition.competitions")}}</span>
                </x-nav-link>
            </li>

            <li class="mb-2">
                <x-nav-link href="{{route('admin.auditor.competitions')}}" :active="request()->routeIs('admin.auditor.competitions')" :sub="false">
                    <x-slot:icon>
                        <i class="fas fa-clipboard-check me-3"></i>
                    </x-slot:icon>
                    <span class="sidebar-text">{{__('links.competition.auditing_responses')}}</span>
                    <span class="block ms-2 py-0.5 px-2 rounded-md bg-primary text-white sidebar-badge">{{ $assignedUserCount }}</span>
                </x-nav-link>
            </li>

            <li class="mb-2">
                <x-nav-link href="{{route('admin.global_questions')}}" :active="request()->routeIs('admin.global_questions')" :sub="false">
                    <x-slot:icon>
                        <i class="fas fa-question-circle me-3"></i>
                    </x-slot:icon>
                    <span class="sidebar-text">{{__("links.global_user.global_questions")}}</span>
                </x-nav-link>
            </li>

            {{-- Admin Approval Requests --}}
            <li class="mb-2">
                <x-nav-link href="{{route('admin.approvals.index')}}" :active="request()->routeIs('admin.approvals.*')" :sub="false">
                    <x-slot:icon>
                        <i class="fas fa-check-circle me-3"></i>
                    </x-slot:icon>
                    <span class="sidebar-text">{{__("admin.admin_approval.title")}}</span>
                    <span class="block ms-2 py-0.5 px-2 rounded-md bg-warning text-white sidebar-badge" x-text="$store.adminApprovals.pendingCount || 0"></span>
                </x-nav-link>
            </li>

            {{-- Regular Admin Payment (Buy Coins) --}}
            <li class="mb-2">
                <x-nav-link href="{{route('payment.create')}}" :active="request()->routeIs('payment.*')" :sub="false">
                    <x-slot:icon>
                        <i class="fas fa-coins me-3"></i>
                    </x-slot:icon>
                    <span class="sidebar-text">{{__('links.payment.buy_coins')}}</span>
                </x-nav-link>
            </li>

            {{-- Accountant Payment Management --}}
            @can('manage payment')
                <x-nav-dropdown :title="__('links.payment.accountant_payments')"
                                :active="request()->is(App::currentLocale() . '/admin/payment/*')
                                        or request()->is(App::currentLocale() . '/admin/payment')" :sub="false"
                                :links="[
                        ['url' => route('admin.payment.transactions'), 'title' => __('links.payment.transactions') , 'active' => request()->routeIs('admin.payment.transactions'), 'subnav' => true],
                        ['url' => route('admin.payment.coin_pricing.index'), 'title' => __('links.payment.pricing') , 'active' => request()->routeIs('admin.payment.coin_pricing.index'), 'subnav' => true],
                        ['url' => route('admin.payment.coin_offers.index'), 'title' => __('links.payment.offers') , 'active' => request()->routeIs('admin.payment.coin_offers.index'), 'subnav' => true],
                        ['url' => route('admin.payment.audit_logs'), 'title' => __('links.payment.audit_logs') , 'active' => request()->routeIs('admin.payment.audit_logs'), 'subnav' => true],
                        ['url' => route('admin.payment.reviews.index'), 'title' => __('links.payment.reviews') , 'active' => request()->routeIs('admin.payment.reviews.*'), 'subnav' => true],
                    ]">
                    <x-slot:icon>
                        <i class="fas fa-credit-card me-3"></i>
                    </x-slot:icon>
                    <x-slot:titleUi>
                        <span class="sidebar-text">{{__('links.payment.accountant_payments')}}</span>
                    </x-slot:titleUi>
                </x-nav-dropdown>
            @endrole

            {{-- System Settings --}}
            @role('owner')
                <li class="mb-2">
                    <x-nav-link href="{{route('admin.system.settings.index')}}" :active="request()->routeIs('admin.system.settings.*')" :sub="false">
                        <x-slot:icon>
                            <i class="fas fa-cogs me-3"></i>
                        </x-slot:icon>
                        <span class="sidebar-text">{{__('links.system.settings')}}</span>
                    </x-nav-link>
                </li>
            @endrole


        </ul>
    </nav>
</div>

