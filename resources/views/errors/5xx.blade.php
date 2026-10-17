@extends('errors.shell')

@section('code', $exception->getStatusCode())
@section('title', 'Something went wrong on our end')
@section('message', 'The page could not be loaded. Try again in a moment. If the problem continues, contact the Job Coordinator.')