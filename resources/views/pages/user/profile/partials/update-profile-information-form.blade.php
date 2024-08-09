<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ ucwords(__('user.profile.information')) }}
        </h2>
    </header>

    <div class="mt-6 space-y-6">
        <div>
            <x-input-label for="name" :value="ucwords(__('user.profile.name'))" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full outline-none" :icon="false" :value="old('name', $user->name)" required autofocus autocomplete="name" />
        </div>

        <div>
            <x-input-label for="email" :value="ucwords(__('user.profile.email'))" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full outline-none" :value="old('email', $user->email)" required autocomplete="username" />
        </div>
    </div>
</section>
