@props(['disabled' => false, "placeholder" ,"options"=>[]])


<div class="relative mb-2 mt-2">
    <select
        {{ $disabled ? 'disabled' : '' }}
        {!! $attributes->merge(['class' => 'text-gray-600 focus:outline-none focus:border focus:border-indigo-700 font-normal w-full h-10 flex items-center  text-sm border-gray-300 rounded border' ])!!}>
        @isset($placeholder)
            <option value="" >{{ $placeholder }} </option>
        @endisset
        @foreach($options as $option)
            <option value="{{$option["value"]}}" @if($option["selected"]) selected @endif>{{$option["text"]}}</option>
        @endforeach
    </select>
</div>
