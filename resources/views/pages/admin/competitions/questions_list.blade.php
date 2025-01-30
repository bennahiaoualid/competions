<div class="block w-full overflow-x-auto">
    <table class="items-center bg-transparent w-full border-collapse ">
        <thead>
        <tr>
            <th class="px-6 bg-slate-300 text-blueGray-500 align-middle border border-solid border-blueGray-100 py-3 text-xs uppercase border-l-0 border-r-0 whitespace-nowrap font-semibold text-center">
                {{__('messages.global.no')}}
            </th>
            <th class="px-6 bg-slate-300 text-blueGray-500 align-middle border border-solid border-blueGray-100 py-3 text-xs uppercase border-l-0 border-r-0 whitespace-nowrap font-semibold text-center">
               {{__('competition.question.question_text')}}
            </th>
            <th class="px-6 bg-slate-300 text-blueGray-500 align-middle border border-solid border-blueGray-100 py-3 text-xs uppercase border-l-0 border-r-0 whitespace-nowrap font-semibold text-center">
                {{__('competition.question.max_score')}}
            </th>
            <th class="px-6 bg-slate-300 text-blueGray-500 align-middle border border-solid border-blueGray-100 py-3 text-xs uppercase border-l-0 border-r-0 whitespace-nowrap font-semibold text-center">
                {{__('competition.question.duration') .' ( '. __('messages.global.second').' )'}}
            </th>
            @if($questions->count() != 0)
                <th class="px-6 bg-slate-300 text-blueGray-500 align-middle border border-solid border-blueGray-100 py-3 text-xs uppercase border-l-0 border-r-0 whitespace-nowrap font-semibold text-center">

                </th>
            @endif
        </tr>
        </thead>

        <tbody>
        @if($questions->count() == 0)
            <form id="add" method="post" action="{{route('admin.competitions.level.question.store')}}">
                @csrf
                @method("post")
                @for($i = 0; $i < $level->questions_number; $i++)
                    <input type="hidden" name="level_id" value="{{$level->id}}">
                    <tr>
                        <td>
                            {{"0". ($i + 1) }}
                        </td>
                        <td class="px-1">
                            <x-text-area name="question_text[]"  class="mt-1 block w-full">
                            </x-text-area>
                            <x-input-error :messages="$errors->createQuestion->get('question_text.'.$i)" class="mt-2" />
                        </td>
                        <td class="px-1">
                            <x-text-input name="max_score[]" type="number" min="1"
                                          lang="en" value="1" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->createQuestion->get('max_score.'.$i)" class="mt-2" />
                        </td>
                        <td class="px-1">
                            <x-text-input name="duration[]" type="number" min="30"
                                          lang="en" value="30" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->createQuestion->get('duration.'.$i)" class="mt-2" />
                        </td>
                    </tr>
                @endfor
            </form>
        @else
            @foreach($questions as $question)
                <form id="add" method="post" action="{{route('admin.competitions.level.question.update')}}">
                    @csrf
                    @method("patch")
                        <input type="hidden" name="id" value="{{$question->id}}">
                        <tr>
                            <td>
                                {{"0" . $loop->index + 1 }}
                            </td>
                            <td class="px-1">
                                <x-text-area name="question_text"  class="mt-1 block w-full">
                                    {{$question->question_text}}
                                </x-text-area>
                                <x-input-error :messages="$errors->getBag('updateQuestion' . $question->id)->get('question_text')" class="mt-2" />
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
                            <td>
                                <x-button color_type="primary">
                                    {{__("form.actions.update")}}
                                </x-button>
                            </td>
                        </tr>
                </form>
            @endforeach
        @endif
        </tbody>

    </table>
</div>
