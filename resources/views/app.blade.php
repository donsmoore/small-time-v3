<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'TimeClock') }}</title>
        <link rel="icon" type="image/x-icon" href="/timeclock/v3/img/favicon.ico">
        <link rel="icon" type="image/svg+xml" href="/timeclock/v3/img/favicon.svg">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>
        <div id="app"></div>
    </body>
</html>

