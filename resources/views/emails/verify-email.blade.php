<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Confirm your Ads of Iraq account</title>
</head>
<body style="margin:0;padding:0;background:#ffffff;font-family:Inter,Helvetica,Arial,sans-serif;color:#0a0a0a;">
    <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background:#ffffff;padding:40px 16px;">
        <tr>
            <td align="center">
                <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="max-width:520px;border:1px solid #e5e5e5;">
                    <tr>
                        <td style="padding:40px 32px 24px;text-align:center;border-bottom:1px solid #e5e5e5;">
                            <p style="margin:0;font-family:Georgia,'Times New Roman',serif;font-size:22px;letter-spacing:-0.02em;">Ads of Iraq</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px;">
                            <p style="margin:0 0 8px;font-size:11px;letter-spacing:0.15em;text-transform:uppercase;color:#737373;">Account</p>
                            <h1 style="margin:0 0 20px;font-family:Georgia,'Times New Roman',serif;font-size:26px;font-weight:400;line-height:1.3;">Welcome to Ads of Iraq</h1>
                            <p style="margin:0 0 16px;font-size:15px;line-height:1.6;color:#0a0a0a;">
                                Hello{{ $user->name ? ' '.$user->name : '' }},
                            </p>
                            <p style="margin:0 0 24px;font-size:15px;line-height:1.6;color:#0a0a0a;">
                                Verify your email address to activate your account and start submitting campaigns, bookmarking work, and using your profile.
                            </p>
                            <table cellpadding="0" cellspacing="0" role="presentation" style="margin:0 0 28px;">
                                <tr>
                                    <td style="background:#0a0a0a;">
                                        <a href="{{ $url }}" style="display:inline-block;padding:14px 28px;font-size:13px;font-weight:500;letter-spacing:0.08em;text-transform:uppercase;color:#ffffff;text-decoration:none;">Verify Email Address</a>
                                    </td>
                                </tr>
                            </table>
                            <p style="margin:0 0 12px;font-size:13px;line-height:1.6;color:#737373;">
                                If you did not create an account, you can ignore this email.
                            </p>
                            <p style="margin:0;font-size:13px;line-height:1.6;color:#737373;">
                                If you don’t see this message in your inbox, please check your <strong>spam</strong> or <strong>junk</strong> folder.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:20px 32px;border-top:1px solid #e5e5e5;text-align:center;">
                            <p style="margin:0;font-size:12px;color:#737373;">Ads of Iraq</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
