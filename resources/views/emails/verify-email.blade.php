<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Your Email</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 40px auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        .header {
            background-color: #4f46e5;
            padding: 32px;
            text-align: center;
        }
        .header h1 {
            color: #ffffff;
            margin: 0;
            font-size: 24px;
        }
        .body {
            padding: 32px;
            color: #374151;
            line-height: 1.6;
        }
        .body p {
            margin: 0 0 16px;
        }
        .btn {
            display: inline-block;
            margin: 24px 0;
            padding: 14px 28px;
            background-color: #4f46e5;
            color: #ffffff;
            text-decoration: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: bold;
        }
        .footer {
            padding: 24px 32px;
            background-color: #f9fafb;
            color: #6b7280;
            font-size: 13px;
            text-align: center;
            border-top: 1px solid #e5e7eb;
        }
        .link-fallback {
            word-break: break-all;
            color: #4f46e5;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Verify Your Email Address</h1>
        </div>
        <div class="body">
            <p>Hi {{ $user->name }},</p>
            <p>
                Thanks for registering! Please click the button below to verify your
                email address and activate your account.
            </p>
            <p style="text-align: center;">
                <a href="{{ $verificationUrl }}" class="btn">Verify Email Address</a>
            </p>
            <p>
                This verification link will expire in <strong>60 minutes</strong>.
            </p>
            <p>
                If you did not create an account, no further action is required.
            </p>
            <p>
                If the button above doesn't work, copy and paste the link below into your browser:
            </p>
            <p class="link-fallback">{{ $verificationUrl }}</p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
        </div>
    </div>
</body>
</html>
