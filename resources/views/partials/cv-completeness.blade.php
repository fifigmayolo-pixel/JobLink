{{--
    CV strength card. Pass the array from StudentAlumniProfile::cvCompleteness() as $cv.
    Reused on the My CV page now and on the student dashboard later (Phase 10).
--}}
<div class="jl-card p-4">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h2 class="h6 text-uppercase text-secondary mb-0">CV strength</h2>
        <span class="fw-bold" style="font-family: 'Lora', Georgia, serif; font-size: 1.6rem; color: var(--jl-navy); line-height: 1;">{{ $cv['percent'] }}%</span>
    </div>

    <div class="progress mb-3" style="height: 10px; background: rgba(15, 42, 92, 0.08);"
         role="progressbar" aria-label="CV completeness" aria-valuenow="{{ $cv['percent'] }}" aria-valuemin="0" aria-valuemax="100">
        <div class="progress-bar" style="width: {{ $cv['percent'] }}%; background: var(--jl-gold);"></div>
    </div>

    @if($cv['ready'])
        <span class="jl-pill jl-pill-neutral"><i class="bi bi-check-circle-fill me-1" aria-hidden="true"></i> Ready to apply</span>
    @else
        <p class="small mb-0" style="color: var(--jl-navy);">
            <strong>To start applying, add:</strong> {{ implode(' and ', $cv['missing_required']) }}.
        </p>
    @endif

    <ul class="list-unstyled mt-3 mb-0">
        @foreach($cv['sections'] as $section)
            <li class="d-flex align-items-center gap-2 py-1 small">
                @if($section['done'])
                    <i class="bi bi-check-circle-fill" style="color: var(--jl-navy);" aria-hidden="true"></i>
                    <span>{{ $section['label'] }}</span>
                @else
                    <i class="bi bi-circle text-secondary" aria-hidden="true"></i>
                    <span class="text-secondary">{{ $section['label'] }}</span>
                    @if($section['required'])
                        <span class="ms-auto small fw-semibold" style="color: var(--jl-navy);">Required</span>
                    @endif
                @endif
            </li>
        @endforeach
    </ul>
</div>
