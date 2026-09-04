<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>@yield('title') — {{ config('app.name', 'Abra POS') }}</title>

        @vite('resources/css/errors.css')
    </head>
    <body class="antialiased bg-[#FBFBFA] text-[#111111] dark:bg-[#0A0A0A] dark:text-[#E5E5E5] min-h-dvh flex flex-col justify-center px-6 sm:px-12 lg:px-20" role="main">

        <main class="max-w-lg">
            <p class="text-lg sm:text-xl text-[#787878] dark:text-[#8A8A8A] max-w-sm leading-relaxed">
                @yield('message')
            </p>

            <a href="{{ route('home') }}" class="inline-block mt-8 text-sm text-[#A3A3A3] dark:text-[#525252] hover:text-[#525252] dark:hover:text-[#A3A3A3] underline underline-offset-4 decoration-[#E5E5E5] dark:decoration-[#262626] transition-colors duration-200">
                Kembali ke beranda
            </a>
        </main>

    </body>
</html>
