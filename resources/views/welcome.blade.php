<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#f8fafc">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="default">
        <meta name="apple-mobile-web-app-title" content="SunTrack">
        <title>SunTrack</title>
        <link rel="icon" href="{{ asset('favicon.png') }}" type="image/png">
        <link rel="apple-touch-icon" href="{{ asset('images/PWA_icon.png') }}">
        <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
        <script>
            (() => {
                let theme = 'light';

                try {
                    theme = localStorage.getItem('suntrack_theme') === 'dark' ? 'dark' : 'light';
                } catch {}

                document.documentElement.dataset.theme = theme;
                document.documentElement.style.colorScheme = theme;
                document.querySelector('meta[name="theme-color"]')
                    ?.setAttribute('content', theme === 'dark' ? '#0b1120' : '#f8fafc');
            })();
        </script>
        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-page text-content antialiased h-screen">
        <div id="app"></div>
    </body>
</html>
