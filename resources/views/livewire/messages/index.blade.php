<div>
    @if(session('message'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-lg mb-6 text-sm flex items-center gap-2">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            {{ session('message') }}
        </div>
    @endif

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <h2 class="text-2xl font-bold text-gray-900">Сообщения</h2>
        <button wire:click="sync" wire:loading.attr="disabled"
            class="inline-flex items-center gap-2 bg-blue-600 text-white px-4 py-2.5 rounded-lg hover:bg-blue-700 transition disabled:opacity-50 cursor-pointer text-sm font-medium">
            <svg wire:loading.remove class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
            <svg wire:loading class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
            <span wire:loading.remove>Синхронизировать</span>
            <span wire:loading>Загрузка...</span>
        </button>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-neutral-200 p-4">
            <p class="text-xs font-medium text-neutral-500 uppercase tracking-wide">Всего</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ $total }}</p>
        </div>
        <div class="bg-white rounded-xl border border-neutral-200 p-4">
            <p class="text-xs font-medium text-neutral-500 uppercase tracking-wide">Не обработано</p>
            <p class="text-2xl font-bold text-amber-600 mt-1">{{ $unprocessed }}</p>
        </div>
        <div class="bg-white rounded-xl border border-neutral-200 p-4">
            <p class="text-xs font-medium text-neutral-500 uppercase tracking-wide">Обработано</p>
            <p class="text-2xl font-bold text-emerald-600 mt-1">{{ $total - $unprocessed }}</p>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden">
        <div class="p-4 border-b border-neutral-100">
            <div class="flex flex-col sm:flex-row gap-3">
                <div class="relative flex-1">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input wire:model.live.debounce.300ms="search" type="text" placeholder="Поиск по тексту, каналу, типу..."
                        class="w-full pl-10 pr-4 py-2.5 rounded-lg border border-neutral-300 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>
                <select wire:model.live="typeFilter"
                    class="px-4 py-2.5 rounded-lg border border-neutral-300 text-sm bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                    <option value="">Все типы</option>
                    @foreach($types as $type)
                        <option value="{{ $type }}">{{ $type }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        @if($messages->isEmpty())
            <div class="flex flex-col items-center justify-center py-16 text-center">
                <svg class="w-16 h-16 text-neutral-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                </svg>
                <p class="text-neutral-500 font-medium">Нет сообщений</p>
                <p class="text-neutral-400 text-sm mt-1">Нажмите «Синхронизировать» для загрузки</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-neutral-50 text-neutral-600 text-xs uppercase tracking-wide">
                            <th class="text-left px-4 py-3 font-medium">ID</th>
                            <th class="text-left px-4 py-3 font-medium">Тип</th>
                            <th class="text-left px-4 py-3 font-medium">Канал</th>
                            <th class="text-left px-4 py-3 font-medium">Текст</th>
                            <th class="text-left px-4 py-3 font-medium">Получено</th>
                            <th class="text-center px-4 py-3 font-medium">Отпр. на AI</th>
                            <th class="text-center px-4 py-3 font-medium">Ответ AI</th>
                            <th class="text-center px-4 py-3 font-medium">AI</th>
                            <th class="text-center px-4 py-3 font-medium">В VK</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100">
                        @foreach($messages as $msg)
                            <tr class="hover:bg-neutral-50 transition">
                                <td class="px-4 py-3 font-mono text-xs text-neutral-400">#{{ $msg->external_id }}</td>
                                <td class="px-4 py-3">
                                    @php
                                        $typeStyles = [
                                            'message_new' => 'bg-blue-50 text-blue-700',
                                            'message_typing_state' => 'bg-neutral-100 text-neutral-600',
                                            'message_reply' => 'bg-purple-50 text-purple-700',
                                        ];
                                        $style = $typeStyles[$msg->payload_type] ?? 'bg-gray-100 text-gray-700';
                                    @endphp
                                    <span class="inline-block px-2.5 py-0.5 rounded-full text-xs font-medium {{ $style }}">
                                        {{ $msg->payload_type ?? '—' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-neutral-600">{{ $msg->channel }}</td>
                                <td class="px-4 py-3 max-w-xs truncate text-neutral-800 font-medium">
                                    @if($msg->text)
                                        {{ $msg->text }}
                                    @else
                                        <span class="text-neutral-300 italic">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-neutral-500 text-xs whitespace-nowrap">
                                    {{ $msg->received_at->format('d.m.Y') }}
                                    <br><span class="text-neutral-300">{{ $msg->received_at->format('H:i:s') }}</span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <button wire:click="toggleSent({{ $msg->id }})"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium transition cursor-pointer
                                        {{ $msg->status?->sent_for_processing
                                            ? 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100'
                                            : 'bg-neutral-100 text-neutral-400 hover:bg-neutral-200' }}">
                                        @if($msg->status?->sent_for_processing)
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                            </svg>
                                            {{ $msg->status->sent_at->format('H:i') }}
                                        @else
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                            </svg>
                                            Отметить
                                        @endif
                                    </button>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <button wire:click="toggleResponded({{ $msg->id }})"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium transition cursor-pointer
                                        {{ $msg->status?->response_received
                                            ? 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100'
                                            : 'bg-neutral-100 text-neutral-400 hover:bg-neutral-200' }}">
                                        @if($msg->status?->response_received)
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                            </svg>
                                            {{ $msg->status->responded_at->format('H:i') }}
                                        @else
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                            </svg>
                                            Отметить
                                        @endif
                                    </button>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @php $hasAnswer = $msg->answers()->exists(); @endphp
                                    @if($hasAnswer)
                                        <div class="flex items-center justify-center gap-1">
                                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-xs font-medium bg-emerald-50 text-emerald-700">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                                </svg>
                                                Готово
                                            </span>
                                            <button wire:click="retryProcessing({{ $msg->id }})"
                                                class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-xs font-medium transition cursor-pointer bg-amber-50 text-amber-700 hover:bg-amber-100">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 4v6h6m0 0L2 5m5 5V3m12 12v6h-6m0 0l5-5m-5 5h6"/>
                                                </svg>
                                                Заново
                                            </button>
                                        </div>
                                    @else
                                        <button wire:click="sendForProcessing({{ $msg->id }})"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium transition cursor-pointer bg-amber-50 text-amber-700 hover:bg-amber-100">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                            </svg>
                                            Отправить
                                        </button>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @php $lastAnswer = $msg->latestAnswer; @endphp
                                    @if($lastAnswer?->response)
                                        <button wire:click="resendReply({{ $msg->id }})"
                                            class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-xs font-medium transition cursor-pointer
                                            {{ $lastAnswer->reply_sent_at
                                                ? 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100'
                                                : 'bg-amber-50 text-amber-700 hover:bg-amber-100' }}">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                            </svg>
                                            {{ $lastAnswer->reply_sent_at ? 'Заново' : 'Отправить' }}
                                        </button>
                                    @else
                                        <span class="text-neutral-300">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div class="mt-6">
        {{ $messages->links() }}
    </div>
</div>
