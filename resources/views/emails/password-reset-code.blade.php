@component('mail::message')
# Password Reset Code

Someone requested a password reset for your JobLink account. Use the code below to continue:

@component('mail::panel')
# {{ $code }}
@endcomponent

This code will expire in 15 minutes. If you didn't request this, you can safely ignore this email — your password won't be changed.

Thanks,<br>
JobLink · Pangasinan State University, Alaminos City Campus
@endcomponent