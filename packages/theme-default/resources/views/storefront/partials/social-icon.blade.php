@php
    $icon = $icon ?? 'link';
    $class = $class ?? 'gadget-footer__social-icon';
@endphp

@switch($icon)
    @case('facebook')
        <svg aria-hidden="true" viewBox="0 0 24 24" class="{{ $class }}">
            <path fill="currentColor" d="M14.2 8.2V6.9c0-.6.2-.9 1-.9h1.5V3.4c-.7-.1-1.4-.2-2.2-.2-2.3 0-3.8 1.4-3.8 3.9v1.1H8.2V11h2.5v9.8h3.1V11h2.6l.4-2.8h-2.6Z"/>
        </svg>
        @break

    @case('instagram')
        <svg aria-hidden="true" viewBox="0 0 24 24" class="{{ $class }}">
            <path fill="currentColor" d="M7.8 2.8h8.4a5 5 0 0 1 5 5v8.4a5 5 0 0 1-5 5H7.8a5 5 0 0 1-5-5V7.8a5 5 0 0 1 5-5Zm0 1.8a3.2 3.2 0 0 0-3.2 3.2v8.4a3.2 3.2 0 0 0 3.2 3.2h8.4a3.2 3.2 0 0 0 3.2-3.2V7.8a3.2 3.2 0 0 0-3.2-3.2H7.8Zm4.2 3a4.4 4.4 0 1 1 0 8.8 4.4 4.4 0 0 1 0-8.8Zm0 1.8a2.6 2.6 0 1 0 0 5.2 2.6 2.6 0 0 0 0-5.2Zm5-2.2a1.1 1.1 0 1 1 0 2.2 1.1 1.1 0 0 1 0-2.2Z"/>
        </svg>
        @break

    @case('x')
        <svg aria-hidden="true" viewBox="0 0 24 24" class="{{ $class }}">
            <path fill="currentColor" d="M13.9 10.5 21.2 2h-1.7l-6.3 7.4L8.1 2H2.3l7.7 11.2L2.3 22h1.7l6.8-7.9 5.4 7.9h5.8l-8.1-11.5Zm-2.4 2.8-.8-1.1L4.6 3.3h2.7l5 7.2.8 1.1 6.4 9.2h-2.7l-5.3-7.5Z"/>
        </svg>
        @break

    @case('youtube')
        <svg aria-hidden="true" viewBox="0 0 24 24" class="{{ $class }}">
            <path fill="currentColor" d="M21.6 7.2s-.2-1.6-.9-2.3c-.9-.9-1.9-.9-2.3-1C15.2 3.7 12 3.7 12 3.7s-3.2 0-6.4.2c-.5.1-1.5.1-2.3 1-.7.7-.9 2.3-.9 2.3S2.2 9 2.2 10.8v1.7c0 1.8.2 3.6.2 3.6s.2 1.6.9 2.3c.9.9 2 .9 2.5 1 1.8.2 6.2.2 6.2.2s3.2 0 6.4-.2c.5-.1 1.5-.1 2.3-1 .7-.7.9-2.3.9-2.3s.2-1.8.2-3.6v-1.7c0-1.8-.2-3.6-.2-3.6ZM10.1 14.8V8.6l5.8 3.1-5.8 3.1Z"/>
        </svg>
        @break

    @case('tiktok')
        <svg aria-hidden="true" viewBox="0 0 24 24" class="{{ $class }}">
            <path fill="currentColor" d="M16.2 2.5c.4 2.6 1.9 4.1 4.4 4.3v3c-1.5.1-2.8-.3-4.3-1.2v5.6c0 7.1-7.8 9.3-11 4.2-2.1-3.3-.8-9.1 5.9-9.3v3.2c-.5.1-1 .2-1.5.4-1.4.5-2.2 1.5-2 3.1.3 3 5.8 3.9 5.3-2V2.5h3.2Z"/>
        </svg>
        @break

    @default
        <svg aria-hidden="true" viewBox="0 0 24 24" class="{{ $class }}">
            <path fill="currentColor" d="M10.6 13.4a1 1 0 0 1 0-1.4l3.9-3.9a2.7 2.7 0 0 0-3.8-3.8L7.9 7.1A2.7 2.7 0 0 0 9 11.5a1 1 0 1 1-.6 1.9 4.7 4.7 0 0 1-1.9-7.7l2.8-2.8a4.7 4.7 0 0 1 6.6 6.6L12 13.4a1 1 0 0 1-1.4 0Zm2.8-2.8a1 1 0 0 1 0 1.4l-3.9 3.9a2.7 2.7 0 0 0 3.8 3.8l2.8-2.8A2.7 2.7 0 0 0 15 12.5a1 1 0 1 1 .6-1.9 4.7 4.7 0 0 1 1.9 7.7l-2.8 2.8a4.7 4.7 0 0 1-6.6-6.6l3.9-3.9a1 1 0 0 1 1.4 0Z"/>
        </svg>
@endswitch
