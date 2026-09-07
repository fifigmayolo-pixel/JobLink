<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\AcceptTermsRequest;

class TermsController extends Controller
{
    public function show()
    {
        return view('auth.terms');
    }

    public function accept(AcceptTermsRequest $request)
    {
        $request->user()->update(['terms_accepted_at' => now()]);

        return redirect()->route('profile.complete');
    }
}