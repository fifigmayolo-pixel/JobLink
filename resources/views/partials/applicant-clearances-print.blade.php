{{--
    The clearances on the printable CV page: names and validity dates only. The PDF files
    themselves open from the application page, never from here. Pass $application.
--}}
@php $rows = \App\Models\ProfileClearance::forProfile($application->profile_id); @endphp

<section class="mt-4 pt-3" style="border-top: 1px solid rgba(15,42,92,0.15);">
    <h2 class="h6 text-uppercase text-secondary mb-2">Clearances on file</h2>
    <ul class="small mb-1">
        @foreach(config('clearances.types') as $type => $meta)
            @php
                $clearance = $rows->get($type);
                $text = ! $clearance
                    ? 'not on file'
                    : ($clearance->isExpired()
                        ? 'expired '.$clearance->valid_until->format('M j, Y')
                        : 'valid until '.$clearance->valid_until->format('M j, Y'));
            @endphp
            <li>{{ $meta['label'] }}: {{ $text }}</li>
        @endforeach
    </ul>
    <p class="small text-secondary mb-0">The PDF files are opened from the application page in JobLink.</p>
</section>
