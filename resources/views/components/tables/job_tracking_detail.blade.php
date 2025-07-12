@if($row->error_message)
    <h4 class="text-red-500">{{ __('job.fields.error_message') }}</h4>

    @php
        $error = json_decode($row->error_message, true);
    @endphp

    @if(is_array($error) && isset($error['key']))
        @php
            
            $data = $error['data'] ?? [];
            foreach ($data as $k => $v) {
                if (is_array($v)) {
                    $data[$k] = implode(', ', $v);
                }
            }
        @endphp
        <p>{{ __($error['key'], $data) }}</p>
    @else
        <p>{{ $row->error_message }}</p>
    @endif
@endif

@if($row->result)
    @php
        $result = $row->localized_result;
    @endphp
    <h4 class="{{ $row->status === 'failed' ? 'text-red-500' : 'text-green-500' }}">{{ __('job.fields.result') }}</h4>
    <ul class="list-disc list-inside">
        @foreach($result as $key => $value)
            <li>{{ $key }} : {{ $value }}</li>
        @endforeach
    </ul>
@endif


