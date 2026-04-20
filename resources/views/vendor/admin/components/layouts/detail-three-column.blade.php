@once
    @push('styles')
        <style>
            .detail-three-column-grid {
                display: grid;
                gap: 0.625rem;
                grid-template-columns: minmax(0, 1fr);
                margin-top: 0.875rem;
            }

            @media (min-width: 1280px) {
                .detail-three-column-grid {
                    grid-template-columns: minmax(0, 1.18fr) 360px 360px;
                    align-items: start;
                }
            }
        </style>
    @endpush
@endonce

<div class="detail-three-column-grid">
    <div class="flex min-w-0 flex-col gap-2 max-xl:w-full">
        {{ $left ?? '' }}
    </div>

    <div class="flex min-w-0 flex-col gap-2 max-xl:w-full">
        {{ $middle ?? '' }}
    </div>

    <div class="flex min-w-0 flex-col gap-2 max-xl:w-full">
        {{ $right ?? '' }}
    </div>
</div>
