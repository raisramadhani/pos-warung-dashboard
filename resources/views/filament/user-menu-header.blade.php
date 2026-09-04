@php
    $user = auth()->user();
    $role = $user?->role;
    $roleLabel =
        $role instanceof \App\Enums\RoleType
            ? $role->getLabel()
            : ($role
                ? \App\Enums\RoleType::tryFrom($role)?->getLabel() ?? $role
                : null);
@endphp

@if ($user)
    <div class="px-4 py-3">
        <p class="text-sm font-bold text-gray-950 dark:text-white truncate">
            {{ $user->name }}
        </p>
        @if ($roleLabel)
            <p class="text-xs text-gray-500 dark:text-gray-400 font-medium truncate mt-0.5">
                {{ $roleLabel }}
            </p>
        @endif
    </div>
@endif
