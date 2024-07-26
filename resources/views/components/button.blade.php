@props(["tooltip" => null, "islink" => false, "color_type" => 'primary', 'outline' => false, 'disabled' => false, 'size' => 'md', 'ariaLabel' => ''])

@php
    $base_classes = "inline-flex items-center border rounded-md font-semibold uppercase cursor-pointer tracking-widest focus:outline-none focus:ring-2 focus:ring-offset-2 transition ease-in-out duration-150";

    // Size classes
    switch ($size) {
        case 'sm':
            $size_classes = "px-2 py-1 text-xs";
            break;
        case 'lg':
            $size_classes = "px-6 py-3 text-lg";
            break;
        case 'md':
        default:
            $size_classes = "px-4 py-2 text-xs";
            break;
    }

    // Outline or solid button styles
    if ($outline) {
        $color_classes = "bg-transparent text-primary border-primary hover:bg-primary hover:text-white focus:bg-primary focus:text-white active:bg-primary active:text-white focus:ring-primary";
        switch ($color_type) {
            case "success":
                $color_classes = "bg-transparent text-success border-success hover:bg-success hover:text-white focus:bg-success focus:text-white active:bg-success active:text-white focus:ring-success";
                break;
            case "warning":
                $color_classes = "bg-transparent text-warning border-warning hover:bg-warning hover:text-white focus:bg-warning focus:text-white active:bg-warning active:text-white focus:ring-warning";
                break;
            case "info":
                $color_classes = "bg-transparent text-info border-info hover:bg-info hover:text-white focus:bg-info focus:text-white active:bg-info active:text-white focus:ring-info";
                break;
            case "danger":
                $color_classes = "bg-transparent text-danger border-danger hover:bg-danger hover:text-white focus:bg-danger focus:text-white active:bg-danger active:text-white focus:ring-danger";
                break;
            case "secondary":
                $color_classes = "bg-transparent text-gray-600 border-gray-600 hover:bg-gray-600 hover:text-white focus:bg-gray-600 focus:text-white active:bg-gray-600 active:text-white focus:ring-gray-600";
                break;
        }
    } else {
        $color_classes = "bg-primary text-white border-transparent hover:bg-primary-dark focus:bg-primary-dark active:bg-primary-dark focus:ring-primary";
        switch ($color_type) {
            case "success":
                $color_classes = "bg-success text-white border-transparent hover:bg-success-dark focus:bg-success-dark active:bg-success-dark focus:ring-success";
                break;
            case "warning":
                $color_classes = "bg-warning text-white border-transparent hover:bg-warning-dark focus:bg-warning-dark active:bg-warning-dark focus:ring-warning";
                break;
            case "info":
                $color_classes = "bg-info text-white border-transparent hover:bg-info-dark focus:bg-info-dark active:bg-info-dark focus:ring-info";
                break;
            case "danger":
                $color_classes = "bg-danger text-white border-transparent hover:bg-danger-dark focus:bg-danger-dark active:bg-danger-dark focus:ring-danger";
                break;
            case "secondary":
                $color_classes = "bg-gray-600 text-white border-transparent hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-700 focus:ring-gray-600";
                break;
        }
    }

    $disabled_classes = $disabled ? 'opacity-50 cursor-not-allowed' : '';
@endphp

@if($islink)
    <a {{ $attributes->merge(['class' => $base_classes . ' ' . $size_classes . ' ' . $color_classes . ' ' . $disabled_classes]) }} {{ $disabled ? 'aria-disabled=true' : '' }} aria-label="{{ $ariaLabel }}">
        @isset($icon)
           {{ $icon }}
        @endisset
        {{ $slot }}
    </a>
@else
    <button @if($tooltip) title="{{$tooltip}}" @endif {{ $attributes->merge(['type' => 'submit', 'class' => $base_classes . ' ' . $size_classes . ' ' . $color_classes . ' ' . $disabled_classes]) }} {{ $disabled ? 'disabled' : '' }} aria-label="{{ $ariaLabel }}">
        @isset($icon)
            {{ $icon }}
        @endisset
        {{ $slot }}
    </button>
@endif

