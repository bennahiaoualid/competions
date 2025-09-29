@if($users->count() > 0)
<div class="block w-full overflow-x-auto">
    <table class="items-center bg-transparent w-full border-collapse ">
        <thead>
        <tr>
            <th class="px-6 bg-slate-300 text-blueGray-500 align-middle border border-solid border-blueGray-100 py-3 text-xs uppercase border-l-0 border-r-0 whitespace-nowrap font-semibold text-center">
                {{__('messages.global.order')}}
            </th>
            <th class="px-6 bg-slate-300 text-blueGray-500 align-middle border border-solid border-blueGray-100 py-3 text-xs uppercase border-l-0 border-r-0 whitespace-nowrap font-semibold text-center">
                {{__('competition.info.competitor')}}
            </th>
            <th class="px-6 bg-slate-300 text-blueGray-500 align-middle border border-solid border-blueGray-100 py-3 text-xs uppercase border-l-0 border-r-0 whitespace-nowrap font-semibold text-center">
                {{__('competition.response.score')}}
            </th>
        </tr>
        </thead>

        <tbody>
        @foreach($users as $user)
            @php
                // Calculate the global index
                $globalIndex = 0;
                if($users instanceof \Illuminate\Pagination\Paginator || $users instanceof \Illuminate\Pagination\LengthAwarePaginator)
                    $globalIndex = ($users->currentPage() - 1) * $users->perPage();
            @endphp
        <tr>
            <td class="border-t-0 px-6 align-middle border-l-0 border-r-0 text-xs whitespace-nowrap p-4 text-center">
                {{$loop->index + 1 + $globalIndex}}
            </td>
            <td class="border-t-0 px-6 align-middle border-l-0 border-r-0 text-xs whitespace-nowrap p-4 text-center">
                {{$user->name}}
            </td>
            <td class="border-t-0 px-6 align-middle border-l-0 border-r-0 text-xs whitespace-nowrap p-4 text-center">
                {{number_format($user->total_score,2)}}
            </td>
        </tr>
        @endforeach
        </tbody>
    </table>
    @if($users instanceof \Illuminate\Pagination\LengthAwarePaginator)
        <x-pagination :paginator="$users" />
    @endif
</div>
@else
    <div class="text-center">
        <div class="text-gray-500 mb-4">
            <i class="fa-solid fa-hourglass-start text-6xl text-primary animate-spin"></i>
        </div>
        <div class="text-gray-700 text-lg font-semibold capitalize">
            {{__('messages.global.coming_soon')}} ...
        </div>
    </div>
@endif
