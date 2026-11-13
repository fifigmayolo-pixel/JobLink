{{--
    One "other education" entry (Senior High, vocational, another college...).
    The student's PSU program is shown from their school record, never typed here.
    $i is the row index, or __INDEX__ for the "Add" template. $row is an array.
--}}
@php
    $row = $row ?? [];
    $thisYear = now()->year;
@endphp
<div class="cv-row rounded border p-3 mb-3" data-row style="background: var(--jl-cream);">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <span class="small fw-semibold" style="color: var(--jl-navy);">Entry <span data-row-number></span></span>
        <button type="button" class="btn btn-outline-secondary jl-icon-btn" data-remove-row aria-label="Remove this entry">
            <i class="bi bi-trash" aria-hidden="true"></i>
        </button>
    </div>

    <div class="row g-3">
        <div class="col-md-5">
            <label class="form-label" for="educations-{{ $i }}-level">Level</label>
            <select id="educations-{{ $i }}-level" name="educations[{{ $i }}][level]" class="form-select @error("educations.$i.level") is-invalid @enderror">
                @foreach(\App\Models\ProfileEducation::LEVELS as $level)
                    <option value="{{ $level }}" @selected(($row['level'] ?? \App\Models\ProfileEducation::LEVELS[0]) === $level)>{{ $level }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-7">
            <label class="form-label" for="educations-{{ $i }}-school">School</label>
            <input type="text" id="educations-{{ $i }}-school" name="educations[{{ $i }}][school_name]" value="{{ $row['school_name'] ?? '' }}"
                   class="form-control @error("educations.$i.school_name") is-invalid @enderror" maxlength="150" required autocomplete="off"
                   placeholder="e.g. Alaminos City National High School">
        </div>
        <div class="col-12">
            <label class="form-label" for="educations-{{ $i }}-degree">Course / strand <span class="text-secondary fw-normal">(optional)</span></label>
            <input type="text" id="educations-{{ $i }}-degree" name="educations[{{ $i }}][degree_program]" value="{{ $row['degree_program'] ?? '' }}"
                   class="form-control @error("educations.$i.degree_program") is-invalid @enderror" maxlength="150" autocomplete="off"
                   placeholder="e.g. ICT strand">
        </div>
        <div class="col-md-6">
            <label class="form-label" for="educations-{{ $i }}-start">Start year</label>
            <input type="number" id="educations-{{ $i }}-start" name="educations[{{ $i }}][start_year]" value="{{ $row['start_year'] ?? '' }}"
                   class="form-control @error("educations.$i.start_year") is-invalid @enderror" min="1980" max="{{ $thisYear }}" required inputmode="numeric">
        </div>
        <div class="col-md-6">
            <label class="form-label" for="educations-{{ $i }}-end">End year <span class="text-secondary fw-normal">(blank if ongoing)</span></label>
            <input type="number" id="educations-{{ $i }}-end" name="educations[{{ $i }}][end_year]" value="{{ $row['end_year'] ?? '' }}"
                   class="form-control @error("educations.$i.end_year") is-invalid @enderror" min="1980" max="{{ $thisYear + 6 }}" inputmode="numeric">
        </div>
        <div class="col-12">
            <label class="form-label" for="educations-{{ $i }}-details">Honors or notes <span class="text-secondary fw-normal">(optional)</span></label>
            <textarea id="educations-{{ $i }}-details" name="educations[{{ $i }}][details]" rows="2" maxlength="500"
                      class="form-control @error("educations.$i.details") is-invalid @enderror"
                      placeholder="e.g. With Honors, Best in Capstone">{{ $row['details'] ?? '' }}</textarea>
        </div>
    </div>
</div>
