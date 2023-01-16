<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class SSOController extends Controller
{
    public function redirectToProvider(): Redirector|RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    public function handleProviderCallback(): Redirector|RedirectResponse
    {
        $user = Socialite::driver('google')->stateless()->user();
        $existingUser = User::query()->where('email', $user->email)->first();
        if (! $existingUser) {
            $newUser = User::query()->create([
                'name' => $user->name,
                'email' => $user->email,
                'password' => $user->id.'@'.$user->email,
            ]);

            event(new Registered($newUser));

            Auth::login($newUser);
        } else {
            $existingUser->update([
                'name' => $user->name,
                'email' => $user->email,
            ]);
            $existingUser->save();
            event(new Registered($existingUser));
            Auth::login($existingUser);
        }

        return redirect(RouteServiceProvider::HOME);
    }
}
