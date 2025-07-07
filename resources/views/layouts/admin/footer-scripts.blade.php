<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js" integrity="sha512-v2CJ7UaYy4JwqLDIrZUI/4hqeoQieOmAZNXBeQyjo21dadnwR+8ZaIJVT8EE2iyI61OV8e6M8PP2/4hpQINQ/g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/2.1.4/toastr.min.js" integrity="sha512-lbwH47l/tPXJYG9AcFNoJaTMhGvYWhVM9YI43CT+uteTRRaiLCui8snIgyAN8XWgNjNhCqlAUdzZptso6OCoFQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script>
    window.addEventListener("DOMContentLoaded", function () {
        const userId = document.querySelector('meta[name="user-id"]').getAttribute('content');

        if (window.Echo) {
            console.log(window)
            window.Echo.private(`job.admin.${userId}`)
                .listen('.JobUpdated', (e) => {

                    toastr.options.timeOut = 10000;
                    toastr.options.progressBar = true;

                    if (document.documentElement.dir === "rtl")
                        toastr.options.positionClass = "toast-top-left";

                    const method = e.status === 'completed' ? toastr.success : toastr.error;
                    e.messages.forEach(message => method(message));
                    setTimeout(() => {
                            window.location.reload();
                        }, 6000);
                });
        } else {
            console.error("Echo not available");
        }
    });

</script>
