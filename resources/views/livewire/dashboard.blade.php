<div class="min-h-screen bg-gray-100">
    <nav class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <h1 class="text-xl font-semibold text-gray-800">Dashboard</h1>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-gray-600 hover:text-gray-800">
                        Выйти
                    </button>
                </form>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">
                <p class="text-lg mb-4">Привет, {{ auth()->user()->name }}!</p>
                <p class="text-gray-600">Email: {{ auth()->user()->email }}</p>
                <p class="text-gray-600">VK ID: {{ auth()->user()->provider_id }}</p>
            </div>
        </div>
    </main>
</div>
