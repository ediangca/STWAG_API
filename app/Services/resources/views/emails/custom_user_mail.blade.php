<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>STWAG</title>
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #f4f6fb;
            margin: 0;
            padding: 0;
        }
        .container {
            background: #fff;
            max-width: 540px;
            margin: 48px auto;
            padding: 36px 32px;
            border-radius: 12px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.07);
        }
        .header {
            border-bottom: 2px solid #e3e8ee;
            padding-bottom: 18px;
            margin-bottom: 28px;
            text-align: center;
        }
        .header h2 {
            margin: 0;
            font-size: 28px;
            color: #2d3748;
            letter-spacing: 1px;
        }
        .message {
            font-size: 17px;
            color: #444;
            margin-bottom: 32px;
            line-height: 1.7;
            text-align: center;
        }
        .salutation {
            font-size: 18px;
            font-weight: 500;
            color: #007bff;
            margin-bottom: 24px;
            text-align: right;
        }
        .footer {
            border-top: 1px solid #e3e8ee;
            padding-top: 18px;
            margin-top: 32px;
            font-size: 13px;
            color: #a0aec0;
            text-align: center;
        }
        .btn {
            display: inline-block;
            padding: 12px 28px;
            background: #007bff;
            color: #fff !important;
            border-radius: 5px;
            text-decoration: none;
            font-size: 16px;
            margin: 24px auto;
            transition: background 0.2s;
        }
        .btn:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>
                Hello, {{ $user ? $user->firstname . '. ' . substr($user->lastname, 0, 1) : 'STWAG User' }}!
            </h2>
        </div>
        <div class="message">
            @if (isset($customMessage))
                {{ $customMessage }}
            @else
                Thank you for being a valued member of our community.
            @endif
        </div>
        <div class="salutation">
            Regards,<br>Your STWAG App Team
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} Your Company. All rights reserved.
        </div>
    </div>
</body>
</html>
