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
            <span style="color:#c9a227;font-size:12px;margin-left:8px;text-transform:uppercase;letter-spacing:1px;">Password Reset</span>
        </div>
        <div style="background:#ffffff;border-radius:0 0 12px 12px;padding:24px;">
            <h1 style="font-size:20px;margin:0 0 16px;color:#0f2a5c;">New temporary password</h1>
            <p style="font-size:15px;line-height:1.6;margin:0 0 16px;">
                The Job Coordinator set a new temporary password for the <strong>{{ $companyName }}</strong> JobLink account. Your previous password no longer works.
            </p>

            <div style="background:#f4f6fa;border-radius:8px;padding:12px 16px;margin:0 0 20px;font-size:14px;line-height:1.8;">
                <strong>Login email:</strong> {{ $loginEmail }}<br>
                <strong>Temporary password:</strong>
                <span style="font-family:Consolas,Menlo,monospace;font-size:16px;letter-spacing:1px;">{{ $tempPassword }}</span>
            </div>

            <p style="margin:0 0 20px;">
                <a href="{{ $loginUrl }}" style="display:inline-block;background:#c9a227;color:#0f2a5c;font-weight:bold;text-decoration:none;padding:12px 22px;border-radius:8px;">Log in to JobLink</a>
            </p>

            <p style="font-size:14px;line-height:1.6;margin:0 0 20px;">
                You'll be asked to choose your own password right after you log in.
            </p>

            <hr style="border:none;border-top:1px solid #e5e7eb;margin:0 0 16px;">
            <p style="font-size:12px;color:#6b7280;margin:0;line-height:1.5;">
                If you weren't expecting this, please contact the Job Coordinator.<br>
                JobLink &middot; Pangasinan State University, Alaminos City Campus
            </p>
        </div>
    </div>
</body>
</html>