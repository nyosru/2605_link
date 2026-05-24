<?php

namespace App\Livewire\Answers;

use App\Models\Answer;
use App\Models\Message;
use App\Services\VkApiService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function resendReply(int $answerId, VkApiService $vk): void
    {
        $answer = Answer::findOrFail($answerId);

        if (!$answer->response) {
            session()->flash('message', "Нет ответа для отправки");
            return;
        }

        try {
            $sent = $vk->sendReply($answer->message, $answer->response);

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

    public function render()
    {
        $query = Answer::with('message');

        if ($this->search) {
            $query->where(function ($q) {
                $q->whereHas('message', function ($q) {
                    $q->where('text', 'like', "%{$this->search}%");
                })->orWhere('response', 'like', "%{$this->search}%");
            });
        }

        $answers = $query->orderBy('created_at', 'desc')->paginate(15);

        return view('livewire.answers.index', [
            'answers' => $answers,
            'pendingCount' => Message::where('payload_type', 'message_new')
                ->whereDoesntHave('answers')
                ->count(),
        ]);
    }
}
