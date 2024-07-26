<script>
    @if (session()->has('messages'))
        toastr.options.timeOut = 10000;
        toastr.options.progressBar = true;
        if (document.documentElement.dir === "rtl")
            toastr.options.positionClass = "toast-top-left";
        let type;
        @foreach(session()->get('messages') as $message)
            type = "{{$message['alert-type'] }}"
            switch (type) {
                case 'info':
                    toastr.info("{{ $message['message'] }}");
                    break;

                case 'success':
                    toastr.success("{{ $message['message'] }}");
                    break;

                case 'warning':
                    toastr.warnings("{{ $message['message'] }}");
                    break;

                case 'error':
                    toastr.error("{{ $message['message'] }}");
                    break;
            }
      @endforeach
    @endif
</script>

