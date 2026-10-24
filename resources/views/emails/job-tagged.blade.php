<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>A job was posted for you &middot; JobLink</title>
</head>
<body style="margin:0; padding:24px; background:#F5F7FA; font-family: Arial, Helvetica, sans-serif; color:#1C1E26;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" style="max-width:560px;" cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="background:#fff; border:1px solid rgba(15,42,92,0.08); border-top:4px solid #C9A227; border-radius:8px; padding:32px;">

                            <h1 style="margin:0 0 16px; font-size:1.25rem; color:#0F2A5C;">
                                A job was posted for {{ $jobPosting->company?->company_name ?? 'your company' }}
                            </h1>

                            <p style="margin:0 0 16px; line-height:1.5;">
                                Hi{{ $jobPosting->company?->contact_person ? ' ' . $jobPosting->company->contact_person : '' }},
                            </p>

                            <p style="margin:0 0 16px; line-height:1.5;">
                                The Job Coordinator at PSU Alaminos City posted the following job opening on your company's behalf. It's already live for students and alumni to view and apply to.
                            </p>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 24px; background:#F5F7FA; border-radius:8px;">
                                <tr>
                                    <td style="padding:16px;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="padding:4px 0; color:#6B7280; font-size:0.85rem; width:150px;">Job title</td>
                                                <td style="padding:4px 0; font-weight:bold; color:#0F2A5C;">{{ $jobPosting->title }}</td>
                                            </tr>
                                            <tr>
                                                <td style="padding:4px 0; color:#6B7280; font-size:0.85rem;">Location</td>
                                                <td style="padding:4px 0;">{{ $jobPosting->location }}</td>
                                            </tr>
                                            <tr>
                                                <td style="padding:4px 0; color:#6B7280; font-size:0.85rem;">Application deadline</td>
                                                <td style="padding:4px 0;">{{ $jobPosting->application_deadline->format('F j, Y') }}</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <table role="presentation" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="border-radius:6px; background:#C9A227;">
                                        <a href="{{ route('jobs.show', $jobPosting) }}"
                                           style="display:inline-block; padding:12px 24px; font-weight:bold; color:#081B3D; text-decoration:none;">
                                            View the posting
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:24px 0 0; line-height:1.5; font-size:0.9rem; color:#6B7280;">
                                You can also log in to JobLink any time to review this posting and manage applicants.
                            </p>

                        </td>
                    </tr>
                    <tr>
                        <td style="padding:16px 8px; text-align:center; font-size:0.78rem; color:#6B7280;">
                            JobLink &middot; Pangasinan State University, Alaminos City Campus
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>