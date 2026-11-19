{{--
    A small status label for one clearance.
    Pass $clearance (a ProfileClearance, or null when nothing is uploaded).
    Valid: "Valid until Mar 3, 2027". Past the date: "Expired" in red. Nothing: "Not uploaded".
--}}
@if(! $clearance)
    <span class="jl-pill jl-pill-urgent"><i class="bi bi-exclamation-circle me-1" aria-hidden="true"></i> Not uploaded</span>
@elseif($clearance->isExpired())
    <span class="jl-pill" style="background: rgba(180,85,63,0.12); color: var(--jl-rose);"><i class="bi bi-x-circle me-1" aria-hidden="true"></i> Expired {{ $clearance->valid_until->format('M j, Y') }}</span>
@else
    <span class="jl-pill jl-pill-neutral"><i class="bi bi-check-circle me-1" aria-hidden="true"></i> Valid until {{ $clearance->valid_until->format('M j, Y') }}</span>
@endif
