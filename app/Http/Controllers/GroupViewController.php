<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class GroupViewController extends Controller
{
    public function toggle(): RedirectResponse
    {
        abort_if(!(Auth::user()->isAdmin() || Auth::user()->isUser()), Response::HTTP_FORBIDDEN, '403 Forbidden');

        session(['group_view' => !session('group_view', true)]);

        return redirect()->back();
    }
}
