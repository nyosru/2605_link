<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', config('app.name'))</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-gray-100">
    @auth
        @php
            $route = request()->route()?->getName();
        @endphp
        <nav class="bg-white shadow-sm border-b border-neutral-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16 items-center">
                    <div class="flex items-center gap-1">
                        <a href="{{ route('dashboard') }}"
                            class="px-3 py-2 rounded-lg text-sm font-medium transition
                            {{ $route === 'dashboard' ? 'bg-neutral-100 text-gray-900' : 'text-gray-500 hover:text-gray-800 hover:bg-neutral-50' }}">
                            Дашборд
                        </a>
                        <a href="{{ route('platforms') }}"
                            class="px-3 py-2 rounded-lg text-sm font-medium transition
                            {{ $route === 'platforms' ? 'bg-neutral-100 text-gray-900' : 'text-gray-500 hover:text-gray-800 hover:bg-neutral-50' }}">
                            Площадки
                        </a>
                        <a href="{{ route('messages') }}"
                            class="px-3 py-2 rounded-lg text-sm font-medium transition
                            {{ $route === 'messages' ? 'bg-neutral-100 text-gray-900' : 'text-gray-500 hover:text-gray-800 hover:bg-neutral-50' }}">
                            Сообщения
                        </a>
                        <a href="{{ route('answers') }}"
                            class="px-3 py-2 rounded-lg text-sm font-medium transition
                            {{ $route === 'answers' ? 'bg-neutral-100 text-gray-900' : 'text-gray-500 hover:text-gray-800 hover:bg-neutral-50' }}">
                            Обработка
                        </a>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <div class="flex items-center gap-3">
                            <span class="text-sm text-neutral-400">{{ auth()->user()->name }}</span>
                            <button type="submit" class="text-sm text-neutral-500 hover:text-neutral-800 transition cursor-pointer">
                                Выйти
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </nav>
    @endauth

    <main class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        {{ $slot }}
    </main>

    @livewireScripts
</body>
</html>
