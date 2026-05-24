<div class="max-w-md mx-auto mt-16">
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Быстрый вход</h1>

    @if($users->isNotEmpty())
        <div class="mb-8">
            <h2 class="text-sm font-medium text-neutral-500 uppercase tracking-wide mb-3">Выберите пользователя</h2>
            <div class="space-y-2">
                @foreach($users as $user)
                    <button wire:click="selectUser({{ $user->id }})"
                        class="w-full text-left px-4 py-3 rounded-lg border border-neutral-200 hover:border-blue-400 hover:bg-blue-50 transition text-sm font-medium text-neutral-700 cursor-pointer">
                        {{ $user->name }}
                        <span class="text-neutral-400 font-normal"> — {{ $user->email }}</span>
                    </button>
                @endforeach
            </div>
        </div>
    @endif

    <div>
        <h2 class="text-sm font-medium text-neutral-500 uppercase tracking-wide mb-3">Или зарегистрируйтесь</h2>
        <form wire:submit="register" class="space-y-4">
            <input wire:model="name" type="text" placeholder="Имя"
                class="w-full rounded-lg border border-neutral-300 p-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
            @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            <input wire:model="email" type="email" placeholder="Email"
                class="w-full rounded-lg border border-neutral-300 p-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
            @error('email') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            <button type="submit"
                class="w-full bg-blue-600 text-white px-4 py-2.5 rounded-lg hover:bg-blue-700 transition text-sm font-medium cursor-pointer">
                Зарегистрироваться и войти
            </button>
        </form>
    </div>
</div>
