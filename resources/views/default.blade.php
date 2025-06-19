<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $page->title }}</title>

    @include('laravel-meta::meta')

    @stack('css')
</head>

<body>

<header>
    {{ $page->title }}
</header>

<main>
    {!! $page->content !!}
</main>


@stack('js')

</body>
</html>
