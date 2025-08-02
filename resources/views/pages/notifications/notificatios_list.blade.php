@extends('layouts.'.$type.'.master')
@section('css')

    @section('title')
        {{ __('notifications.notifications') }}
    @stop
@endsection

@section('content')

    <div class="flex justify-between items-center my-2 p-4 shadow-sm">
        <h1 class="text-xl font-bold">{{ __('notifications.notifications') }}</h1>
        <div x-data>
            <x-button
                name="delete_notifications_modale"
                color_type="danger"
                x-on:click="$dispatch('open-modal', { detail: 'delete_notifications_modal' })">
                <x-slot:icon>
                    <i class="fa-solid fa-trash me-2"></i>
                </x-slot:icon>
                {{__("form.actions.delete_all")}}
            </x-button>
        </div>
    </div>

    <div class="grid sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-4 gap-4 max-w-full mt-4">
        @foreach ($notifications as $notification)
            <x-ui_widgets.notification_card
                :type="$notification['notification_priority_type']"
                :title="$notification['title']"
                :detailsUrl="$notification['link']"
                :message="$notification['message']"
                :deleteRoute="route('notifications.destroy')"
                :notificationId="$notification['id']"
                :createdAt="$notification['created_at']"
                :icon="$notification['icon'] ?? null"
            />
        @endforeach
    </div>
    <div class="mt-2">
        <x-pagination :paginator="$notifications" />
    </div>

    <x-modal name="delete_notifications_modal" title="My Modal" :show="false">
        <x-slot:modalhead>
            {{__("form.notification.delete")}}
        </x-slot>
        <form id="delete_notifications" method="post" action="{{route("notifications.bulkDelete")}}" class="space-y-2">
            @csrf
            @method('DELETE')

            <div>
                <input type="hidden" name="notification_ids" x-model="inputValue"/>
                <p class=""> {{__("form.actions.confirm_delete")}}</p>
            </div>
        </form>
        <x-slot:modalfooter>
            <div class="flex justify-end">
                <x-button form="delete_notifications" color_type="danger" >{{ __('form.actions.delete') }}</x-button>
            </div>
        </x-slot>
    </x-modal>
@endsection 