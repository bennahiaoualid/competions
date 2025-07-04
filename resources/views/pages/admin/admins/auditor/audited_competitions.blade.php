@extends('layouts.admin.master')
@section('css')

    @section('title')
        {{__('links.competition.auditing_responses')}}
    @stop
@endsection

@section('page_title')
    {{ __('competition.info.auditor.in_competition') }}
@endsection

@section('content')
    <div class="flex justify-between items-center my-2 p-4 shadow-sm">
        <h1 class="text-xl font-bold">{{__('links.competition.list')}}</h1>
        <div x-data>
            <x-button
                name="myModal"
                x-on:click="$dispatch('open-modal', { detail: 'filter' })">
                <x-slot:icon>
                    <i class="fa-solid fa-filter me-2"></i>
                </x-slot:icon>
                {{__("form.actions.filter")}}
            </x-button>
        </div>
    </div>

    <div class="">
        @foreach($competitions as $competition)
            <x-collapsible-card :title="$competition->title . ' | ' . $competition->status" type="info">
                @include('pages.admin.admins.auditor.levels_audit_list')
            </x-collapsible-card>
        @endforeach
        <div class="mt-6">
            <x-pagination :paginator="$competitions" />
        </div>
    </div>

    <!-- filter form -->
    <x-modal name="filter" title="My Modal" :show="$errors->hasBag('filterCompetitions')">
        <x-slot:modalhead>
            {{__("form.filter.filter")}}
        </x-slot>
        <form id="filter" method="post" action="{{ route('admin.auditor.competition.filtred') }}" class="space-y-2">
            @csrf
            @method('post')

            <div>
                <x-input-label for="title" :value=" ucwords(__('competition.info.title'))" />
                <x-text-input id="title" name="title" type="text" class="mt-1 block w-full"  />
                <x-input-error :messages="$errors->filterCompetitions->get('title')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="start_date" :value=" ucwords(__('competition.info.start_date'))" />
                <div class="flex flex-col sm:flex-row justify-between">
                    <div>
                        <x-input-label for="start_date_from" :value=" ucwords(__('competition.info.from'))" />
                        <x-text-input id="start_date_from" name="start_date_from" type="text" lang="en"
                                      class="mt-1 block w-full date-input"  />
                        <x-input-error :messages="$errors->filterCompetitions->get('age_start')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="start_date_to" :value=" ucwords(__('competition.info.to'))" />

                        <x-text-input id="start_date_to" name="start_date_to" type="text" lang="en"
                                      class="mt-1 block w-full date-input" />
                        <x-input-error :messages="$errors->filterCompetitions->get('age_end')" class="mt-2" />
                    </div>
                </div>
            </div>

            <div>
                <x-input-label for="age_start" :value=" ucwords(__('competition.info.users_age'))" />
                <div class="flex flex-col sm:flex-row justify-between">
                    <div>
                        <x-text-input id="age_start" name="age_start" type="number" min="6" lang="en"
                                      class="mt-1 block w-full" :placeholder="__('competition.info.age_start')"  />
                        <x-input-error :messages="$errors->filterCompetitions->get('age_start')" class="mt-2" />
                    </div>
                    <div>
                        <x-text-input id="age_end" name="age_end" type="number" min="6" lang="en"
                                      class="mt-1 block w-full" :placeholder="__('competition.info.age_end')"  />
                        <x-input-error :messages="$errors->filterCompetitions->get('age_end')" class="mt-2" />
                    </div>
                </div>
            </div>
            <div>
                <x-input-label for="status" :value=" ucwords(__('competition.info.status.state'))" />
                <x-form.select-box id="status" name="status"  :options="[
                    ['value' => '', 'text' => __('form.filter.all'), 'selected' => true],
                    ['value' => 'pending', 'text' => __('competition.info.status.pending'), 'selected' => false],
                    ['value' => 'active', 'text' => __('competition.info.status.active'), 'selected' => false],
                    ['value' => 'finished', 'text' => __('competition.info.status.finished'), 'selected' => false],
                ]">
                </x-form.select-box>
                <x-input-error :messages="$errors->filterCompetitions->get('status')" class="mt-2" />
            </div>
        </form>
        <x-slot:modalfooter>
            <div class="flex justify-end">
                <x-button form="filter" color_type="success" >{{ __('form.actions.save') }}</x-button>
            </div>
        </x-slot>
    </x-modal>
@endsection
@section('custom_js')
    <script>
        flatpickr(".date-input", {
            enableTime: true,
            dateFormat: "Y-m-d",
            locale: "en"
        });
    </script>
@endsection
