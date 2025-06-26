@if($row->error_message)
    <h4 class="text-red-500">{{__('job.fields.error_message')}}</h4>
    <p class="">{{$row->error_message}}</p>
@elseif($row->result)
    <h4 class="text-green-500">{{__('job.fields.result')}}</h4>
    <ul class="list-disc list-inside">
        @foreach($row->result as $key => $value)
            <li>{{$key}} : {{$value}}</li>
        @endforeach
    </ul>
@endif



