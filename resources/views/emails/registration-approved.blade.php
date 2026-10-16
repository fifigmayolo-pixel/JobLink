<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body style="margin:0;padding:0;background:#f4f6fa;font-family:Arial,Helvetica,sans-serif;color:#1f2937;">
    <div style="max-width:560px;margin:0 auto;padding:24px 16px;">
        <div style="background:#0f2a5c;border-radius:12px 12px 0 0;padding:20px 24px;border-bottom:4px solid #c9a227;">
            <span style="color:#ffffff;font-size:20px;font-weight:bold;">JobLink</span>
            <span style="color:#c9a227;font-size:12px;margin-left:8px;text-transform:uppercase;letter-spacing:1px;">Registration Approved</span>
        </div>
        <div style="background:#ffffff;border-radius:0 0 12px 12px;padding:24px;">
            <h1 style="font-size:20px;margin:0 0 16px;color:#0f2a5c;">You're approved, {{ $fullName }}!</h1>
            <p style="font-size:15px;line-height:1.6;margin:0 0 16px;">
                The Job Coordinator approved your request to join JobLink as {{ $applicantType === 'alumni' ? 'an' : 'a' }} <strong>{{ ucfirst($applicantType) }}</strong>. You can now activate your account.
            </p>

            <p style="font-size:14px;margin:0 0 8px;">On the activation page, enter these exactly as shown:</p>
            <div style="background:#f4f6fa;border-radius:8px;padding:12px 16px;margin:0 0 20px;font-size:14px;line-height:1.7;">
                <strong>Student ID:</strong> {{ $studentId }}<br>
                <strong>Full name:</strong> {{ $fullName }}
            </div>

            <p style="margin:0 0 20px;">
                <a href="{{ $activateUrl }}" style="display:inline-block;background:#c9a227;color:#0f2a5c;font-weight:bold;text-decoration:none;padding:12px 22px;border-radius:8px;">Activate my account</a>
            </p>

            <p style="font-size:14px;line-height:1.6;margin:0 0 20px;">
                After that, choose your own password, accept the Terms &amp; Conditions, and complete your profile.
            </p>

            <hr style="border:none;border-top:1px solid #e5e7eb;margin:0 0 16px;">
            <p style="font-size:12px;color:#6b7280;margin:0;line-height:1.5;">
                If the button doesn't work, copy this link into your browser: {{ $activateUrl }}<br>
                JobLink &middot; Pangasinan State University, Alaminos City Campus
            </p>
        </div>
    </div>
</body>
</html>