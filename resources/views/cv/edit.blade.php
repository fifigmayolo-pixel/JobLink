@extends('layouts.app')

@section('title', 'My CV')

@section('content')
@php
    $maxSkills = \App\Http\Requests\UpdateCvRequest::MAX_SKILLS;
    $summaryMin = \App\Models\StudentAlumniProfile::CV_SUMMARY_MIN_LENGTH;
    $skills = array_values(array_filter($skills, 'is_string'));

    $sections = [
        'experiences' => [
            'icon' => 'bi-briefcase',
            'title' => 'Work experience or OJT',
            'help' => 'Jobs, internships, OJT and volunteer work all count. We sort them newest first.',
            'empty' => 'No entries yet. Fresh graduate? Your OJT is real experience, so add it here.',
            'add' => 'Add experience',
            'max' => \App\Http\Requests\UpdateCvRequest::MAX_EXPERIENCES,
            'partial' => 'cv._row-experience',
        ],
        'educations' => [
            'icon' => 'bi-mortarboard',
            'title' => 'Other education',
            'help' => 'Senior High School, vocational courses or another college. Your PSU program is already filled in from your school record.',
            'empty' => 'No entries yet. Add your Senior High School or any other schooling.',
            'add' => 'Add education',
            'max' => \App\Http\Requests\UpdateCvRequest::MAX_EDUCATIONS,
            'partial' => 'cv._row-education',
        ],
        'certifications' => [
            'icon' => 'bi-patch-check',
            'title' => 'Certifications and seminars',
            'help' => 'TESDA certificates, online courses, trainings and seminars you attended.',
            'empty' => 'No entries yet. Add any certificate or seminar you have.',
            'add' => 'Add certification or seminar',
            'max' => \App\Http\Requests\UpdateCvRequest::MAX_CERTIFICATIONS,
            'partial' => 'cv._row-certification',
        ],
    ];
@endphp

<div class="row justify-content-center">
    <div class="col-xl-11">
        <h1 class="h2 mb-1">My CV</h1>
        <p class="text-secondary mb-4">This is the CV companies see when you apply. Fill it in once and keep it fresh.</p>
        <hr class="jl-rule">

        <div class="row g-4">
            {{-- Strength card: first on phones, sticky on the right on desktop --}}
            <div class="col-lg-4 order-1 order-lg-2">
                <div class="jl-sidebar-sticky">
                    @include('partials.cv-completeness', ['cv' => $cv])

                    <button type="submit" form="cvForm" class="btn btn-jl-gold w-100 mt-3 d-none d-lg-block" data-save-btn>
                        Save CV
                    </button>
                </div>
            </div>

            <div class="col-lg-8 order-2 order-lg-1">
                {{-- Locked school record --}}
                <div class="jl-card p-4 mb-4">
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        @include('partials.profile-avatar', ['imagePath' => $profile->profile_picture, 'label' => $profile->full_name, 'size' => 56])
                        <div style="min-width: 0;">
                            <div class="fw-semibold" style="color: var(--jl-navy);">{{ $profile->full_name }}</div>
                            <div class="small text-secondary">
                                {{ $profile->course_program }} &middot;
                                {{ $profile->status === 'student' ? $profile->year_level.' - '.$profile->section : 'Graduated '.$profile->year_graduated }}
                            </div>
                        </div>
                        <a href="{{ route('profile.edit') }}" class="btn btn-outline-secondary btn-sm ms-lg-auto">Edit contact details</a>
                    </div>
                    <p class="small text-secondary mt-3 mb-0">
                        This part comes from your school record, so it is filled in for you. Your Job Coordinator can fix it if there is a typo.
                    </p>
                </div>

                <form method="POST" action="{{ route('cv.update') }}" id="cvForm" novalidate>
                    @csrf
                    @method('PUT')

                    {{-- Summary --}}
                    <div class="jl-card p-4 mb-4">
                        <div class="d-flex align-items-start gap-2 mb-3">
                            <span class="jl-section-icon"><i class="bi bi-person-lines-fill" aria-hidden="true"></i></span>
                            <div>
                                <h2 class="h6 text-uppercase text-secondary mb-0">Personal summary</h2>
                                <p class="small text-secondary mb-0 mt-1">Two or three sentences: who you are and what kind of job you want.</p>
                            </div>
                            <span class="jl-pill jl-pill-urgent ms-auto d-none d-sm-inline-flex">Required to apply</span>
                        </div>

                        <label class="visually-hidden" for="cvSummary">Personal summary</label>
                        <textarea id="cvSummary" name="cv_summary" rows="5" maxlength="1000" data-min="{{ $summaryMin }}"
                                  class="form-control @error('cv_summary') is-invalid @enderror"
                                  placeholder="e.g. Graduating BSIT student with hands-on OJT experience in web development. Looking for an entry-level developer role where I can learn and contribute.">{{ old('cv_summary', $profile->cv_summary) }}</textarea>
                        <div class="d-flex justify-content-between gap-3 small mt-2">
                            <span id="summaryHint" class="text-secondary" aria-live="polite"></span>
                            <span id="summaryCount" class="text-secondary flex-shrink-0"></span>
                        </div>
                    </div>

                    {{-- Skills --}}
                    <div class="jl-card p-4 mb-4">
                        <div class="d-flex align-items-start gap-2 mb-3">
                            <span class="jl-section-icon"><i class="bi bi-lightning-charge" aria-hidden="true"></i></span>
                            <div>
                                <h2 class="h6 text-uppercase text-secondary mb-0">Skills</h2>
                                <p class="small text-secondary mb-0 mt-1">Type a skill and press Enter. Companies search these, so be specific (for example Microsoft Excel, not computers).</p>
                            </div>
                            <span class="jl-pill jl-pill-urgent ms-auto d-none d-sm-inline-flex">Required to apply</span>
                        </div>

                        <div class="input-group">
                            <input type="text" id="skillInput" class="form-control" maxlength="60" autocomplete="off"
                                   placeholder="e.g. Customer service" aria-label="Add a skill">
                            <button type="button" class="btn btn-jl-navy" id="skillAdd">Add</button>
                        </div>
                        <div id="skillError" class="small text-danger mt-2 d-none" role="alert"></div>

                        <div id="skillChips" class="d-flex flex-wrap gap-2 mt-3" data-max="{{ $maxSkills }}">
                            @foreach($skills as $skill)
                                <span class="jl-pill jl-pill-neutral" style="white-space: normal;" data-chip>
                                    <span class="text-break">{{ $skill }}</span>
                                    <input type="hidden" name="skills[]" value="{{ $skill }}">
                                    <button type="button" class="btn-close ms-2" style="font-size: 0.55rem;" aria-label="Remove {{ $skill }}" data-chip-remove></button>
                                </span>
                            @endforeach
                        </div>
                        <div class="small text-secondary mt-2"><span id="skillCount">{{ count($skills) }}</span> of {{ $maxSkills }} skills</div>
                    </div>

                    {{-- Repeatable sections --}}
                    @foreach($sections as $key => $section)
                        <div class="jl-card p-4 mb-4" data-repeater="{{ $key }}" data-max="{{ $section['max'] }}">
                            <div class="d-flex align-items-start gap-2 mb-3">
                                <span class="jl-section-icon"><i class="bi {{ $section['icon'] }}" aria-hidden="true"></i></span>
                                <div>
                                    <h2 class="h6 text-uppercase text-secondary mb-0">{{ $section['title'] }}</h2>
                                    <p class="small text-secondary mb-0 mt-1">{{ $section['help'] }}</p>
                                </div>
                            </div>

                            <div data-rows>
                                @foreach($rows[$key] as $row)
                                    @include($section['partial'], ['i' => $loop->index, 'row' => is_array($row) ? $row : []])
                                @endforeach
                            </div>

                            <p class="text-secondary small mb-3 {{ count($rows[$key]) ? 'd-none' : '' }}" data-empty>{{ $section['empty'] }}</p>

                            <button type="button" class="btn btn-outline-secondary btn-sm" data-add>
                                <i class="bi bi-plus-lg" aria-hidden="true"></i> {{ $section['add'] }}
                            </button>
                            <span class="small text-secondary ms-2 d-none" data-max-note>Limit of {{ $section['max'] }} reached.</span>

                            <template data-template>@include($section['partial'], ['i' => '__INDEX__', 'row' => []])</template>
                        </div>
                    @endforeach

                    {{-- Links --}}
                    <div class="jl-card p-4 mb-4">
                        <div class="d-flex align-items-start gap-2 mb-3">
                            <span class="jl-section-icon"><i class="bi bi-link-45deg" aria-hidden="true"></i></span>
                            <div>
                                <h2 class="h6 text-uppercase text-secondary mb-0">Online links</h2>
                                <p class="small text-secondary mb-0 mt-1">Optional. Only https links are accepted.</p>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="linkedinUrl">LinkedIn</label>
                                <input type="url" id="linkedinUrl" name="linkedin_url" value="{{ old('linkedin_url', $profile->linkedin_url) }}"
                                       class="form-control @error('linkedin_url') is-invalid @enderror" maxlength="255" autocomplete="off"
                                       placeholder="https://www.linkedin.com/in/your-name">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="portfolioUrl">Portfolio or website</label>
                                <input type="url" id="portfolioUrl" name="portfolio_url" value="{{ old('portfolio_url', $profile->portfolio_url) }}"
                                       class="form-control @error('portfolio_url') is-invalid @enderror" maxlength="255" autocomplete="off"
                                       placeholder="https://your-portfolio.com">
                            </div>
                        </div>
                    </div>

                    {{-- What companies see --}}
                    <div class="jl-card p-4 mb-4" style="border-left: 4px solid var(--jl-gold) !important;">
                        <h2 class="h6 mb-2"><i class="bi bi-shield-check me-1" aria-hidden="true"></i> What companies see when you apply</h2>
                        <p class="small text-secondary mb-2">Your name, course and year, this CV, and these contact details:</p>
                        <ul class="small mb-2">
                            <li>Email: {{ $profile->personal_email }}</li>
                            <li>Mobile: {{ $profile->mobile_number }}</li>
                            <li>City and province: {{ $profile->municipality_city }}, {{ $profile->province }}</li>
                        </ul>
                        <p class="small text-secondary mb-2">
                            Not shared: your date of birth, gender, civil status and street address.
                        </p>
                        <p class="small text-secondary mb-0">
                            When you apply, the company gets a copy of your CV as it is that day. Editing it later does not change applications you already sent.
                            See our <a href="{{ route('privacy') }}" target="_blank" rel="noopener">Privacy Policy</a>.
                        </p>
                    </div>

                    <button type="submit" class="btn btn-jl-gold btn-lg" data-save-btn>Save CV</button>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    var form = document.getElementById('cvForm');
    if (!form) { return; }

    /* ---------- Personal summary: live counter ---------- */
    var summary = document.getElementById('cvSummary');
    var summaryHint = document.getElementById('summaryHint');
    var summaryCount = document.getElementById('summaryCount');
    var summaryMin = parseInt(summary.dataset.min, 10);

    function updateSummary() {
        // Array.from counts characters the same way the server does (emoji and accents count once).
        var length = Array.from(summary.value.trim()).length;
        var enough = length >= summaryMin;

        summaryCount.textContent = length + ' / ' + summary.maxLength;
        summaryHint.textContent = enough
            ? 'Long enough to apply.'
            : 'Write at least ' + summaryMin + ' characters to apply (' + (summaryMin - length) + ' to go).';
        summaryHint.classList.toggle('text-secondary', !enough);
        summaryHint.style.color = enough ? 'var(--jl-navy)' : '';
        summaryHint.style.fontWeight = enough ? '600' : '';
    }
    summary.addEventListener('input', updateSummary);
    updateSummary();

    /* ---------- Skills: chips ---------- */
    var skillInput = document.getElementById('skillInput');
    var skillAdd = document.getElementById('skillAdd');
    var skillError = document.getElementById('skillError');
    var skillCount = document.getElementById('skillCount');
    var chips = document.getElementById('skillChips');
    var maxSkills = parseInt(chips.dataset.max, 10);

    function skillNames() {
        return Array.prototype.map.call(chips.querySelectorAll('input[name="skills[]"]'), function (el) {
            return el.value.toLowerCase();
        });
    }

    function showSkillError(message) {
        skillError.textContent = message;
        skillError.classList.toggle('d-none', message === '');
    }

    function updateSkillCount() {
        skillCount.textContent = chips.querySelectorAll('[data-chip]').length;
    }

    function addSkill(raw) {
        var name = raw.replace(/\s+/g, ' ').trim();
        if (name === '') { return; }

        if (skillNames().indexOf(name.toLowerCase()) !== -1) {
            showSkillError('"' + name + '" is already on your list.');
            return;
        }
        if (chips.querySelectorAll('[data-chip]').length >= maxSkills) {
            showSkillError('You can add up to ' + maxSkills + ' skills.');
            return;
        }

        // Built with textContent / value, never innerHTML, so typed text can't inject markup.
        var chip = document.createElement('span');
        chip.className = 'jl-pill jl-pill-neutral';
        chip.style.whiteSpace = 'normal';
        chip.setAttribute('data-chip', '');

        var label = document.createElement('span');
        label.className = 'text-break';
        label.textContent = name;

        var hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.name = 'skills[]';
        hidden.value = name;

        var remove = document.createElement('button');
        remove.type = 'button';
        remove.className = 'btn-close ms-2';
        remove.style.fontSize = '0.55rem';
        remove.setAttribute('aria-label', 'Remove ' + name);
        remove.setAttribute('data-chip-remove', '');

        chip.appendChild(label);
        chip.appendChild(hidden);
        chip.appendChild(remove);
        chips.appendChild(chip);

        showSkillError('');
        updateSkillCount();
    }

    // "PHP, MySQL, Excel" pasted or typed with commas becomes three skills.
    function addMany(text) {
        text.split(',').forEach(addSkill);
        skillInput.value = '';
    }

    skillInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ',') {
            e.preventDefault();
            addMany(skillInput.value);
        }
    });
    skillInput.addEventListener('input', function () {
        if (skillInput.value.indexOf(',') !== -1) { addMany(skillInput.value); }
        else { showSkillError(''); }
    });
    skillAdd.addEventListener('click', function () {
        addMany(skillInput.value);
        skillInput.focus();
    });
    chips.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-chip-remove]');
        if (!btn) { return; }
        btn.closest('[data-chip]').remove();
        showSkillError('');
        updateSkillCount();
    });
    updateSkillCount();

    /* ---------- Repeatable sections (experience, education, certifications) ---------- */
    var repeaters = Array.prototype.slice.call(document.querySelectorAll('[data-repeater]'));

    function rowsOf(section) {
        return section.querySelector('[data-rows]');
    }

    function refresh(section) {
        var rows = rowsOf(section).children;
        var max = parseInt(section.dataset.max, 10);
        var full = rows.length >= max;

        Array.prototype.forEach.call(rows, function (row, n) {
            var number = row.querySelector('[data-row-number]');
            if (number) { number.textContent = n + 1; }
        });

        section.querySelector('[data-empty]').classList.toggle('d-none', rows.length > 0);
        section.querySelector('[data-add]').disabled = full;
        section.querySelector('[data-max-note]').classList.toggle('d-none', !full);
    }

    // "Currently ongoing": no end date, and the field is not required.
    function syncCurrent(row) {
        var toggle = row.querySelector('[data-current-toggle]');
        var end = row.querySelector('[data-end-date]');
        if (!toggle || !end) { return; }

        end.disabled = toggle.checked;
        end.required = !toggle.checked;
        if (toggle.checked) { end.value = ''; }
    }

    repeaters.forEach(function (section) {
        var template = section.querySelector('template[data-template]');
        var nextIndex = rowsOf(section).children.length;

        section.querySelector('[data-add]').addEventListener('click', function () {
            if (rowsOf(section).children.length >= parseInt(section.dataset.max, 10)) { return; }

            // Every new row gets its own number, so field names never clash after removals.
            var html = template.innerHTML.split('__INDEX__').join(String(nextIndex++));
            var holder = document.createElement('div');
            holder.innerHTML = html.trim();

            var row = holder.firstElementChild;
            rowsOf(section).appendChild(row);
            refresh(section);

            var first = row.querySelector('input:not([type="checkbox"]):not([type="hidden"]), textarea');
            if (first) { first.focus(); }
        });

        section.addEventListener('click', function (e) {
            var btn = e.target.closest('[data-remove-row]');
            if (!btn) { return; }
            btn.closest('[data-row]').remove();
            refresh(section);
        });

        section.addEventListener('change', function (e) {
            if (e.target.matches('[data-current-toggle]')) {
                syncCurrent(e.target.closest('[data-row]'));
            }
        });

        refresh(section);
    });

    /* ---------- Submit ---------- */
    function rowIsBlank(row) {
        return Array.prototype.every.call(row.querySelectorAll('input, textarea'), function (el) {
            if (el.type === 'checkbox' || el.type === 'hidden') { return true; }
            return el.value.trim() === '';
        });
    }

    function removeBlankRows() {
        repeaters.forEach(function (section) {
            Array.prototype.slice.call(rowsOf(section).children).forEach(function (row) {
                if (rowIsBlank(row)) { row.remove(); }
            });
            refresh(section);
        });
    }

    var saveButtons = Array.prototype.slice.call(document.querySelectorAll('[data-save-btn]'));
    var saveLabels = saveButtons.map(function (btn) { return btn.innerHTML; });
    var saving = false;

    function setSaving(on) {
        saving = on;
        saveButtons.forEach(function (btn, n) {
            btn.disabled = on;
            btn.innerHTML = on
                ? '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Saving...'
                : saveLabels[n];
        });
    }

    // The form has novalidate, so the checks below replace the browser's own pop-up.
    // That lets us throw away rows the student added but never filled in first.
    form.addEventListener('submit', function (e) {
        if (saving) { e.preventDefault(); return; }

        addMany(skillInput.value); // a skill typed but not yet "added" still counts
        removeBlankRows();

        if (!form.checkValidity()) {
            e.preventDefault();
            form.reportValidity();
            return;
        }

        setSaving(true);
    });

    // Coming back with the Back button: undo the locked state.
    window.addEventListener('pageshow', function (e) {
        if (e.persisted) { setSaving(false); }
    });
})();
</script>
@endpush
@endsection
