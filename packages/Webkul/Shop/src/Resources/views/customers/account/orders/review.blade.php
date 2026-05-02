<x-shop::layouts.account>
    <x-slot:title>
        @lang('shop::app.customers.account.orders.view.review.page-title')
    </x-slot>

    <div class="max-md:hidden">
        <x-shop::layouts.account.navigation />
    </div>

    <div class="mx-4 flex-auto max-md:mx-6 max-sm:mx-4">
        <div class="mb-8 flex items-center justify-between gap-4 max-md:mb-5">
            <div class="flex items-center gap-2.5">
                <a
                    class="grid"
                    href="{{ route('shop.customers.account.orders.view', $order->id) }}"
                >
                    <span class="icon-arrow-left rtl:icon-arrow-right text-2xl"></span>
                </a>

                <div>
                    <h2 class="text-2xl font-medium max-md:text-xl max-sm:text-base">
                        @lang('shop::app.customers.account.orders.view.review.page-title')
                    </h2>

                    <p class="mt-1 text-sm text-zinc-500">
                        @lang('shop::app.customers.account.orders.view.review.order-context', ['order_id' => $order->increment_id])
                    </p>
                </div>
            </div>
        </div>

        @if ($errors->has('review'))
            <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                {{ $errors->first('review') }}
            </div>
        @endif

        <div class="grid gap-8 lg:grid-cols-[280px_1fr] lg:items-start">
            <div class="rounded-xl border border-zinc-200 bg-white p-4">
                <div class="flex items-start gap-4">
                    <x-shop::media.images.lazy
                        class="h-24 w-24 shrink-0 rounded-xl object-cover max-md:h-20 max-md:w-20"
                        src="{{ $item->product->base_image_url ?? bagisto_asset('images/small-product-placeholder.webp') }}"
                        alt="{{ $item->name }}"
                    />

                    <div class="min-w-0">
                        <p class="text-sm font-medium text-zinc-500">
                            @lang('shop::app.customers.account.orders.view.review.reviewing')
                        </p>

                        <h3 class="mt-1 max-h-[3.5rem] overflow-hidden text-lg font-medium leading-snug">
                            {{ $item->name }}
                        </h3>

                        <p class="mt-2 text-sm text-zinc-500">
                            @lang('shop::app.customers.account.orders.view.information.sku'):
                            <span class="font-medium text-black">
                                {{ $item->getTypeInstance()->getOrderedItem($item)->sku }}
                            </span>
                        </p>
                    </div>
                </div>
            </div>

            <form
                method="POST"
                action="{{ route('shop.customers.account.reviews.order-item.store', [$order->id, $item->id]) }}"
                enctype="multipart/form-data"
                class="rounded-xl border border-zinc-200 bg-white p-5"
            >
                @csrf

                <div class="grid gap-5">
                    <div>
                        <label class="mb-2 block text-sm font-medium">
                            @lang('shop::app.customers.account.orders.view.review.rating')
                        </label>

                        <v-order-review-rating
                            initial-rating="{{ old('rating', 5) }}"
                        ></v-order-review-rating>

                        @error('rating')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium">
                            @lang('shop::app.customers.account.orders.view.review.title')
                        </label>

                        <input
                            type="text"
                            name="title"
                            value="{{ old('title') }}"
                            class="w-full rounded-xl border border-zinc-200 px-4 py-3"
                            required
                        >

                        @error('title')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium">
                            @lang('shop::app.customers.account.orders.view.review.comment')
                        </label>

                        <textarea
                            name="comment"
                            rows="5"
                            class="w-full rounded-xl border border-zinc-200 px-4 py-3"
                            required
                        >{{ old('comment') }}</textarea>

                        @error('comment')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium">
                            @lang('shop::app.customers.account.orders.view.review.attachments')
                        </label>

                        <input
                            type="file"
                            name="attachments[]"
                            multiple
                            accept="image/*,video/*"
                            class="w-full rounded-xl border border-zinc-200 px-4 py-3 text-sm"
                        >

                        @error('attachments')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror

                        @error('attachments.*')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex flex-wrap justify-end gap-3">
                        <a
                            href="{{ route('shop.customers.account.orders.view', $order->id) }}"
                            class="secondary-button border-zinc-200 px-5 py-3 font-normal"
                        >
                            @lang('shop::app.customers.account.orders.view.review.cancel')
                        </a>

                        <button
                            type="submit"
                            class="primary-button px-5 py-3"
                        >
                            @lang('shop::app.customers.account.orders.view.review.submit')
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @pushOnce('scripts')
        <script
            type="text/x-template"
            id="v-order-review-rating-template"
        >
            <div class="flex flex-wrap items-center gap-1.5">
                <button
                    v-for="rating in [1, 2, 3, 4, 5]"
                    :key="rating"
                    type="button"
                    class="group p-0.5"
                    :aria-label="rating + ' stars'"
                    @click="selectedRating = rating"
                >
                    <span
                        class="icon-star-fill text-3xl transition"
                        :class="selectedRating >= rating ? 'text-amber-500' : 'text-zinc-300 group-hover:text-amber-400'"
                    ></span>
                </button>

                <input
                    type="hidden"
                    name="rating"
                    :value="selectedRating"
                >

                <span class="ms-3 text-sm text-zinc-500">
                    @lang('shop::app.customers.account.orders.view.review.selected-rating')

                    @{{ selectedRating }} / 5
                </span>
            </div>
        </script>

        <script type="module">
            app.component('v-order-review-rating', {
                template: '#v-order-review-rating-template',

                props: ['initialRating'],

                data() {
                    return {
                        selectedRating: Number(this.initialRating) || 5,
                    };
                },
            });
        </script>
    @endpushOnce
</x-shop::layouts.account>
