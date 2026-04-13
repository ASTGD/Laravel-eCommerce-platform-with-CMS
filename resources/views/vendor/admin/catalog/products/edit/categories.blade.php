@php
    $configurableAttributes = $product->type === 'configurable'
        ? $product->attribute_family
            ->configurable_attributes
            ->load(['translations', 'options', 'options.translations'])
        : collect();

    $selectedSuperAttributeCodes = $product->super_attributes->pluck('code')->all();
@endphp

{!! view_render_event('bagisto.admin.catalog.product.edit.form.categories.before', ['product' => $product]) !!}

<!-- Panel -->
<div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
    <!-- Panel Header -->
    <p class="mb-4 flex justify-between text-base font-semibold text-gray-800 dark:text-white">
        @lang('admin::app.catalog.products.edit.categories.title')
    </p>

    {!! view_render_event('bagisto.admin.catalog.product.edit.form.categories.controls.before', ['product' => $product]) !!}

    <!-- Panel Content -->
    <div class="mb-5 text-sm text-gray-600 dark:text-gray-300">
        <v-product-categories>
            <x-admin::shimmer.tree />
        </v-product-categories>
    </div>

    {!! view_render_event('bagisto.admin.catalog.product.edit.form.categories.controls.after', ['product' => $product]) !!}
</div>

@if ($product->type === 'configurable')
    <div class="box-shadow mt-2.5 rounded bg-white p-4 dark:bg-gray-900">
        <p class="mb-1 text-base font-semibold text-gray-800 dark:text-white">
            Configurable Attributes
        </p>

        <p class="mb-4 text-xs font-medium text-gray-500 dark:text-gray-300">
            Select the family attributes this product should use for variant combinations. Save once after changing this list, then reopen the product editor to add combinations for any newly selected attributes.
        </p>

        <input
            type="hidden"
            name="super_attribute_codes[]"
            value=""
        >

        @if ($configurableAttributes->isEmpty())
            <p class="text-sm text-gray-600 dark:text-gray-300">
                No configurable attributes are available in this attribute family.
            </p>
        @else
            <div class="grid gap-2.5">
                @foreach ($configurableAttributes as $attribute)
                    <label class="flex cursor-pointer items-start gap-2.5 rounded border border-gray-200 p-3 transition-all hover:border-gray-400 dark:border-gray-800 dark:hover:border-gray-700">
                        <input
                            type="checkbox"
                            name="super_attribute_codes[]"
                            value="{{ $attribute->code }}"
                            class="mt-0.5 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                            @checked(in_array($attribute->code, $selectedSuperAttributeCodes, true))
                        >

                        <span class="grid gap-0.5">
                            <span class="text-sm font-semibold text-gray-800 dark:text-white">
                                {{ $attribute->admin_name }}
                            </span>

                            <span class="text-xs text-gray-500 dark:text-gray-300">
                                {{ $attribute->code }}
                            </span>
                        </span>
                    </label>
                @endforeach
            </div>

            <x-admin::form.control-group.error control-name="super_attribute_codes" />
        @endif
    </div>
@endif

{!! view_render_event('bagisto.admin.catalog.product.edit.form.categories.after', ['product' => $product]) !!}

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-product-categories-template"
    >
        <div>
            <template v-if="isLoading">
                <x-admin::shimmer.tree />
            </template>

            <template v-else>
                <x-admin::tree.view
                    input-type="checkbox"
                    selection-type="individual"
                    name-field="categories"
                    id-field="id"
                    value-field="id"
                    ::items="categories"
                    :value="json_encode($product->categories->pluck('id'))"
                    :fallback-locale="config('app.fallback_locale')"
                />
            </template>
        </div>
    </script>

    <script type="module">
        app.component('v-product-categories', {
            template: '#v-product-categories-template',

            data() {
                return {
                    isLoading: true,

                    categories: [],
                }
            },

            mounted() {
                this.get();
            },

            methods: {
                get() {
                    axios.get("{{ route('admin.catalog.categories.tree') }}", {
                            params: {
                                channel: "{{ $currentChannel->code }}",
                            }
                        })
                        .then(response => {
                            this.isLoading = false;

                            this.categories = response.data.data;
                        }).catch(error => {
                            console.log(error);
                        });
                }
            }
        });

        const configurableAttributeCodes = @json($configurableAttributes->pluck('code')->values());

        const syncConfigurableAttributeFields = () => {
            const selectedCodes = Array.from(
                document.querySelectorAll('input[name="super_attribute_codes[]"]:checked')
            ).map((element) => element.value);

            configurableAttributeCodes.forEach((code) => {
                const field = document.querySelector(
                    `input[name="${code}"], select[name="${code}"], textarea[name="${code}"]`
                );

                if (! field) {
                    return;
                }

                const controlGroup = field.closest('.control-group') ?? field.closest('.mb-4');

                if (! controlGroup) {
                    return;
                }

                controlGroup.style.display = selectedCodes.includes(code) ? 'none' : '';
            });
        };

        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('input[name="super_attribute_codes[]"]').forEach((checkbox) => {
                checkbox.addEventListener('change', syncConfigurableAttributeFields);
            });

            syncConfigurableAttributeFields();
        });
    </script>
@endpushOnce
