<div class="grid content-start gap-2.5">
    <p class="text-base font-semibold text-gray-600 dark:text-gray-300">
        {{ $title }}
    </p>

    <p class="leading-[140%] text-gray-600 dark:text-gray-300">
        {{ $info }}
    </p>
</div>

<div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
    @if ($methodChild)
        <div>
            <p class="text-sm font-semibold text-gray-700 dark:text-gray-200">
                Payment Settings
            </p>

            <div class="mt-4">
                @foreach ($methodChild->getFields() as $field)
                    @if (
                        $field->getType() == 'blade'
                        && view()->exists($path = $field->getPath())
                    )
                        {!! view($path, ['field' => $field, 'child' => $methodChild])->render() !!}
                    @else
                        @include('admin::configuration.field-type', ['child' => $methodChild])
                    @endif
                @endforeach
            </div>
        </div>
    @endif

    @if ($gatewayChild)
        <div class="{{ $methodChild ? 'mt-6 border-t border-gray-200 pt-6 dark:border-gray-800' : '' }}">
            <p class="text-sm font-semibold text-gray-700 dark:text-gray-200">
                Gateway Settings
            </p>

            <div class="mt-4">
                @foreach ($gatewayChild->getFields() as $field)
                    @if (
                        $field->getType() == 'blade'
                        && view()->exists($path = $field->getPath())
                    )
                        {!! view($path, ['field' => $field, 'child' => $gatewayChild])->render() !!}
                    @else
                        @include('admin::configuration.field-type', ['child' => $gatewayChild])
                    @endif
                @endforeach
            </div>
        </div>
    @endif
</div>
