<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset Notification</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 0;">
    <table align="center" border="0" cellpadding="0" cellspacing="0" width="600" style="border-collapse: collapse; background-color: #ffffff;">
        <tr>
            <td align="center" style="padding: 20px 0; background-color: #007bff; color: #ffffff; font-size: 24px; font-weight: bold;">
                Password Reset Notification
            </td>
        </tr>
        <tr>
            <td style="padding: 20px; color: #333333; font-size: 16px;">
                <p>Dear {{ $name }},</p>
                <p>We received a request to reset your password. Below is your new password:</p>
                <p style="font-size: 18px; font-weight: bold; color: #007bff;">{{ $new_password }}</p>
                <p>Please log in with this new password and change it to something more secure as soon as possible.</p>
                <p>If you did not request a password reset, please contact our support team immediately.</p>
                <p>Thank you,<br>FMB 52 Team</p>
            </td>
        </tr>
        <tr>
            <td style="padding: 10px; text-align: center; background-color: #f4f4f4; color: #666666; font-size: 12px;">
                &copy; {{ date('Y') }} FMB 52. All rights reserved.
            </td>
        </tr>
    </table>
</body>
</html>
