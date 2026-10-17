@extends('errors.shell')

@section('code', '503')
@section('title', 'JobLink is being updated')
@section('message', 'We are doing maintenance and will be back shortly. Try again in a few minutes.')

@section('actions')
    <button type="button" class="err-btn err-btn-gold" onclick="location.reload()">Reload page</button>
@endsection