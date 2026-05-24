<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class QuickLogin extends Component
{
    public string $name = '';
    public string $email = '';
    public ?int $selectedUserId = null;

    public function selectUser(int $id): void
    {
        $user = User::findOrFail($id);
        Auth::login($user, true);
        $this->redirect(route('dashboard'), navigate: false);
    }

    public function register(): void
    {
        $this->validate([
            'name' => 'required|min:2',
            'email' => 'required|email|unique:users,email',
        ]);

        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => Hash::make(Str::random(32)),
        ]);

        Auth::login($user, true);
        $this->redirect(route('dashboard'), navigate: false);
    }

    public function render()
    {
        return view('livewire.auth.quick-login', [
            'users' => User::orderBy('name')->get(),
        ]);
    }
}
