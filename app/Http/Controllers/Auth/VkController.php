<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class VkController
{
    public function redirect()
    {
        return Socialite::driver('vkontakte')->redirect();
    }

    public function callback()
    {
        $vkUser = Socialite::driver('vkontakte')->user();

        $user = User::where('provider_id', $vkUser->getId())
            ->orWhere('email', $vkUser->getEmail())
            ->first();

        if (!$user) {
            $user = User::create([
                'name' => $vkUser->getName() ?? $vkUser->getNickname(),
                'email' => $vkUser->getEmail() ?? $vkUser->getId() . '@vk.com',
                'password' => Hash::make(Str::random(32)),
                'provider_id' => $vkUser->getId(),
                'provider_token' => $vkUser->token,
                'provider_name' => 'vkontakte',
            ]);
        } else {
            $user->update([
                'provider_id' => $vkUser->getId(),
                'provider_token' => $vkUser->token,
                'provider_name' => 'vkontakte',
            ]);
        }

        Auth::login($user, true);

        return redirect('/dashboard');
    }
}
