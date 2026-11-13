{{--
    One certification or seminar entry.
    $i is the row index, or __INDEX__ for the "Add" template. $row is an array.
--}}
@php
    $row = $row ?? [];
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
            <label class="form-label" for="certifications-{{ $i }}-kind">Type</label>
            <select id="certifications-{{ $i }}-kind" name="certifications[{{ $i }}][kind]" class="form-select @error("certifications.$i.kind") is-invalid @enderror">
                @foreach(\App\Models\ProfileCertification::KINDS as $value => $label)
                    <option value="{{ $value }}" @selected(($row['kind'] ?? 'certification') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-7">
            <label class="form-label" for="certifications-{{ $i }}-title">Title</label>
            <input type="text" id="certifications-{{ $i }}-title" name="certifications[{{ $i }}][title]" value="{{ $row['title'] ?? '' }}"
                   class="form-control @error("certifications.$i.title") is-invalid @enderror" maxlength="150" required autocomplete="off"
                   placeholder="e.g. NC II Computer Systems Servicing">
        </div>
        <div class="col-md-7">
            <label class="form-label" for="certifications-{{ $i }}-issuer">Issued by <span class="text-secondary fw-normal">(optional)</span></label>
            <input type="text" id="certifications-{{ $i }}-issuer" name="certifications[{{ $i }}][issuer]" value="{{ $row['issuer'] ?? '' }}"
                   class="form-control @error("certifications.$i.issuer") is-invalid @enderror" maxlength="150" autocomplete="off"
                   placeholder="e.g. TESDA">
        </div>
        <div class="col-md-5">
            <label class="form-label" for="certifications-{{ $i }}-date">Date <span class="text-secondary fw-normal">(optional)</span></label>
            <input type="date" id="certifications-{{ $i }}-date" name="certifications[{{ $i }}][date_received]" value="{{ $row['date_received'] ?? '' }}"
                   class="form-control @error("certifications.$i.date_received") is-invalid @enderror">
        </div>
    </div>
</div>
