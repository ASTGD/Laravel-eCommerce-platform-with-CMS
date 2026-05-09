<x-admin::layouts>
    <x-slot:title>
        My Affiliate
    </x-slot>

    <section class="flex flex-col gap-4 pt-1 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-3xl font-semibold tracking-tight text-slate-950 md:text-4xl dark:text-white">
                My Affiliate
            </h1>
        </div>

        @if (bouncer()->hasPermission('affiliates.profiles.create'))
            <a
                href="{{ route('admin.affiliates.profiles.create') }}"
                class="primary-button !rounded-xl !px-4 !py-2 !text-sm !shadow-sm !shadow-blue-200/60"
            >
                Add Affiliate
            </a>
        @endif
    </section>

    <div class="mt-6 rounded bg-white p-4 dark:bg-gray-900">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap gap-2">
                @foreach ($statusOptions as $statusCode => $statusLabel)
                    @php
                        $isAllStatus = $statusCode === 'all';
                        $isActiveStatus = $isAllStatus ? $status === null : $status === $statusCode;
                    @endphp

                    <a
                        href="{{ $isAllStatus ? route('admin.affiliates.profiles.index') : route('admin.affiliates.profiles.index', ['status' => $statusCode]) }}"
                        class="{{ $isActiveStatus ? 'primary-button' : 'transparent-button hover:bg-gray-200 dark:text-white dark:hover:bg-gray-800' }}"
                    >
                        {{ $statusLabel }}
                        <span class="ltr:ml-1 rtl:mr-1">({{ $statusCounts[$statusCode] ?? 0 }})</span>
                    </a>
                @endforeach
            </div>

            <form
                method="GET"
                action="{{ route('admin.affiliates.profiles.index') }}"
                class="flex min-w-[320px] items-center gap-2 max-sm:min-w-full"
            >
                @if ($status)
                    <input
                        type="hidden"
                        name="status"
                        value="{{ $status }}"
                    >
                @endif

                <input
                    type="search"
                    name="search"
                    value="{{ $search }}"
                    class="w-full rounded-md border px-3 py-2.5 text-sm text-gray-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"
                    placeholder="Search affiliate, email, phone, code"
                >

                <button
                    type="submit"
                    class="secondary-button"
                >
                    Search
                </button>
            </form>
        </div>

        <div class="mt-5 overflow-x-auto">
            <table class="w-full min-w-[900px] text-left">
                <thead>
                    <tr class="border-b border-gray-200 text-sm text-gray-600 dark:border-gray-800 dark:text-gray-300">
                        <th class="px-4 py-3">Affiliate</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Referral Code</th>
                        <th class="px-4 py-3">Traffic</th>
                        <th class="px-4 py-3">Sales</th>
                        <th class="px-4 py-3">Applied</th>
                        <th class="px-4 py-3 text-right">Action</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($profiles as $profile)
                        <tr class="border-b border-gray-100 text-sm dark:border-gray-800">
                            <td class="px-4 py-4">
                                <p class="font-semibold text-gray-800 dark:text-white">
                                    {{ trim(($profile->customer?->first_name ?? '').' '.($profile->customer?->last_name ?? '')) ?: 'Customer #'.$profile->customer_id }}
                                </p>

                                <p class="mt-1 text-gray-500 dark:text-gray-300">
                                    {{ $profile->customer?->email ?? 'No email' }}
                                </p>
                            </td>

                            <td class="px-4 py-4">
                                {{ $profile->status_label }}
                            </td>

                            <td class="px-4 py-4 font-medium text-gray-800 dark:text-white">
                                {{ $profile->referral_code }}
                            </td>

                            <td class="px-4 py-4">
                                {{ $profile->clicks_count }} clicks
                            </td>

                            <td class="px-4 py-4">
                                {{ $profile->attributions_count }} orders
                            </td>

                            <td class="px-4 py-4">
                                {{ $profile->created_at ? core()->formatDate($profile->created_at, 'd M Y') : 'N/A' }}
                            </td>

                            <td class="px-4 py-4 text-right">
                                <a
                                    href="{{ route('admin.affiliates.profiles.show', $profile) }}"
                                    class="text-blue-600 hover:underline"
                                >
                                    View Profile
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td
                                colspan="7"
                                class="px-4 py-10 text-center text-sm text-gray-500 dark:text-gray-300"
                            >
                                No affiliates found for this filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-5">
            {{ $profiles->links() }}
        </div>
    </div>
</x-admin::layouts>
