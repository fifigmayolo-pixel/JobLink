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
            <span style="color:#c9a227;font-size:12px;margin-left:8px;text-transform:uppercase;letter-spacing:1px;">Application Update</span>
        </div>
        <div style="background:#ffffff;border-radius:0 0 12px 12px;padding:24px;">
            @if($companyName || $jobTitle)
                <p style="font-size:13px;color:#6b7280;margin:0 0 12px;">
                    @if($companyName) From <strong>{{ $companyName }}</strong> @endif
                    @if($companyName && $jobTitle) &middot; @endif
                    @if($jobTitle) Re: {{ $jobTitle }} @endif
                </p>
            @endif

            <h1 style="font-size:20px;margin:0 0 16px;color:#0f2a5c;">{{ $mailTitle }}</h1>
            <p style="font-size:15px;line-height:1.6;margin:0 0 24px;">{!! nl2br(e($mailMessage)) !!}</p>

            <hr style="border:none;border-top:1px solid #e5e7eb;margin:0 0 16px;">
            <p style="font-size:12px;color:#6b7280;margin:0;line-height:1.5;">
                @if($replyToEmail)
                    Replying to this email will reach {{ $companyName ?? 'the company' }} directly.
                @endif
                You can also see this in your JobLink notifications.
            </p>
        </div>
    </div>
</body>
</html>