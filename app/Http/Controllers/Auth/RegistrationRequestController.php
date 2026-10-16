<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\StoreRegistrationRequest;
use App\Models\RegistrationRequest;

class RegistrationRequestController extends Controller
{
    public function show()
    {
        return view('auth.register');
    }

    public function store(StoreRegistrationRequest $request)
    {
        $data = $request->validated();

        RegistrationRequest::create([
            'student_id_number' => $data['student_id_number'],
            'full_name' => $data['full_name'],
            'email' => $data['email'],
            'applicant_type' => $data['applicant_type'],
            'status' => RegistrationRequest::STATUS_PENDING,
        ]);

        return redirect()->route('register.show')->with('submitted_email', $data['email']);
    }
}