@if (config_item('csrf_protection'))
    <!-- CSRF Token -->
    <script type="text/javascript">
        var csrfParam = "{{ $token_name }}";
        var csrfVal = "{{ $token_value }}";

        function getCsrfToken() {
            const cookieEntry = document.cookie
                .split('; ')
                .find((entry) => entry.startsWith(`${csrfParam}=`));
            if (cookieEntry) {
                return decodeURIComponent(cookieEntry.split('=')[1]);
            }

            return csrfVal;
        }
    </script>
    <!-- jQuery Cookie -->
    <script src="{{ asset('bootstrap/js/jquery.cookie.min.js') }}"></script>
    <script src="{{ asset('js/anti-csrf.js') }}"></script>
@endif
