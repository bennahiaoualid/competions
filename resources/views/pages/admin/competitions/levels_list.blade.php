@if($competition->levels->count())
<div class="block w-full overflow-x-auto">
    <table class="items-center bg-transparent w-full border-collapse ">
        <thead>
        <tr>
            <th class="px-6 bg-blueGray-50 text-blueGray-500 align-middle border border-solid border-blueGray-100 py-3 text-xs uppercase border-l-0 border-r-0 whitespace-nowrap font-semibold text-center">
               {{__('competition.level.name')}}
            </th>
            <th class="px-6 bg-blueGray-50 text-blueGray-500 align-middle border border-solid border-blueGray-100 py-3 text-xs uppercase border-l-0 border-r-0 whitespace-nowrap font-semibold text-center">
                {{__('competition.info.start_date')}}
            </th>
            <th class="px-6 bg-blueGray-50 text-blueGray-500 align-middle border border-solid border-blueGray-100 py-3 text-xs uppercase border-l-0 border-r-0 whitespace-nowrap font-semibold text-center">
                {{__('competition.info.status.state')}}
            </th>
            <th class="px-6 bg-blueGray-50 text-blueGray-500 align-middle border border-solid border-blueGray-100 py-3 text-xs uppercase border-l-0 border-r-0 whitespace-nowrap font-semibold text-center">
                {{__('competition.level.questions_number')}}
            </th>
            <th class="px-6 bg-blueGray-50 text-blueGray-500 align-middle border border-solid border-blueGray-100 py-3 text-xs uppercase border-l-0 border-r-0 whitespace-nowrap font-semibold text-center">
            </th>
        </tr>
        </thead>

        <tbody>
        @foreach($competition->levels as $level)
        <tr>
            <td class="border-t-0 px-6 align-middle border-l-0 border-r-0 text-xs whitespace-nowrap p-4 text-start text-blueGray-700 ">
                {{$level->name}}
            </td>
            <td class="border-t-0 px-6 align-middle border-l-0 border-r-0 text-xs whitespace-nowrap p-4 text-start">
                {{Carbon\Carbon::parse($level->start_date)->timezone(session('timezone'))}}
            </td>
            <td class="border-t-0 px-6 align-center border-l-0 border-r-0 text-xs whitespace-nowrap p-4" >
                <x-status-widget :status="$level->getStatus()"
                                 :text="__('competition.info.status.'.$level->getStatus())" />
            </td>
            <td class="border-t-0 px-6 align-middle border-l-0 border-r-0 text-xs whitespace-nowrap p-4 text-center">
                {{$level->questions_number}}
            </td>
            <td class="flex gep-4 justify-center">
                <x-button :islink="true" color_type="info" size="sm" title="permissions"
                          :outline="true" href='{{route("admin.competitions.level.edit", ["id" => base64_encode($level->id)])}}' target="_blank">
                    <x-slot:icon>
                        <i class="fa-regular fa-pen-to-square"></i>
                    </x-slot:icon>
                </x-button>
                @if($competition->canEdit())
                <x-button :islink="true" color_type="danger" size="sm" title="delete"
                          :outline="true" href='{{route("admin.competitions.level.delete", ["id" => base64_encode($level->id)])}}'>
                    <x-slot:icon>
                        <i class="fa-solid fa-trash fa-fw"></i>
                    </x-slot:icon>
                </x-button>
                @endif
            </td>
        </tr>
        @endforeach
        </tbody>

    </table>
</div>
@else
    <div class="text-center">
        <div class="text-gray-500 mb-4">
            <i class="fas fa-folder-open text-6xl"></i>
        </div>
        <div class="text-gray-700 text-lg font-semibold">
            {{__('messages.global.no_records')}}
        </div>
    </div>
@endif
