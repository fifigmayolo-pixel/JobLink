<?php

namespace App\Http\Controllers;

use App\Models\TracerStudyLink;

class TracerStudyController extends Controller
{
    public function show()
    {
        return view('alumni.tracer-study-intro', [
            'link' => TracerStudyLink::current(),
        ]);
    }
}