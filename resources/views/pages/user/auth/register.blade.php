<x-guest-layout>
<div class="flex flex-col max-w-[40rem] mx-auto px-2 sm:px-4 md:px-6 lg:px-8 py-4 w-full shadow-lg">
    <h1 class="mb-4 font-medium self-center text-xl sm:text-2xl uppercase text-gray-800 ">
        {{__("form.title.register")}}
    </h1>
    <form action="{{route("register")}}" method="POST">
        @csrf

        <!-- user name -->
        <div>
            <x-input-label for="name" :value="__('user.profile.name')" />
            <x-text-input id="name" class="block mt-1 w-full" type="text" :icon="true"
                          name="name" :value="old('name')" min="3" max="40"
                          required autofocus autocomplete="username" >
                <x-slot:input_icon>
                    <svg class="h-6 w-6" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" stroke="currentColor">
                        <path d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                    </svg>
                </x-slot:input_icon>
            </x-text-input>
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('user.profile.email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" :icon="true"
                          name="email" :value="old('email')"
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
            <x-input-label for="password" :value="__('user.profile.password.password')" />
            <x-text-input id="password" class="block mt-1 w-full" type="password" :icon="true"
                          name="password" required >
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

        <div>
            <x-button class="flex items-center justify-center text-2xl w-full">
                <x-slot:icon>
                    <span class="rotate-180 me-2">
                        <svg class="h-6 w-6" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" stroke="currentColor">
                        <path d="M13 9l3 3m0 0l-3 3m3-3H8m13 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </span>
                </x-slot:icon>
                {{ __('form.actions.register') }}
            </x-button>
        </div>
    </form>
</div>
</x-guest-layout>
