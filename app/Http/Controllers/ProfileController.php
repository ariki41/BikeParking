<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\RetiredUserId;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Display the reviews posted by the authenticated user.
     */
    public function reviews(Request $request): View
    {
        $reviews = $request->user()
            ->reviews()
            ->with('parkingSpot')
            ->latest('updated_at')
            ->latest('id')
            ->paginate(10);

        return view('profile.reviews', [
            'reviews' => $reviews,
        ]);
    }

    /**
     * Display the images added by the authenticated user.
     */
    public function images(Request $request): View
    {
        $images = $request->user()
            ->parkingSpotImages()
            ->with('parkingSpot')
            ->latest('created_at')
            ->latest('id')
            ->paginate(10);

        return view('profile.images', [
            'images' => $images,
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());
        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => [
                'required', 'current_password',
            ],
        ]);

        $user = $request->user();

        Auth::logout();

        DB::transaction(function () use ($user): void {
            RetiredUserId::query()->create([
                'user_id_hash' => RetiredUserId::hashFor($user->user_id),
            ]);

            $user->delete();
        });

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
