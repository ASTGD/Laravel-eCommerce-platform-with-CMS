<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    @for ($i = 0; $i < 8; $i++)
        <div class="relative min-w-0 overflow-hidden rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="absolute inset-y-0 left-0 w-1.5 bg-gray-200 dark:bg-gray-700"></div>

            <div class="relative flex items-start justify-between gap-4">
                <div class="min-w-0 flex-1">
                    <div class="shimmer h-3 w-24 rounded"></div>

                    <div class="shimmer mt-4 h-5 w-28 rounded md:h-6"></div>

                    <div class="mt-3 flex items-center gap-2">
                        <div class="shimmer h-6 w-14 rounded-full"></div>

                        <div class="shimmer h-4 w-32 rounded"></div>
                    </div>
                </div>

                <div class="shimmer h-10 w-10 shrink-0 rounded-xl"></div>
            </div>
        </div>
    @endfor
</div>
