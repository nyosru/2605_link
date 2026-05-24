<div>
    @if(session('message'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-lg mb-6 text-sm flex items-center gap-2">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            {{ session('message') }}
        </div>
    @endif

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <h2 class="text-2xl font-bold text-gray-900">Обработка сообщений</h2>
        <span class="text-sm text-neutral-500">Ожидают: <strong class="text-amber-600">{{ $pendingCount }}</strong></span>
    </div>

    <div class="mb-6">
        <input wire:model.live.debounce.300ms="search" type="text" placeholder="Поиск по тексту сообщения или ответу..."
            class="w-full max-w-md rounded-lg border border-neutral-300 p-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
    </div>

    <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden">
        @if($answers->isEmpty())
            <div class="flex flex-col items-center justify-center py-16 text-center">
                <svg class="w-16 h-16 text-neutral-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <p class="text-neutral-500 font-medium">Нет обработанных сообщений</p>
                <p class="text-neutral-400 text-sm mt-1">Запустите обработку через <code class="text-xs bg-neutral-100 px-1.5 py-0.5 rounded">make process</code></p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-neutral-50 text-neutral-600 text-xs uppercase tracking-wide">
                            <th class="text-left px-4 py-3 font-medium">Сообщение</th>
                            <th class="text-left px-4 py-3 font-medium">Ответ</th>
                            <th class="text-center px-4 py-3 font-medium">Запуск</th>
                            <th class="text-center px-4 py-3 font-medium">Время</th>
                            <th class="text-center px-4 py-3 font-medium">Отправлено</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100">
                        @foreach($answers as $answer)
                            <tr class="hover:bg-neutral-50 transition">
                                <td class="px-4 py-3 max-w-xs">
                                    <div class="flex items-center gap-2 mb-0.5">
                                        <span class="text-xs font-mono text-neutral-400">#{{ $answer->message->external_id }}</span>
                                        <span class="text-xs bg-blue-50 text-blue-700 px-2 py-0.5 rounded-full">{{ $answer->message->payload_type }}</span>
                                    </div>
                                    <p class="text-sm text-neutral-800 truncate">{{ $answer->prompt ?: '—' }}</p>
                                </td>
                                <td class="px-4 py-3 max-w-md">
                                    @if($answer->response)
                                        <p class="text-sm text-neutral-700 line-clamp-3">{{ $answer->response }}</p>
                                    @else
                                        <span class="text-neutral-300 italic">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center whitespace-nowrap">
                                    @if($answer->started_at)
                                        <span class="text-xs text-neutral-500">
                                            {{ $answer->started_at->format('d.m H:i:s') }}
                                        </span>
                                    @else
                                        <span class="text-neutral-300">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if($answer->processing_time_ms)
                                        <span class="inline-flex items-center gap-1 text-xs font-medium
                                            {{ $answer->processing_time_ms < 5000 ? 'text-emerald-600' : ($answer->processing_time_ms < 30000 ? 'text-amber-600' : 'text-red-600') }}">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            {{ number_format($answer->processing_time_ms / 1000, 1) }}с
                                        </span>
                                    @else
                                        <span class="text-neutral-300">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if($answer->response)
                                        <button wire:click="resendReply({{ $answer->id }})"
                                            class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-xs font-medium transition cursor-pointer
                                            {{ $answer->reply_sent_at
                                                ? 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100'
                                                : 'bg-amber-50 text-amber-700 hover:bg-amber-100' }}">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                            </svg>
                                            {{ $answer->reply_sent_at ? $answer->reply_sent_at->format('H:i') . ' Заново' : 'Отправить' }}
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
        {{ $answers->links() }}
    </div>
</div>
