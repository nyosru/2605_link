<?php

namespace App\Livewire\Messages;

use App\Models\Message;
use App\Services\MessageService;
use App\Services\ProcessingService;
use App\Services\VkApiService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';
    public string $channel = 'testPM';
    public string $typeFilter = '';

    protected $queryString = ['search', 'typeFilter'];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingTypeFilter(): void
    {
        $this->resetPage();
    }

    public function sync(MessageService $service): void
    {
        $count = $service->syncFromApi($this->channel);
        if ($count > 0) {
            session()->flash('message', "Загружено {$count} новых сообщений");
        } else {
            session()->flash('message', 'Новых сообщений нет');
        }
    }

    public function toggleSent(Message $message, MessageService $service): void
    {
        if ($message->status?->sent_for_processing) {
            $service->markNotSent($message);
        } else {
            $service->markSent($message);
        }
    }

    public function toggleResponded(Message $message, MessageService $service): void
    {
        if ($message->status?->response_received) {
            $service->markNotResponded($message);
        } else {
            $service->markResponded($message);
        }
    }

    public function sendForProcessing(Message $message, ProcessingService $service): void
    {
        if ($message->answers()->exists()) {
            session()->flash('message', "Сообщение #{$message->external_id} уже обработано");
            return;
        }

        try {
            $answer = $service->process($message);
            session()->flash('message', "Сообщение #{$message->external_id} обработано за {$answer->processing_time_ms}ms");
        } catch (\Throwable $e) {
            session()->flash('message', "Ошибка: {$e->getMessage()}");
        }
    }

    public function retryProcessing(Message $message, ProcessingService $service): void
    {
        $message->answers()->delete();

        if ($message->status) {
            $message->status->update([
                'sent_for_processing' => false,
                'sent_at' => null,
                'response_received' => false,
                'responded_at' => null,
            ]);
        }

        try {
            $answer = $service->process($message);
            session()->flash('message', "Сообщение #{$message->external_id} переобработано за {$answer->processing_time_ms}ms");
        } catch (\Throwable $e) {
            session()->flash('message', "Ошибка: {$e->getMessage()}");
        }
    }

    public function resendReply(int $messageId, VkApiService $vk): void
    {
        $message = Message::findOrFail($messageId);
        $answer = $message->answers()->latest()->first();

        if (!$answer || !$answer->response) {
            session()->flash('message', "Нет ответа для отправки");
            return;
        }

        try {
            $sent = $vk->sendReply($message, $answer->response);

            if ($sent) {
                $answer->update(['reply_sent_at' => now()]);
                session()->flash('message', "Ответ отправлен в VK");
            } else {
                session()->flash('message', "Ошибка отправки в VK");
            }
        } catch (\Throwable $e) {
            session()->flash('message', "Ошибка: {$e->getMessage()}");
        }
    }

    public function render(MessageService $service)
    {
        $query = Message::with('status', 'latestAnswer');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('text', 'like', "%{$this->search}%")
                    ->orWhere('channel', 'like', "%{$this->search}%")
                    ->orWhere('payload_type', 'like', "%{$this->search}%");
            });
        }

        if ($this->typeFilter) {
            $query->where('payload_type', $this->typeFilter);
        }

        $messages = $query->orderBy('received_at', 'desc')->paginate(15);

        return view('livewire.messages.index', [
            'messages' => $messages,
            'total' => Message::count(),
            'unprocessed' => Message::whereDoesntHave('status', function ($q) {
                $q->where('sent_for_processing', true);
            })->count(),
            'types' => Message::select('payload_type')->distinct()->pluck('payload_type')->filter(),
        ]);
    }
}
