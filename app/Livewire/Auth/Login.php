<?php

namespace App\Livewire\Auth;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Login extends Component
{
    public function redirectToVk()
    {
        return redirect()->route('vk.redirect');
    }

    public function render()
    {
        return view('livewire.auth.login');
    }
}
