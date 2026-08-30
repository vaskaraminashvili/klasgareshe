<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#FFF8F1" />

    {{-- Blocking, before any CSS: first paint and wire:navigate must see html.dark. --}}
    <script>
        (function() {
            function applyTheme() {
                var saved = localStorage.getItem('theme');
                var theme = saved ||
                    (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
                if (theme === 'dark') document.documentElement.classList.add('dark');
                else document.documentElement.classList.remove('dark');
                var meta = document.querySelector('meta[name="theme-color"]');
                if (meta) meta.setAttribute('content', theme === 'dark' ? '#0F0B22' : '#FFF8F1');
            }

            applyTheme();

            document.addEventListener('livewire:navigating', function(e) {
                if (e.detail && typeof e.detail.onSwap === 'function') {
                    e.detail.onSwap(applyTheme);
                }
            });
        })();
    </script>

    <title>{{ $title ?? config('app.name') }}</title>
    <link rel="stylesheet" href="{{ asset('assets/icons/regular/style.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/icons/fill/style.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/fonts/fonts.css') }}" />
    <link
        href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@600;700;800&amp;family=Nunito:wght@400;600;700;800&amp;display=swap"
        rel="stylesheet" />
    <link rel="icon" type="image/png" href="{{ asset('assets/images/icon.png') }}" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <link href="{{ asset('assets/css/index.css') }}" rel="stylesheet">
    @stack('styles')

    @livewireStyles
</head>

<body>
    {{ $slot }}

    @livewireScripts

    {{-- index.js exposes window.Swiper; load it before app.js so rails can init. --}}
    <script src="{{ asset('assets/js/index.js') }}" data-navigate-once></script>
    <script src="{{ asset('assets/js/app.js') }}" data-navigate-once></script>
    @stack('scripts')
</body>

</html>
