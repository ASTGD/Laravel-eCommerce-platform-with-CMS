@foreach ($children as $child)
    <div class="grid content-start gap-2.5">
        <p class="text-base font-semibold text-gray-600 dark:text-gray-300">
            {{ $child->getName() }}
        </p>

        <p class="leading-[140%] text-gray-600 dark:text-gray-300">
            {!! $child->getInfo() !!}
        </p>
    </div>

    <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
        @foreach ($child->getFields() as $field)
            @if (
                $field->getType() == 'blade'
                && view()->exists($path = $field->getPath())
            )
                {!! view($path, compact('field', 'child'))->render() !!}
            @else
                @include('admin::configuration.field-type')
            @endif
        @endforeach
    </div>
@endforeach
