<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\MagicLinkLoginService;
use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MagicLinkLoginController extends Controller
{
    public function __invoke(Request $request, User $user, MagicLinkLoginService $magicLinkLoginService): RedirectResponse
    {
        $token = (string) $request->query('token', '');

        abort_unless($magicLinkLoginService->consume($user, $token), 403);

        $panel = Filament::getPanel('admin');

        abort_unless($user->canAccessPanel($panel), 403);

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect()->intended($panel->getUrl());
    }
}
