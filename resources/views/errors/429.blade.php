@extends('errors.shell')

@section('code', '429')
@section('title', 'Too many attempts')
@section('message', 'You sent too many requests in a short time. Wait about a minute, then try again.')