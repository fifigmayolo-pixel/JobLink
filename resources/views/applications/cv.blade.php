@extends('layouts.app')

@section('title', 'The CV I Sent')

@section('content')
@php
    $company = $application->jobPosting->company;
@endphp
<div class="row justify-content-center">
    <div class="col-xl-9 col-lg-10">
        <a href="{{ route('applications.show', $application) }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Back to application
        </a>

        <h1 class="h2 mt-3 mb-1">The CV I sent</h1>
        <p class="text-secondary mb-4">
            This is the copy of your CV that {{ $company?->company_name ?? 'the company' }} received for
            <strong>{{ $application->jobPosting->title }}</strong> on {{ $application->date_applied->format('M j, Y') }}.
            Changing your CV later does not change it.
        </p>
        <hr class="jl-rule">

        <div class="jl-card p-4 p-md-5">
            @include('cv._document', ['cv' => $application->cv_snapshot])
        </div>

        <p class="small text-secondary mt-3">
            Want to update your CV for future applications? <a href="{{ route('cv.edit') }}">Open My CV</a>.
        </p>
    </div>
</div>
@endsection
