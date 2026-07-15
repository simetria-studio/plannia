<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $title ?? config('app.name', 'PLANNIA') }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-plannia-bg text-gray-800">
        <div class="min-h-screen">
            @include('layouts.partials.sidebar')

            <div class="pl-60">
                @include('layouts.partials.topbar', [
                    'pendingCount' => \App\Models\GeneratedDocument::where('school_id', auth()->user()->school_id)->where('status', 'pendente')->count(),
                ])

                <main class="p-8">
                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>
