<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class GoogleController extends Controller
{
    // Step 1: Send the user to Google
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

   public function handleGoogleCallback()
    {
        $googleUser = Socialite::driver('google')->user();
        
        // 1. Bulletproof check: Look for them by Google ID first, then by Email
        $user = User::where('google_id', $googleUser->id)
                    ->orWhere('email', $googleUser->email)
                    ->first();

        if ($user) {
            // Update Google ID just in case they originally registered with an email & password
            if (!$user->google_id) {
                $user->update(['google_id' => $googleUser->id]);
            }

            // Log them in!
            Auth::login($user);

            // Check if they abandoned the setup process previously
            if (empty($user->name) || str_starts_with($user->name, 'G-')) {
                return redirect()->route('username.setup');
            }

            // They are fully set up! Normal login, go to homepage.
            return redirect()->route('homepage');

        } else {
            // 2. Brand New User! First time ever clicking the button.
            $newUser = User::create([
                'name' => 'G-' . $googleUser->id, // Temporary name
                'email' => $googleUser->email,
                'google_id' => $googleUser->id,
                'password' => null,
            ]);

            Auth::login($newUser);

            // Force them to pick a name
            return redirect()->route('username.setup');
        }
    }

    // Step 3: Save the username when they fill out the setup form
    public function updateUsername(Request $request)
    {
        $request->validate([
            // Make sure the chosen name is unique in the 'users' table 'name' column
            'username' => 'required|string|alpha_dash|min:3|max:20|unique:users,name',
        ], [
            'username.unique' => 'This username is already taken!',
            'username.alpha_dash' => 'Usernames can only contain letters, numbers, dashes, and underscores.',
        ]);

        $user = Auth::user();
        $user->name = $request->username;
        $user->save();

        return redirect()->route('homepage')->with('success', 'Welcome to 9Mint! Your account is ready.');
    }
}