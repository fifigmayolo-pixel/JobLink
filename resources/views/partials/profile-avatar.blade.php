@php
    $size = $size ?? 96;
    $icon = $icon ?? 'bi-person-fill';
@endphp

@if($imagePath)
    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($imagePath) }}"
         alt="{{ $label ?? '' }}"
         style="width: {{ $size }}px; height: {{ $size }}px; object-fit: cover; border-radius: 0.75rem;">
@else
    <div class="d-inline-flex align-items-center justify-content-center"
         style="width: {{ $size }}px; height: {{ $size }}px; background: rgba(15,42,92,0.08); color: var(--jl-navy); border-radius: 0.75rem;">
        <i class="bi {{ $icon }}" style="font-size: {{ $size * 0.5 }}px;"></i>
    </div>
@endif