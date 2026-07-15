<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>Login — PLANNIA</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen flex">
            <div class="hidden lg:flex lg:w-1/2 bg-plannia-navy items-center justify-center p-12">
                <div class="max-w-md text-center">
                    <x-plannia-logo class="text-white h-10 mx-auto mb-8" />
                    <h1 class="text-3xl font-bold text-white mb-4">Sistema de PEI e PAEE</h1>
                    <p class="text-white/60 text-lg leading-relaxed">
                        Gerencie planos educacionais individualizados com praticidade e segurança.
                    </p>
                </div>
            </div>

            <div class="flex-1 flex items-center justify-center bg-plannia-bg p-8">
                <div class="w-full max-w-md">
                    <div class="lg:hidden mb-8 text-center">
                        <x-plannia-logo class="text-plannia-navy h-8 mx-auto" />
                    </div>

                    <div class="plannia-card p-8">
                        <h2 class="text-xl font-bold text-gray-900 mb-1">Entrar</h2>
                        <p class="text-sm text-gray-500 mb-6">Acesse sua conta PLANNIA</p>

                        <x-auth-session-status class="mb-4" :status="session('status')" />

                        <form method="POST" action="{{ route('login') }}" class="space-y-5">
                            @csrf
                            <div>
                                <label class="plannia-label" for="email">E-mail</label>
                                <input id="email" name="email" type="email" class="plannia-input" value="{{ old('email') }}" required autofocus>
                                <x-input-error :messages="$errors->get('email')" class="mt-1" />
                            </div>
                            <div>
                                <label class="plannia-label" for="password">Senha</label>
                                <input id="password" name="password" type="password" class="plannia-input" required>
                                <x-input-error :messages="$errors->get('password')" class="mt-1" />
                            </div>
                            <div class="flex items-center justify-between">
                                <label class="inline-flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" name="remember" class="rounded text-plannia-blue focus:ring-plannia-blue">
                                    <span class="text-sm text-gray-600">Lembrar-me</span>
                                </label>
                                @if (Route::has('password.request'))
                                    <a href="{{ route('password.request') }}" class="text-sm text-plannia-blue hover:text-plannia-blue-hover">Esqueceu a senha?</a>
                                @endif
                            </div>
                            <button type="submit" class="plannia-btn-primary w-full justify-center py-3">Entrar</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
