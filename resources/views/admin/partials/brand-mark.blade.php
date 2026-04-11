@php
    $class = $class ?? 'flex h-11 w-11 items-center justify-center rounded-2xl bg-blue-600 text-white shadow-sm';
@endphp

<span class="{{ $class }}" aria-hidden="true">
    <svg
        viewBox="0 0 48 48"
        fill="none"
        xmlns="http://www.w3.org/2000/svg"
        class="h-6 w-6"
    >
        <path
            d="M24 6L38 14V34L24 42L10 34V14L24 6Z"
            fill="currentColor"
            fill-opacity="0.18"
            stroke="currentColor"
            stroke-width="3"
            stroke-linejoin="round"
        />

        <path
            d="M17 28L24 14L31 28"
            stroke="currentColor"
            stroke-width="3"
            stroke-linecap="round"
            stroke-linejoin="round"
        />

        <path
            d="M20 24H28"
            stroke="currentColor"
            stroke-width="3"
            stroke-linecap="round"
        />
    </svg>
</span>
