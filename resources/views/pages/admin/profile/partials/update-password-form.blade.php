<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ucwords( __('user.profile.password.update')) }}
        </h2>
    </header>

    <form method="post" action="{{ route('admin.password.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('put')

        <div>
            <x-input-label for="update_password_current_password" :value=" ucwords(__('user.profile.password.current'))" />
            <x-text-input id="update_password_current_password" name="current_password" type="password" class="mt-1 block w-full" autocomplete="current-password" />
            <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="update_password_password" :value="ucwords(__('user.profile.password.new'))" />
            <x-text-input id="update_password_password" name="password" type="password" class="mt-1 block w-full" autocomplete="new-password" />
            <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="update_password_password_confirmation" :value="ucwords(__('user.profile.password.confirm'))" />
            <x-text-input id="update_password_password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" autocomplete="new-password" />
            <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-2" />
        </div>

        <ul class="mt-1 text-sm text-gray-600 space-y-1 list-disc">
            <li>{{ __('user.profile.password.8_at_least') }}</li>
            <li>{{ __('user.profile.password.contains_number') }}</li>
            <li>{{ __('user.profile.password.contains_upper') }}</li>
            <li>{{ __('user.profile.password.contains_symbol') }}</li>
        </ul>

        <div class="flex items-center gap-4">
            <x-button color_type="primary" >{{ __('form.actions.change') }}</x-button>
            @if (session('status') === 'password-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 3000)"
                    class="text-sm text-gray-600"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>
