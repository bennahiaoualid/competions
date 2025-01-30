<div class="block w-full overflow-x-auto bg-gray-200 px-8 py-2">
    <table class="items-center bg-transparent w-full border-collapse ">
        <thead>
        <tr>
            <th class="px-2 bg-slate-300 text-blueGray-500 align-middle border border-solid border-blueGray-100 py-3 text-xs uppercase border-l border-r  border-blueGray-100  whitespace-nowrap font-semibold text-center max-w-fit w-1">
                {{__('messages.global.no')}}
            </th>
            <th class="px-6 bg-slate-300 text-blueGray-500 align-middle border border-solid border-blueGray-100 py-3 text-xs uppercase border-l border-r  border-blueGray-100 whitespace-nowrap font-semibold text-center w-full">
                {{__('competition.global.choice')}}
            </th>
        </tr>
        </thead>

        <tbody>
        @foreach($row->choices as $choice)
            <tr>
                <td class="border-t-0 px-6 align-middle border-l border-r  border-blueGray-100 text-xs whitespace-nowrap p-4 text-center text-blueGray-700 w-1">
                    {{$loop->index + 1}}
                </td>
                <td class="border-t-0 px-6 align-middle border-l border-r  border-blueGray-100 text-xs whitespace-nowrap p-4 text-start text-blueGray-700 w-full">
                    {{$choice->choice_text}}
                </td>
            </tr>
        @endforeach
        </tbody>

    </table>
</div>


