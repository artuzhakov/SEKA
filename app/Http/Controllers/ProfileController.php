<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\User;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        // dd($request);

        $request->user()->save();

        return Redirect::route('profile.edit');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    public function updateOnlineStatus(Request $request)
    {
        if ($request->isJson()) {
            $data = $request->json()->all();
            $user_id = $data['user_id'] ?? null;
            $is_online = $data['is_online'] ?? null;
        } else {
            $user_id = $request->user_id;
            $is_online = $request->is_online;
        }

        if ($user_id && !is_null($is_online)) {
            $user = User::find($user_id);
            if ($user) {
                $user->is_online = $is_online;
                $user->save();
                return response()->json(['success' => 'Успешно'], 200);
            }
        }

        return response()->json(['error' => 'Invalid data'], 400);
    }

    public function getOnlineStatus(Request $request, $userId)
    {
        $user = User::find($userId);
        return response()->json(['is_online' => $user->is_online], 200);
    }
}
