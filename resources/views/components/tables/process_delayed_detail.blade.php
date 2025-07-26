@if($row->context_data)
    @php
        $data = $row->context_data;
    @endphp
    @if(is_array($data))
    <h4 class="text-primary bold">{{ __('delayed_process.fields.context_data') }}</h4>
    <table class="table-auto w-full text-sm">
        <tbody>
        @foreach($data as $key => $value)
            <tr>
                <td class="font-semibold pr-2">
                    {{ __('delayed_process.context.' . $key) ?? \Illuminate\Support\Str::headline($key) }}
                </td>
                <td>
                    @if(is_array($value))
                        {{ implode(', ', $value) }}
                    @elseif(\Illuminate\Support\Str::endsWith($key, '_at') && strtotime($value))
                        {{ \Carbon\Carbon::parse($value)->translatedFormat('Y-m-d H:i') }}
                    @else
                        {{ $value }}
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
    @endif
@endif