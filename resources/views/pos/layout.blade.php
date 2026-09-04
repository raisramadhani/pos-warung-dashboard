@php
    $settings = app(\App\Settings\GeneralSettings::class);
    $storage = \Illuminate\Support\Facades\Storage::disk('public');
    $brandName = $settings->brandName ?: config('app.name');
    $favicon = $settings->favicon ? $storage->url($settings->favicon) : Vite::asset('resources/images/favicon.ico');
@endphp
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'POS') - {{ $brandName }}</title>
    @if ($favicon)
        <link rel="icon" href="{{ $favicon }}" />
    @endif
    <script>
        if (localStorage.getItem('darkMode') === 'true') {
            document.documentElement.classList.add('dark');
        }
    </script>
    @vite(['resources/css/pos.css', 'resources/js/pos-bluetooth.js', 'resources/js/pos-date-range-picker.js', 'resources/js/server-clock.js', 'resources/js/pos.js'])
</head>

<body
    class="bg-gray-100 dark:bg-gray-900 text-textDark dark:text-gray-100 h-screen w-screen overflow-hidden flex flex-col font-sans transition-colors duration-200 @yield('body-class', '')">
    @yield('body')
</body>

</html>
