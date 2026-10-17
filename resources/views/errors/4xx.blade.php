@extends('errors.shell')

@section('code', $exception->getStatusCode())
@section('title', 'This request could not be completed')
@section('message', 'Go back and try again. If it keeps happening, contact the Job Coordinator.')