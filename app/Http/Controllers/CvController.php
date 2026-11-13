<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateCvRequest;
use App\Models\ProfileExperience;
use App\Models\ProfileEducation;
use App\Models\ProfileCertification;
use App\Models\StudentAlumniProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class CvController extends Controller
{
    public function edit(Request $request)
    {
        $profile = $this->profileFor($request)->loadCv();

        // After a failed save, show exactly what the student typed (even if they
        // removed every row of a section). Otherwise show what is in the database.
        $fromOld = session()->hasOldInput();

        $skills = $fromOld
            ? old('skills', [])
            : $profile->skills->pluck('name')->all();

        $rows = [
            'experiences' => $fromOld ? (array) old('experiences', []) : $profile->experiences->map(fn (ProfileExperience $e) => [
                'kind' => $e->kind,
                'position' => $e->position,
                'organization' => $e->organization,
                'start_date' => $e->start_date?->format('Y-m-d'),
                'end_date' => $e->end_date?->format('Y-m-d'),
                'is_current' => $e->isCurrent(),
                'description' => $e->description,
            ])->all(),

            'educations' => $fromOld ? (array) old('educations', []) : $profile->educations->map(fn (ProfileEducation $e) => [
                'level' => $e->level,
                'school_name' => $e->school_name,
                'degree_program' => $e->degree_program,
                'start_year' => $e->start_year,
                'end_year' => $e->end_year,
                'details' => $e->details,
            ])->all(),

            'certifications' => $fromOld ? (array) old('certifications', []) : $profile->certifications->map(fn (ProfileCertification $c) => [
                'kind' => $c->kind,
                'title' => $c->title,
                'issuer' => $c->issuer,
                'date_received' => $c->date_received?->format('Y-m-d'),
            ])->all(),
        ];

        return view('cv.edit', [
            'profile' => $profile,
            'cv' => $profile->cvCompleteness(),
            'skills' => is_array($skills) ? $skills : [],
            'rows' => $rows,
        ]);
    }

    public function update(UpdateCvRequest $request)
    {
        // The owner always comes from the logged-in user, never from the request,
        // so one student can't write to another student's CV.
        $profile = $this->profileFor($request);
        $data = $request->validated();

        DB::transaction(function () use ($profile, $data) {
            $profile->update([
                'cv_summary' => $data['cv_summary'] ?? null,
                'linkedin_url' => $data['linkedin_url'] ?? null,
                'portfolio_url' => $data['portfolio_url'] ?? null,
            ]);

            // Each section is replaced as a whole. Nothing else points at these
            // row ids (applications keep a JSON snapshot), so this is safe.
            $profile->skills()->delete();
            $profile->skills()->createMany(
                collect($data['skills'] ?? [])->map(fn ($name) => ['name' => $name])->all()
            );

            $profile->experiences()->delete();
            $profile->experiences()->createMany(
                collect($data['experiences'] ?? [])->map(fn ($row) => [
                    'kind' => $row['kind'],
                    'position' => $row['position'],
                    'organization' => $row['organization'],
                    'start_date' => $row['start_date'],
                    'end_date' => empty($row['is_current']) ? ($row['end_date'] ?? null) : null,
                    'description' => $row['description'] ?? null,
                ])->all()
            );

            $profile->educations()->delete();
            $profile->educations()->createMany(
                collect($data['educations'] ?? [])
                    ->map(fn ($row) => Arr::only($row, [
                        'level', 'school_name', 'degree_program', 'start_year', 'end_year', 'details',
                    ]))->all()
            );

            $profile->certifications()->delete();
            $profile->certifications()->createMany(
                collect($data['certifications'] ?? [])
                    ->map(fn ($row) => Arr::only($row, [
                        'kind', 'title', 'issuer', 'date_received',
                    ]))->all()
            );
        });

        $cv = $profile->fresh()->cvCompleteness();

        $message = $cv['ready']
            ? 'CV saved. You can now apply to jobs.'
            : 'CV saved. To start applying, add: '.implode(' and ', $cv['missing_required']).'.';

        return redirect()->route('cv.edit')->with('status', $message);
    }

    private function profileFor(Request $request): StudentAlumniProfile
    {
        $profile = $request->user()->profile;

        abort_unless($profile, 403);

        return $profile;
    }
}
