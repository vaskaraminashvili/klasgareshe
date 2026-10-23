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
                var accents = { violet: 1, pink: 1, mint: 1, sky: 1, sun: 1 };
                var accent = localStorage.getItem('accent') || 'violet';
                if (!accents[accent]) accent = 'violet';
                document.documentElement.setAttribute('data-accent', accent);
                var size = localStorage.getItem('textSize') || 'md';
                if (size !== 'sm' && size !== 'md' && size !== 'lg') size = 'md';
                document.documentElement.setAttribute('data-text', size);
            }

            applyTheme();

            document.addEventListener('livewire:navigating', function(e) {
                if (e.detail && typeof e.detail.onSwap === 'function') {
                    e.detail.onSwap(applyTheme);
                }
            });
        })();
    </script>

    @php
        $kidzioSoundPack = 'default';
        $kidzioLayoutUser = auth()->user();
        if ($kidzioLayoutUser instanceof \App\Models\User) {
            $kidzioSoundPack = app(\App\Services\ShopService::class)->equippedSoundPack($kidzioLayoutUser);
        }
    @endphp
    <meta name="kidzio-audio-base" content="{{ asset('assets/audio') }}" />
    <meta name="kidzio-sound-pack" content="{{ $kidzioSoundPack }}" />
    <meta name="kidzio-share-url" content="{{ route('user-login') }}" />
    <meta name="kidzio-share-text" content="{{ __('share.text') }}" />
    <meta name="kidzio-share-title" content="{{ __('share.title') }}" />
    <meta name="kidzio-share-copied" content="{{ __('share.copied') }}" />
    <meta name="kidzio-speak-unavailable" content="{{ __('share.unavailable') }}" />
    <link rel="preload" href="{{ asset('assets/audio/'.$kidzioSoundPack.'/correct.wav') }}" as="audio" />
    <link rel="preload" href="{{ asset('assets/audio/'.$kidzioSoundPack.'/incorrect.wav') }}" as="audio" />
    <link rel="preload" href="{{ asset('assets/audio/'.$kidzioSoundPack.'/levelup.wav') }}" as="audio" />

    <title>{{ $title ?? config('app.name') }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    @auth
        @if (filled(config('webpush.vapid.public_key')))
            @php
                $pushUser = auth()->user();
            @endphp
            @if ($pushUser instanceof \App\Models\User && app(\App\Services\KidSetupService::class)->isFullySetUp($pushUser) && app(\App\Services\NotificationService::class)->wantsAnyDelivery($pushUser))
                <meta name="vapid-public-key" content="{{ config('webpush.vapid.public_key') }}" />
            @endif
        @endif
    @endauth
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
    @if (filled(config('webpush.vapid.public_key')))
        <script src="{{ asset('assets/js/push.js') }}" data-navigate-once></script>
    @endif
    @stack('scripts')
</body>

</html>
