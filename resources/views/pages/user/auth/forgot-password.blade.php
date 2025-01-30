<x-guest-layout>
    <div class="flex flex-col max-w-[40rem] mx-auto px-2 sm:px-4 md:px-6 lg:px-8 py-4 w-full shadow-lg">
        <h1 class="mb-4 font-medium self-center text-xl sm:text-2xl uppercase text-gray-800 ">
            {{__("form.title.rest_password")}}
        </h1>
        <div class="mb-4 text-sm text-gray-600">
            {{ __('passwords.reset.description') }}
        </div>

        <!-- Session Status -->
        <x-auth-session-status class="mb-4" :status="session('status')" />

        <form method="POST" action="{{ route('password.email') }}">
            @csrf
            <!-- Email Address -->
            <div>
                <x-input-label  for="email" :value="__('user.profile.email')" />
                <x-text-input id="email" class="block mt-1 w-full" type="email" name="email"
                              :value="old('email')" :icon="true" required autofocus>
                    <x-slot:input_icon>
                        <svg class="h-6 w-6" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" stroke="currentColor">
                            <path d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                        </svg>
                    </x-slot:input_icon>
                </x-text-input>
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <div class="flex items-center justify-end mt-4">
                <x-button type="primary">
                    {{ __('passwords.reset.send') }}
                </x-button>
            </div>
        </form>
    </div>
</x-guest-layout>


