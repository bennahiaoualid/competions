<div class="block w-full overflow-x-auto">
    <table class="items-center bg-transparent w-full border-collapse ">
        <thead>
        <tr>
            <th class="px-6 bg-blueGray-50 text-blueGray-500 align-middle border border-solid border-blueGray-100 py-3 text-xs uppercase border-l-0 border-r-0 whitespace-nowrap font-semibold text-left">
               {{__('competition.level.name')}}
            </th>
            <th class="px-6 bg-blueGray-50 text-blueGray-500 align-middle border border-solid border-blueGray-100 py-3 text-xs uppercase border-l-0 border-r-0 whitespace-nowrap font-semibold text-left">
                {{__('competition.info.start_date')}}
            </th>
            <th class="px-6 bg-blueGray-50 text-blueGray-500 align-middle border border-solid border-blueGray-100 py-3 text-xs uppercase border-l-0 border-r-0 whitespace-nowrap font-semibold text-left">
                {{__('competition.info.status.state')}}
            </th>
            <th class="px-6 bg-blueGray-50 text-blueGray-500 align-middle border border-solid border-blueGray-100 py-3 text-xs uppercase border-l-0 border-r-0 whitespace-nowrap font-semibold text-left">
                {{__('competition.level.questions_number')}}
            </th>
            <th class="px-6 bg-blueGray-50 text-blueGray-500 align-middle border border-solid border-blueGray-100 py-3 text-xs uppercase border-l-0 border-r-0 whitespace-nowrap font-semibold text-left">
                {{__('competition.level.questions_number')}}
            </th>
        </tr>
        </thead>

        <tbody>
        @foreach($competition->levels as $level)
        <tr>
            <td class="border-t-0 px-6 align-middle border-l-0 border-r-0 text-xs whitespace-nowrap p-4 text-left text-blueGray-700 ">
                {{$level->name}}
            </td>
            <td class="border-t-0 px-6 align-middle border-l-0 border-r-0 text-xs whitespace-nowrap p-4 ">
                {{Carbon\Carbon::parse($level->start_date)->timezone(session('timezone'))}}
            </td>
            <td class="border-t-0 px-6 align-center border-l-0 border-r-0 text-xs whitespace-nowrap p-4">
                <x-status-widget :status="$level->getStatus()"
                                 :text="__('competition.info.status.'.$level->getStatus())" />
            </td>
            <td class="border-t-0 px-6 align-middle border-l-0 border-r-0 text-xs whitespace-nowrap p-4">
                {{$level->questions_number}}
            </td>
            <td>
                <x-button :islink="true" color_type="info" size="sm" title="permissions"
                          :outline="true" href='{{route("admin.competitions.level.edit", ["id" => base64_encode($level->id)])}}' target="_blank">
                    <x-slot:icon>
                        <i class="fa-regular fa-pen-to-square"></i>
                    </x-slot:icon>
                </x-button>
            </td>
        </tr>
        @endforeach
        </tbody>

    </table>
</div>
