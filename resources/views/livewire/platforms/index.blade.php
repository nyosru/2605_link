<div>
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-gray-900">Площадки</h2>
    </div>

    <form wire:submit="save" class="bg-white rounded-xl border border-neutral-200 p-6 mb-8 space-y-4">
        <h3 class="text-lg font-semibold text-gray-800">{{ $editId ? 'Редактировать' : 'Новая площадка' }}</h3>

        <div>
            <label class="block text-sm font-medium text-neutral-600 mb-1">Название</label>
            <input wire:model="name" type="text" placeholder="Например: testPM"
                class="w-full max-w-xs rounded-lg border border-neutral-300 p-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
            @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-neutral-600 mb-1">Platform ID</label>
            <div class="flex gap-2">
                <input wire:model="platformId" type="text" placeholder="Сгенерируйте или введите"
                    class="flex-1 rounded-lg border border-neutral-300 p-2.5 text-sm font-mono focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                <button type="button" wire:click="generatePlatformId"
                    class="px-4 py-2.5 rounded-lg border border-neutral-300 text-sm text-neutral-600 hover:bg-neutral-50 transition cursor-pointer">
                    Сгенерировать
                </button>
            </div>
            @error('platformId') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-neutral-600 mb-1">Secret</label>
            <div class="flex gap-2">
                <input wire:model="secret" type="text" placeholder="Сгенерируйте или введите"
                    class="flex-1 rounded-lg border border-neutral-300 p-2.5 text-sm font-mono focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                <button type="button" wire:click="generateSecret"
                    class="px-4 py-2.5 rounded-lg border border-neutral-300 text-sm text-neutral-600 hover:bg-neutral-50 transition cursor-pointer">
                    Сгенерировать
                </button>
            </div>
            @error('secret') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
        </div>

        <div class="flex gap-3 pt-2">
            <button type="submit"
                class="bg-blue-600 text-white px-6 py-2.5 rounded-lg hover:bg-blue-700 transition text-sm font-medium cursor-pointer">
                {{ $editId ? 'Сохранить' : 'Создать' }}
            </button>
            @if($editId)
                <button type="button" wire:click="$set('editId', null)"
                    class="px-4 py-2.5 rounded-lg border border-neutral-300 text-sm text-neutral-600 hover:bg-neutral-50 transition cursor-pointer">
                    Отмена
                </button>
            @endif
        </div>
    </form>

    @if($platforms->isNotEmpty())
        <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-neutral-50 text-neutral-600 text-xs uppercase tracking-wide">
                        <th class="text-left px-4 py-3 font-medium">Название</th>
                        <th class="text-left px-4 py-3 font-medium">Platform ID</th>
                        <th class="text-left px-4 py-3 font-medium">Secret</th>
                        <th class="text-center px-4 py-3 font-medium">Действия</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100">
                    @foreach($platforms as $platform)
                        <tr class="hover:bg-neutral-50 transition">
                            <td class="px-4 py-3 font-medium text-neutral-800">{{ $platform->name }}</td>
                            <td class="px-4 py-3 font-mono text-xs text-neutral-500">{{ $platform->platform_id }}</td>
                            <td class="px-4 py-3 font-mono text-xs text-neutral-400">{{ $platform->secret }}</td>
                            <td class="px-4 py-3 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <button wire:click="edit({{ $platform->id }})"
                                        class="text-xs text-blue-600 hover:text-blue-800 transition cursor-pointer">
                                        Редактировать
                                    </button>
                                    <button wire:click="delete({{ $platform->id }})"
                                        wire:confirm="Удалить площадку?"
                                        class="text-xs text-red-500 hover:text-red-700 transition cursor-pointer">
                                        Удалить
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="text-center py-16 text-neutral-400">
            <p class="font-medium">Нет площадок</p>
            <p class="text-sm mt-1">Создайте первую площадку выше</p>
        </div>
    @endif
</div>
