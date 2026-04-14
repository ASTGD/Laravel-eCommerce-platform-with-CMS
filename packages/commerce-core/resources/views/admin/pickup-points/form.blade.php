@php
    $isEdit = (bool) $pickupPoint->exists;
    $countries = core()->countries();
@endphp

<x-admin::layouts>
    <x-slot:title>
        {{ $isEdit ? 'Edit Pickup Point' : 'Create Pickup Point' }}
    </x-slot>

    <x-admin::form
        :action="$isEdit ? route('admin.sales.pickup-points.update', $pickupPoint) : route('admin.sales.pickup-points.store')"
    >
        @if ($isEdit)
            @method('PUT')
        @endif

        <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
            <p class="text-xl font-bold text-gray-800 dark:text-white">
                {{ $isEdit ? 'Edit Pickup Point' : 'Create Pickup Point' }}
            </p>

            <div class="flex items-center gap-x-2.5">
                <a
                    href="{{ route('admin.sales.pickup-points.index') }}"
                    class="transparent-button hover:bg-gray-200 dark:text-white dark:hover:bg-gray-800"
                >
                    Back
                </a>

                <button
                    type="submit"
                    class="primary-button"
                >
                    Save Pickup Point
                </button>
            </div>
        </div>

        <div class="mt-3.5 flex gap-2.5 max-xl:flex-wrap">
            <div class="flex flex-1 flex-col gap-2 max-xl:flex-auto">
                <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
                    <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                        General
                    </p>

                    <div class="grid grid-cols-2 gap-4 max-md:grid-cols-1">
                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label class="required">
                                Code
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="text"
                                name="code"
                                rules="required"
                                :value="old('code', $pickupPoint->code)"
                                label="Code"
                                placeholder="Code"
                            />

                            <x-admin::form.control-group.error control-name="code" />
                        </x-admin::form.control-group>

                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label class="required">
                                Name
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="text"
                                name="name"
                                rules="required"
                                :value="old('name', $pickupPoint->name)"
                                label="Name"
                                placeholder="Name"
                            />

                            <x-admin::form.control-group.error control-name="name" />
                        </x-admin::form.control-group>

                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label>
                                Slug
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="text"
                                name="slug"
                                :value="old('slug', $pickupPoint->slug)"
                                label="Slug"
                                placeholder="Slug"
                            />

                            <x-admin::form.control-group.error control-name="slug" />
                        </x-admin::form.control-group>

                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label>
                                Courier
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="text"
                                name="courier_name"
                                :value="old('courier_name', $pickupPoint->courier_name)"
                                label="Courier"
                                placeholder="Courier"
                            />

                            <x-admin::form.control-group.error control-name="courier_name" />
                        </x-admin::form.control-group>
                    </div>
                </div>

                <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
                    <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                        Contact and Address
                    </p>

                    <div class="grid grid-cols-2 gap-4 max-md:grid-cols-1">
                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label>
                                Phone
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="text"
                                name="phone"
                                :value="old('phone', $pickupPoint->phone)"
                                label="Phone"
                                placeholder="Phone"
                            />

                            <x-admin::form.control-group.error control-name="phone" />
                        </x-admin::form.control-group>

                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label>
                                Email
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="email"
                                name="email"
                                :value="old('email', $pickupPoint->email)"
                                label="Email"
                                placeholder="Email"
                            />

                            <x-admin::form.control-group.error control-name="email" />
                        </x-admin::form.control-group>

                        <x-admin::form.control-group class="col-span-2 max-md:col-span-1">
                            <x-admin::form.control-group.label class="required">
                                Address Line 1
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="text"
                                name="address_line_1"
                                rules="required"
                                :value="old('address_line_1', $pickupPoint->address_line_1)"
                                label="Address Line 1"
                                placeholder="Address Line 1"
                            />

                            <x-admin::form.control-group.error control-name="address_line_1" />
                        </x-admin::form.control-group>

                        <x-admin::form.control-group class="col-span-2 max-md:col-span-1">
                            <x-admin::form.control-group.label>
                                Address Line 2
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="text"
                                name="address_line_2"
                                :value="old('address_line_2', $pickupPoint->address_line_2)"
                                label="Address Line 2"
                                placeholder="Address Line 2"
                            />

                            <x-admin::form.control-group.error control-name="address_line_2" />
                        </x-admin::form.control-group>

                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label class="required">
                                City
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="text"
                                name="city"
                                rules="required"
                                :value="old('city', $pickupPoint->city)"
                                label="City"
                                placeholder="City"
                            />

                            <x-admin::form.control-group.error control-name="city" />
                        </x-admin::form.control-group>

                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label>
                                State
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="text"
                                name="state"
                                :value="old('state', $pickupPoint->state)"
                                label="State"
                                placeholder="State"
                            />

                            <x-admin::form.control-group.error control-name="state" />
                        </x-admin::form.control-group>

                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label>
                                Postcode
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="text"
                                name="postcode"
                                :value="old('postcode', $pickupPoint->postcode)"
                                label="Postcode"
                                placeholder="Postcode"
                            />

                            <x-admin::form.control-group.error control-name="postcode" />
                        </x-admin::form.control-group>

                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label class="required">
                                Country
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="select"
                                name="country"
                                rules="required"
                                :label="'Country'"
                            >
                                @foreach ($countries as $country)
                                    <option
                                        value="{{ $country->code }}"
                                        @selected(old('country', $pickupPoint->country) === $country->code)
                                    >
                                        {{ $country->name }}
                                    </option>
                                @endforeach
                            </x-admin::form.control-group.control>

                            <x-admin::form.control-group.error control-name="country" />
                        </x-admin::form.control-group>

                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label>
                                Landmark
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="text"
                                name="landmark"
                                :value="old('landmark', $pickupPoint->landmark)"
                                label="Landmark"
                                placeholder="Landmark"
                            />

                            <x-admin::form.control-group.error control-name="landmark" />
                        </x-admin::form.control-group>
                    </div>
                </div>

                <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
                    <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                        Availability
                    </p>

                    <div class="grid grid-cols-2 gap-4 max-md:grid-cols-1">
                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label>
                                Opening Hours
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="textarea"
                                name="opening_hours"
                                :value="old('opening_hours', $pickupPoint->opening_hours)"
                                label="Opening Hours"
                                placeholder="Opening Hours"
                            />

                            <x-admin::form.control-group.error control-name="opening_hours" />
                        </x-admin::form.control-group>

                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label>
                                Notes
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="textarea"
                                name="notes"
                                :value="old('notes', $pickupPoint->notes)"
                                label="Notes"
                                placeholder="Notes"
                            />

                            <x-admin::form.control-group.error control-name="notes" />
                        </x-admin::form.control-group>
                    </div>
                </div>
            </div>

            <div class="flex w-[360px] max-w-full flex-col gap-2 max-xl:w-full">
                <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
                    <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                        Settings
                    </p>

                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>
                            Sort Order
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="number"
                            name="sort_order"
                            :value="old('sort_order', $pickupPoint->sort_order ?? 0)"
                            label="Sort Order"
                            placeholder="Sort Order"
                        />

                        <x-admin::form.control-group.error control-name="sort_order" />
                    </x-admin::form.control-group>

                    <input
                        type="hidden"
                        name="is_active"
                        value="0"
                    >

                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>
                            Active
                        </x-admin::form.control-group.label>

                        <label class="inline-flex items-center gap-2">
                            <input
                                type="checkbox"
                                name="is_active"
                                value="1"
                                @checked((int) old('is_active', $pickupPoint->is_active ? 1 : 0) === 1)
                            >

                            <span class="text-sm text-gray-600 dark:text-gray-300">
                                Available in checkout
                            </span>
                        </label>

                        <x-admin::form.control-group.error control-name="is_active" />
                    </x-admin::form.control-group>
                </div>
            </div>
        </div>
    </x-admin::form>
</x-admin::layouts>
