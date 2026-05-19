<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title inertia>{{ config('app.name', 'Dafydio Cloud') }}</title>
        <link rel="icon" type="image/png" href="/images/dafydio-booth-icon.png">
        <link rel="apple-touch-icon" href="/images/dafydio-booth-icon.png">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
