@php $size = $size ?? 96; @endphp

@if($profile->profile_picture)
    <img src="{{ \Illuminate\Support\Facades\Storage::url($profile->profile_picture) }}"
         alt="{{ $profile->full_name }}"
         style="width: {{ $size }}px; height: {{ $size }}px; object-fit: cover; border-radius: 0.75rem;">
@else
    <div class="d-inline-flex align-items-center justify-content-center"
         style="width: {{ $size }}px; height: {{ $size }}px; background: rgba(15,42,92,0.08); color: var(--jl-navy); border-radius: 0.75rem;">
        <i class="bi bi-person-fill" style="font-size: {{ $size * 0.5 }}px;"></i>
    </div>
@endif