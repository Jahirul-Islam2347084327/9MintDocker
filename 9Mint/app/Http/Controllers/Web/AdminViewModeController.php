<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AdminViewModeController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user || ! $user->hasAdminRole()) {
            abort(403);
        }

        $data = $request->validate([
            'mode' => ['required', 'in:' . User::ADMIN_VIEW_MODE_ADMIN . ',' . User::ADMIN_VIEW_MODE_CUSTOMER],
        ]);

        $request->session()->put('admin_view_mode', $data['mode']);

        return back()->with('status', $data['mode'] === User::ADMIN_VIEW_MODE_ADMIN
            ? 'Admin view enabled.'
            : 'Customer view enabled.');
    }
}
