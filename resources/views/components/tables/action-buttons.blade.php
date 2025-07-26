<div class="flex gap-4">
    <x-button color_type="danger" size="sm" title="delete" :outline="true"
            x-on:click="
                $dispatch('open-modal', 
                    { detail: 'delete' , 
                    value:'{{$row->id}}',
                    input_detail: { adminName: '{{ $row->name }}' }
                    })"
            >
        <x-slot:icon>
            <i class="fa-solid fa-trash fa-fw text-base"></i>
        </x-slot:icon>
    </x-button>
    <x-button :islink="true" color_type="info" size="sm"
            :outline="true" href='{{route("admin.edit", ["id" => $row->id])}}' target="_blank">
        <x-slot:icon>
            <i class="fa-solid fa-edit text-base"></i>
        </x-slot:icon>
    </x-button>
</div>

