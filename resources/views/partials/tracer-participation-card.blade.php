<div class="jl-card p-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h5 mb-0">Tracer Study</h2>
        <a href="{{ route('coordinator.tracer-study-links.index') }}" class="btn btn-outline-secondary btn-sm">Manage Link</a>
    </div>

    @if($tracerStats)
        @php
            $eligible = $tracerStats['eligible'];
            $degAccessed = $tracerStats['rate'] * 3.6;
            $tracerDonut = $eligible > 0
                ? "conic-gradient(var(--jl-gold) 0deg {$degAccessed}deg, var(--jl-slate) {$degAccessed}deg 360deg)"
                : 'rgba(107,114,128,0.12)';
        @endphp

        <div class="d-flex align-items-center gap-4 flex-wrap">
            <div style="position: relative; width: 140px; height: 140px; flex-shrink: 0;">
                <div style="width: 100%; height: 100%; border-radius: 50%; background: {{ $tracerDonut }};"></div>
                <div style="position: absolute; inset: 16px; background: #fff; border-radius: 50%; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center;">
                    <div class="display-font h4 mb-0" style="color: var(--jl-navy);">{{ $eligible }}</div>
                    <div class="text-secondary" style="font-size: 0.6rem; line-height: 1.2;">Total Eligible Alumni</div>
                </div>
            </div>
            <div class="flex-grow-1" style="min-width: 160px;">
                <div class="d-flex justify-content-between align-items-center py-1">
                    <span><i class="bi bi-circle-fill me-1" style="color: var(--jl-gold); font-size: 0.6rem;"></i>Accessed Tracer Study</span>
                    <span>{{ $tracerStats['accessed'] }} <span class="text-secondary small">({{ $tracerStats['rate'] }}%)</span></span>
                </div>
                <div class="d-flex justify-content-between align-items-center py-1">
                    <span><i class="bi bi-circle-fill me-1" style="color: var(--jl-slate); font-size: 0.6rem;"></i>Not Yet Accessed</span>
                    <span>{{ $tracerStats['not_accessed'] }} <span class="text-secondary small">({{ $tracerStats['not_accessed_pct'] }}%)</span></span>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
            <span class="text-secondary text-uppercase small">Access Rate</span>
            <span class="display-font h3 mb-0" style="color: var(--jl-navy);">{{ $tracerStats['rate'] }}%</span>
        </div>
        <p class="text-secondary mb-0 mt-2" style="font-size: 0.75rem;">
            Counts alumni who opened the current Tracer Study. JobLink can't see whether the external form was submitted.
        </p>
    @else
        <p class="text-secondary small mb-0">No Tracer Study link is active yet. Add one on the Tracer Study Link page.</p>
    @endif
</div>