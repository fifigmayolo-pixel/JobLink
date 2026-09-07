@extends('layouts.app')

@section('title', 'Notifications')

@section('content')
<div class="d-flex justify-content-between align-items-end mb-4">
    <h1 class="h2 mb-0">Notifications</h1>
    <form method="POST" action="{{ route('notifications.read-all') }}">
        @csrf
        @method('PATCH')
        <button type="submit" class="btn btn-outline-secondary btn-sm">Mark all as read</button>
    </form>
</div>
<hr class="jl-rule">

@forelse($notifications as $notification)
    <div class="jl-card p-4 mb-3 {{ $notification->read_at ? '' : 'border-start border-4' }}" style="{{ $notification->read_at ? '' : 'border-left-color: var(--jl-gold) !important;' }}">
        <div class="d-flex justify-content-between align-items-start gap-2">
            <div>
                <h2 class="h6 mb-1">{{ $notification->title }}</h2>
                <p class="mb-1">{{ $notification->message }}</p>
                <span class="text-secondary small">{{ $notification->created_at->diffForHumans() }}</span>
            </div>
            @unless($notification->read_at)
                <form method="POST" action="{{ route('notifications.read', $notification) }}">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-outline-secondary btn-sm">Mark read</button>
                </form>
            @endunless
        </div>
    </div>
@empty
    <div class="jl-card p-5 text-center">
        <p class="mb-0 text-secondary">No notifications yet.</p>
    </div>
@endforelse

{{ $notifications->links() }}
@endsection