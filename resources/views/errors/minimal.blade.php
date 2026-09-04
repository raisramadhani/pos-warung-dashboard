<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex, nofollow">

        <title>@yield('title')</title>

        @vite('resources/css/errors.css')
    </head>
    <body class="antialiased bg-[#FBFBFA] text-[#111111] dark:bg-[#0A0A0A] dark:text-[#E5E5E5] font-sans min-h-dvh flex flex-col items-start justify-center px-6 sm:px-12 lg:px-20">
        <main role="main" class="w-full max-w-2xl">
            <p class="font-mono text-[8rem] sm:text-[12rem] tracking-[-0.08em] leading-none text-[#111111] dark:text-[#F5F5F5] select-none">
                @yield('code')
            </p>

            <div class="w-12 h-px bg-[#E5E5E5] dark:bg-[#2A2A2A] my-6"></div>

            <p class="text-lg sm:text-xl text-[#787774] dark:text-[#8A8A8A] leading-relaxed max-w-md">
                @yield('message')
            </p>

            <a href="{{ route('home') }}" class="inline-block mt-10 text-sm text-[#787774] dark:text-[#8A8A8A] hover:text-[#111111] dark:hover:text-[#F5F5F5] underline underline-offset-4 decoration-[#E5E5E5] dark:decoration-[#2A2A2A] hover:decoration-[#111111] dark:hover:decoration-[#F5F5F5] transition-colors duration-200">
                Kembali ke beranda
            </a>
        </main>
    </body>
</html>
