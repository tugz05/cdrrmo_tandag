<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link
            href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap"
            rel="stylesheet">

        <link rel="stylesheet" href="{{ asset('vendor/atlassian-design-for-bootstrap-2.0.2/fastbootstrap.min.css') }}">
        <link rel="stylesheet" href="{{ asset('vendor/bootstrap-5-select/dselect.css') }}">

        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <script src="{{ asset('vendor/jquery/jquery-3.7.0.js') }}"></script>

        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead

        <link rel="stylesheet" href="{{ asset('styles/styles.css') }}">

    </head>
    <body class="font-sans antialiased">
        @inertia


        <script src="{{ asset('vendor/atlassian-design-for-bootstrap-2.0.2/fastbootstrap.min.js') }}"></script>

        <script src="{{ asset('vendor/bootstrap-5-select/dselect.js') }}"></script>


        <script>

            let menuToggle = document.querySelectorAll('.toggle');
            menuToggle.onClick = function() {
                console.log('hey')
                // menuToggle.className.toggle('active');
            }

            let list = document.querySelectorAll('.list');
            for(let i=0; i<list.length; i++) {
                list[i].onClick = function() {
                    let j = 0;
                    while(j < list.length) {
                        list[j++].className = 'list';
                    }
                    list[i].className = 'list active';
                }
            }
        </script>
    </body>
</html>
