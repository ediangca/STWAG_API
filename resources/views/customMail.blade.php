<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="{{ asset('img/stwag-logo.png') }}">
    <title>STWAG</title>
    <style>
        :root {
            --primary-color: #49b5b2;
        }

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
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.07);
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
            color: var(--primary-color);
            letter-spacing: 1px;
        }

        .subject {
            font-size: 20px;
            font-weight: 600;
            color: #222;
            margin-bottom: 18px;
            text-align: center;
        }

        .body-content {
            font-size: 17px;
            color: #444;
            margin-bottom: 32px;
            line-height: 1.7;
        }

        .action {
            text-align: center;
        }

        .action a {
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

        .footer {
            margin-top: 32px;
            font-size: 16px;
        }

        .footer span {
            font-weight: bold;
            color: var(--primary-color);
        }

        .copyright {
            border-top: 1px solid #e3e8ee;
            padding-top: 18px;
            font-size: 13px;
            color: #a0aec0;
            text-align: center;
            margin-top: 16px;
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
            <img src="{{ asset('img/stwag-logo.png') }}" alt="STWAG Logo" style="height: 100px;">
            <h2>STWAG</h2>
            <em>(Spin to Win and Gain)</em>
        </div>
        <div class="body-content">
            @if (isset($customSubject))
                <div class="subject">
                    {{ $customSubject }}
                </div>
            @endif
            <div>
                Hello, <strong>
                    {{ $user ? $user->firstname . '. ' . substr($user->lastname, 0, 1) : 'STWAG User' }}</strong>!
            </div>
            <div style="margin-top: 18px;">
                @if (isset($customMessage))
                    {{ $customMessage }}
                @else
                    Thank you for being a valued member of our community.
                @endif
                
                @if (isset($customAction))
                    <div class="action" style="text-align: center;">
                        <a href="{{ $customURL }}" class="btn" target="_blank">
                            {{ $customAction ?? 'Click Here!' }}
                        </a>
                    </div>
                @endif
            </div>
        </div>
        <div class="footer">
            Regards, <br><span>Your STWAG App Team</span>
        </div>
        <div class="copyright">
            &copy; {{ date('Y') }} STWAG. All rights reserved.
        </div>
    </div>
</body>

</html>
