<div class="flex gap-4">
    @can("delete user")
        <div x-data>
            <x-button color_type="danger" size="sm" title="delete" :outline="true"
                      x-on:click="$dispatch('open-modal', { detail: 'delete' , value:'{{$row->id}}' })">
                <x-slot:icon>
                    <i class="fa-solid fa-trash fa-fw text-base"></i>
                </x-slot:icon>
            </x-button>
        </div>
    @endcan

    @can("update user")
        <x-button :islink="true" color_type="info" size="sm" title="permissions"
                  :outline="true" href='{{route("admin.users.edit", ["id" => $row->id])}}' target="_blank">
            <x-slot:icon>
                <i class="fa-solid fa-unlock text-base"></i>
            </x-slot:icon>
        </x-button>
    @endcan

</div>
