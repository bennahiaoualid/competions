<div class="block w-full overflow-x-auto">
    <table class="items-center bg-transparent w-full border-collapse ">
        <thead>
        <tr>
            <th class="px-2 bg-slate-300 text-blueGray-500 align-middle border border-solid border-blueGray-100 py-3 text-xs uppercase border-l-0 border-r-0 whitespace-nowrap font-semibold text-center" style="width: 60px">
                {{__('messages.global.no')}}
            </th>
            <th class="px-2 bg-slate-300 text-blueGray-500 align-middle border border-solid border-blueGray-100 py-3 text-xs uppercase border-l-0 border-r-0 whitespace-nowrap font-semibold text-center" style="width: auto">
                {{__('competition.question.question_text')}}
            </th>
            <th class="px-2 bg-slate-300 text-blueGray-500 align-middle border border-solid border-blueGray-100 py-3 text-xs uppercase border-l-0 border-r-0 whitespace-nowrap font-semibold text-center" style="width: auto">
                {{__('competition.question.response')}}
            </th>
            <th class="px-2 bg-slate-300 text-blueGray-500 align-middle border border-solid border-blueGray-100 py-3 text-xs uppercase border-l-0 border-r-0 whitespace-nowrap font-semibold text-center" style="width: 100px">
                {{__('competition.question.max_score')}}
            </th>
            <th class="px-2 bg-slate-300 text-blueGray-500 align-middle border border-solid border-blueGray-100 py-3 text-xs uppercase border-l-0 border-r-0 whitespace-nowrap font-semibold text-center" style="width: 120px">
                {{__('competition.question.duration') .' ( '. __('messages.global.second').' )'}}
            </th>
            <th class="px-2 bg-slate-300 text-blueGray-500 align-middle border border-solid border-blueGray-100 py-3 text-xs uppercase border-l-0 border-r-0 whitespace-nowrap font-semibold text-center">

            </th>
        </tr>
        </thead>

        <tbody>
        @if($questions->count() == 0)
            <tr>
                <td colspan="4" class="text-center">
                    <div class="text-gray-500 mb-4">
                        <i class="fas fa-folder-open text-6xl"></i>
                    </div>
                    <div class="text-gray-700 text-lg font-semibold">
                        {{__('messages.global.no_records')}}
                    </div>
                </td>
            </tr>
        @else
            @foreach($questions as $question)
                <form id="add" method="post" action="{{route('admin.competitions.level.question.update',['question' => $question])}}">
                    @csrf
                    @method("patch")
                        <tr>
                            @php
                                // Calculate the global index
                                $globalIndex = 0;
                                if($questions instanceof \Illuminate\Pagination\Paginator || $questions instanceof \Illuminate\Pagination\LengthAwarePaginator)
                                    $globalIndex = ($questions->currentPage() - 1) * $questions->perPage();
                            @endphp

                            <td class="text-center">
                                {{($loop->index + 1 + $globalIndex) }}
                            </td>
                            <td class="px-1">
                                <x-text-area name="question_text"  class="mt-1 block w-full" >{{ trim($question->question_text) }}</x-text-area>
                                <x-input-error :messages="$errors->getBag('updateQuestion' . $question->id)->get('question_text')" class="mt-2" />
                            </td>
                            <td class="px-1">
                                <x-text-area name="perfect_response"  class="mt-1 block w-full" >{{ trim($question->perfect_response) }}</x-text-area>
                                <x-input-error :messages="$errors->getBag('updateQuestion' . $question->id)->get('perfect_response')" class="mt-2" />
                            </td>
                            <td class="px-1 align-top">
                                <x-text-input name="max_score" type="number" min="1"
                                            lang="en" value="{{$question->max_score}}" class="mt-1 block w-full" />
                                <x-input-error :messages="$errors->getBag('updateQuestion' . $question->id)->get('max_score')" class="mt-2" />
                            </td>
                            <td class="px-1 align-top">
                                <x-text-input name="duration" type="number" min="1"
                                            lang="en" value=" {{$question->duration}}" class="mt-1 block w-full" />
                                <x-input-error :messages="$errors->getBag('updateQuestion' . $question->id)->get('duration')" class="mt-2" />
                            </td>
                            <td class="align-middle">
                                <div class="flex justify-center items-center">
                                    <x-button color_type="primary">
                                        {{ __("form.actions.update") }}
                                    </x-button>
                                </div>
                            </td>
                        </tr>
                </form>
            @endforeach
        @endif
        </tbody>

    </table>
</div>
