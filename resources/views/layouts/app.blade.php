<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">

    <title>{{ $title ?? config('app.name') }}</title>
    <meta name="theme-color" content="#FFF8F1" />
    <link rel="stylesheet" href="{{ asset('assets/icons/regular/style.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/icons/fill/style.css') }}" />
    <link
        href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@600;700;800&amp;family=Nunito:wght@400;600;700;800&amp;display=swap"
        rel="stylesheet" />
    <link rel="icon" type="image/png" href="{{ asset('assets/images/icon.png') }}" />

    <!-- Pre-paint theme script (prevents FOUC). Matches crypto/ pattern. -->
    <script>
        (function() {
            var saved = localStorage.getItem('theme');
            var theme = saved ||
                (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            if (theme === 'dark') document.documentElement.classList.add('dark');
            else document.documentElement.classList.remove('dark');
            var meta = document.querySelector('meta[name="theme-color"]');
            if (meta) meta.setAttribute('content', theme === 'dark' ? '#0F0B22' : '#FFF8F1');
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <link href="{{ asset('assets/css/index.css') }}" rel="stylesheet">

    @livewireStyles
</head>

<body>
    {{ $slot }}

    @livewireScripts
</body>


<script src="{{ asset('assets/js/app.js') }}"></script>
<script defer src="{{ asset('assets/js/index.js') }}"></script>

</html>
