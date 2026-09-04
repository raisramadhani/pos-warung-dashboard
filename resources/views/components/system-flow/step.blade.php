@props([
    'icon' => 'tabler-circle-check',
    'title' => '',
    'description' => null,
    'note' => null,
    'last' => false,
])

<div class="relative flex gap-4">
    @unless ($last)
        <span class="absolute left-[22px] top-12 bottom-0 w-px bg-gray-200 dark:border-white/10" aria-hidden="true"></span>
    @endunless

    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-gray-100 text-gray-600 dark:bg-white/5 dark:text-gray-400">
        <x-dynamic-component :component="$icon" class="h-5 w-5" />
    </div>

    <div class="min-w-0 flex-1 pb-1">
        <h3 class="text-sm font-semibold text-gray-950 dark:text-white">{{ $title }}</h3>
        @if ($description)
            <p class="mt-1 text-sm leading-relaxed text-gray-500 dark:text-gray-400">{!! $description !!}</p>
        @endif
        @if ($note)
            <div class="mt-2 rounded-lg bg-warning-50 text-sm text-warning-700 ring-1 ring-warning-600/10 dark:bg-warning-400/10 dark:text-warning-400 dark:ring-warning-400/20">
                <div class="flex gap-2 px-3 py-2">
                    <x-tabler-alert-circle class="mt-0.5 h-4 w-4 shrink-0" />
                    <span>{{ $note }}</span>
                </div>
            </div>
        @endif
    </div>
</div>
