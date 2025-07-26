@props([
    'title',
    'message',
    'type' => 'info', // info, success, warning, danger
    'deleteRoute' => null,
    'notificationId' => null,
    'detailsUrl' => null,
    'createdAt' => null
])

@php
    $styles = [
        'info' => [
            'icon' => 'fas fa-info-circle',
            'color' => 'text-info',
            'border' => 'border-t-4 border-info',
            'button' => 'text-info hover:bg-info/10 border-info',
        ],
        'success' => [
            'icon' => 'fas fa-check-circle',
            'color' => 'text-success',
            'border' => 'border-t-4 border-success',
            'button' => 'text-success hover:bg-success/10 border-success',
        ],
        'warning' => [
            'icon' => 'fas fa-exclamation-triangle',
            'color' => 'text-warning',
            'border' => 'border-t-4 border-warning',
            'button' => 'text-warning hover:bg-warning/10 border-warning',
        ],
        'danger' => [
            'icon' => 'fas fa-times-circle',
            'color' => 'text-danger',
            'border' => 'border-t-4 border-danger',
            'button' => 'text-danger hover:bg-danger/10 border-danger',
        ],
    ];

    $style = $styles[$type];
    $formId = 'delete-notification-' . ($notificationId ?? uniqid());
@endphp

<div class="bg-white rounded shadow-sm p-4 flex justify-between items-start {{ $style['border'] }}">
    <div class="flex gap-3">
        <i class="{{ $style['icon'] }} {{ $style['color'] }} text-xl mt-1"></i>
        <div>
            <h3 class="font-semibold {{ $style['color'] }}">{{ $title }}</h3>
            <p class="text-gray-700 text-sm mt-1">{{ $message }}</p>
            <div class="flex justify-between items-center">
                <p class="text-xs text-gray-500">
                    {{ $createdAt}}  
                </p>
                @if ($detailsUrl)
                    <a href="{{ $detailsUrl }}"
                        class="inline-block mt-2 text-sm border rounded px-3 py-1 {{ $style['button'] }}">
                        <i class="fas fa-link mr-1"></i> {{ __('notifications.link_text.detail') }}
                    </a>
                @endif
            </div>
        </div>
    </div>

    @if ($deleteRoute)
        <form id="{{ $formId }}" action="{{ $deleteRoute }}" method="POST" class="hidden">
            @csrf
            @method('DELETE')
            @if ($notificationId)
                <input type="hidden" name="notification_id" value="{{ $notificationId }}">
            @endif
        </form>

        <button type="button"
                class="text-gray-400 hover:text-danger ml-3"
                title="Delete notification"
                onclick="document.getElementById('{{ $formId }}').submit();">
            <i class="fas fa-trash-alt"></i>
        </button>
    @endif
</div>
