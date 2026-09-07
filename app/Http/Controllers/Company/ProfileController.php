<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Http\Requests\Company\UpdateCompanyProfileRequest;

class ProfileController extends Controller
{
    public function show()
    {
        return view('company.profile-edit', ['company' => auth()->user()->company]);
    }

    public function update(UpdateCompanyProfileRequest $request)
    {
        $data = $request->validated();

        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('company-logos', 'public');
        }

        $request->user()->company()->update($data);

        return back()->with('status', 'Company profile updated.');
    }
}