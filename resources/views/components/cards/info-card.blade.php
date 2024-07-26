@php
    $typeClasses = [
        'success' => 'bg-green-500',
        'danger' => 'bg-red-500',
        'info' => 'bg-cyan-500',
        'warning' => 'bg-yellow-500',
    ];
     $typeClasses_dark = [
        'success' => 'bg-green-600',
        'danger' => 'bg-red-600',
        'info' => 'bg-cyan-600',
        'warning' => 'bg-yellow-600',
    ];
      $typeClasses_icon = [
        'success' => 'text-green-600',
        'danger' => 'text-red-600',
        'info' => 'text-cyan-600',
        'warning' => 'text-yellow-600',
    ];
    $sizeClasses = [
        'sm' => 'p-2 text-sm',
        'md' => 'p-4 text-base',
        'lg' => 'p-6 text-lg',
    ];
    $bgColor = $typeClasses[$type] ?? 'bg-gray-500';
    $bgColorIcon = $typeClasses_icon[$type] ?? 'bg-gray-500';
    $bgColorDark = $typeClasses_dark[$type] ?? 'bg-gray-6000';
    $sizeClass = $sizeClasses[$size] ?? 'p-4 text-base';
@endphp

<div {{$attributes->merge(["class" => $bgColor. " rounded shadow-lg flex flex-col"])}}>
    <div class="{{$sizeClass}} space-y-2 relative">
        <div class="text-white text-3xl">{{ $value }}</div>
        <div class="text-white">{{ $content }}</div>
        <div class="{{$bgColorIcon}} text-5xl absolute top-1/2 end-2 -translate-y-1/2">
            @isset($icon)
            {{$icon}}
            @endisset
        </div>
    </div>
    <a href="#" class="{{$bgColorDark}} block text-white text-center py-1">
        More info <i class="fas fa-arrow-circle-right"></i>
    </a>
</div>
