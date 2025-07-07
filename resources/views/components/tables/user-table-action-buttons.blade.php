<div class="flex gap-4">
    @can("delete user")
        <div x-data>
            <x-button color_type="danger" size="sm" title="delete" :outline="true"
            x-on:click="
                $dispatch('open-modal', 
                    { detail: 'delete' , 
                    value:'{{$user->id}}',
                    input_detail: { userName: '{{ $user->name }}' }
                    })"
            >
                <x-slot:icon>
                    <i class="fa-solid fa-trash fa-fw text-base"></i>
                </x-slot:icon>
            </x-button>
        </div>
    @endcan

    @can("update user")
        <x-button :islink="true" color_type="info" size="sm" title="edit"
                :outline="true" href='{{route("admin.users.edit", ["user" => $user])}}' target="_blank">
            <x-slot:icon>
                <i class="fa-solid fa-edit text-base"></i>
            </x-slot:icon>
        </x-button>
    @endcan

</div>
