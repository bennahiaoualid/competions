<div class="block w-full overflow-x-auto">
    <table class="items-center bg-transparent w-full border-collapse ">
        <thead>
        <tr>
            <th class="px-6 max-w-10 bg-blueGray-50 text-center text-blueGray-500 align-middle border border-solid border-blueGray-100 py-3 text-xs uppercase border-l-0 border-r-0 whitespace-nowrap font-semibold text-left">
                {{__('messages.global.no')}}
            </th>
            <th class="px-6 min-w-[60%] bg-blueGray-50 text-center text-blueGray-500 align-middle border border-solid border-blueGray-100 py-3 text-xs uppercase border-l-0 border-r-0 whitespace-nowrap font-semibold text-left">
               {{__('competition.question.question_text')}}
            </th>
            <th class="px-6 w-1/6 bg-blueGray-50 text-center text-blueGray-500 align-middle border border-solid border-blueGray-100 py-3 text-xs uppercase border-l-0 border-r-0 whitespace-nowrap font-semibold text-left">
                {{__('competition.question.max_score')}}
            </th>
            <th class="px-6 w-1/6 bg-blueGray-50 text-center text-blueGray-500 align-middle border border-solid border-blueGray-100 py-3 text-xs uppercase border-l-0 border-r-0 whitespace-nowrap font-semibold text-left">
                {{__('competition.question.duration')}}
            </th>
            @if($questions->count() != 0)
                <th class="px-6 w-1/6 bg-blueGray-50 text-center text-blueGray-500 align-middle border border-solid border-blueGray-100 py-3 text-xs uppercase border-l-0 border-r-0 whitespace-nowrap font-semibold text-left">

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
                            <x-text-input name="duration[]" type="number" min="1"
                                          lang="en" value="1" class="mt-1 block w-full" />
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
           {{-- @foreach($competition->levels as $level)
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
            --}}
        @endif
        </tbody>

    </table>
</div>
