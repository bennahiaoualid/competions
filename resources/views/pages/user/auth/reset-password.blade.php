<x-guest-layout>
    <div class="flex flex-col max-w-[40rem] mx-auto px-2 sm:px-4 md:px-6 lg:px-8 py-4 w-full shadow-lg">
        <h1 class="mb-4 font-medium self-center text-xl sm:text-2xl uppercase text-gray-800 ">
            {{__("form.title.rest_password")}}
        </h1>

        <form method="POST" action="{{ route('password.store') }}">
            @csrf

            <!-- Password Reset Token -->
            <input type="hidden" name="token" value="{{ $request->route('token') }}">

            <!-- Email Address -->
            <div>
                <x-input-label for="email" :value="__('user.profile.email')" />
                <x-text-input id="email" class="block mt-1 w-full" type="email" :icon="true"
                              name="email" :value="old('email', $request->email)"
                              required autofocus autocomplete="username" >
                    <x-slot:input_icon>
                        <svg class="h-6 w-6" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" stroke="currentColor">
                            <path d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                        </svg>
                    </x-slot:input_icon>
                </x-text-input>
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <!-- Password -->
            <div class="mt-4">
                <x-input-label for="password" :value="__('user.profile.password.new')" />
                <x-text-input id="password" class="block mt-1 w-full" type="password" :icon="true"
                              name="password" required autocomplete="new-password"  >
                    <x-slot:input_icon>
                        <svg class="h-6 w-6" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" stroke="currentColor">
                            <path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </x-slot:input_icon>
                </x-text-input>
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <!-- Confirm Password -->
            <div class="mt-4">
                <x-input-label for="password_confirmation" :value="__('user.profile.password.confirm')" />

                <x-text-input id="password_confirmation" class="block mt-1 w-full"
                              type="password" :icon="true"
                              name="password_confirmation" required autocomplete="new-password">
                    <x-slot:input_icon>
                        <svg class="h-6 w-6" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" stroke="currentColor">
                            <path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </x-slot:input_icon>
                </x-text-input>

                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
            </div>

            <div class="flex items-center justify-end mt-4">
                <x-button>
                    {{ __('form.actions.save') }}
                </x-button>
            </div>
        </form>
    </div>
</x-guest-layout>



