{{--
    One work / OJT / volunteer entry. $i is the row index, or the text __INDEX__ when
    this is the "Add" template (the script swaps it for a real number). $row is an array.
--}}
@php
    $row = $row ?? [];
    $isCurrent = ! empty($row['is_current']);
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
            <label class="form-label" for="experiences-{{ $i }}-kind">Type</label>
            <select id="experiences-{{ $i }}-kind" name="experiences[{{ $i }}][kind]" class="form-select @error("experiences.$i.kind") is-invalid @enderror">
                @foreach(\App\Models\ProfileExperience::KINDS as $value => $label)
                    <option value="{{ $value }}" @selected(($row['kind'] ?? 'work') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-7">
            <label class="form-label" for="experiences-{{ $i }}-position">Position / role</label>
            <input type="text" id="experiences-{{ $i }}-position" name="experiences[{{ $i }}][position]" value="{{ $row['position'] ?? '' }}"
                   class="form-control @error("experiences.$i.position") is-invalid @enderror" maxlength="120" required autocomplete="off"
                   placeholder="e.g. IT Intern">
        </div>
        <div class="col-12">
            <label class="form-label" for="experiences-{{ $i }}-organization">Company / organization</label>
            <input type="text" id="experiences-{{ $i }}-organization" name="experiences[{{ $i }}][organization]" value="{{ $row['organization'] ?? '' }}"
                   class="form-control @error("experiences.$i.organization") is-invalid @enderror" maxlength="150" required autocomplete="off"
                   placeholder="e.g. Alaminos City Hall">
        </div>
        <div class="col-md-6">
            <label class="form-label" for="experiences-{{ $i }}-start">Start date</label>
            <input type="date" id="experiences-{{ $i }}-start" name="experiences[{{ $i }}][start_date]" value="{{ $row['start_date'] ?? '' }}"
                   class="form-control @error("experiences.$i.start_date") is-invalid @enderror" required>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="experiences-{{ $i }}-end">End date</label>
            <input type="date" id="experiences-{{ $i }}-end" name="experiences[{{ $i }}][end_date]" value="{{ $row['end_date'] ?? '' }}"
                   class="form-control @error("experiences.$i.end_date") is-invalid @enderror" data-end-date
                   @disabled($isCurrent) @required(! $isCurrent)>
            <div class="form-check mt-2">
                <input class="form-check-input" type="checkbox" id="experiences-{{ $i }}-current" name="experiences[{{ $i }}][is_current]" value="1" data-current-toggle @checked($isCurrent)>
                <label class="form-check-label small" for="experiences-{{ $i }}-current">Currently ongoing</label>
            </div>
        </div>
        <div class="col-12">
            <label class="form-label" for="experiences-{{ $i }}-description">What did you do? <span class="text-secondary fw-normal">(optional)</span></label>
            <textarea id="experiences-{{ $i }}-description" name="experiences[{{ $i }}][description]" rows="2" maxlength="1000"
                      class="form-control @error("experiences.$i.description") is-invalid @enderror"
                      placeholder="e.g. Set up and maintained the office network and fixed printer issues.">{{ $row['description'] ?? '' }}</textarea>
        </div>
    </div>
</div>
