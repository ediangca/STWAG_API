<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="description" content="STWAG - Spin to Win and Gain">
    <link rel="icon" type="image/png" href="{{ asset('img/stwaglogo.png') }}">
    <link href="{{ asset('bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('fontawesome/css/all.min.css') }}" rel="stylesheet">
    {{-- <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"> --}}
    {{-- <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet"> --}}

    <title>SWAG</title>
    <style>
        body {
            background: #f4f6fb;
            margin: 0;
            padding: 0;
            font-family: "Montserrat", sans-serif;
            font-optical-sizing: auto;
            font-weight: <weight>;
            font-style: normal;
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
            color: #006A71;
            letter-spacing: 1px;
        }

        .subject {
            font-size: 20px;
            font-weight: bold;
            color: #222;
            margin-bottom: 18px;
            text-align: center;
            font-style: italic;
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
            background: #006A71;
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
            color: #49b5b2;
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
            background: #006A71;
            color: #fff !important;
            border-radius: 5px;
            text-decoration: none;
            font-size: 16px;
            margin: 24px auto;
            transition: background 0.2s;
        }

        .btn:hover {
            background: #008d97;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <img src="{{ asset('img/stwaglogo.png') }}" alt="STWAG Logo" style="height: 100px;">
            <h2>STWAG</h2>
            <em>(Spin to Win and Gain)</em>
        </div>
        <div class="body-content">
            @if (isset($customSubject))
                <div class="subject">
                    {{ $customSubject }}
                </div>
            @endif
            @if (isset($user))
                <div>
                    Hello, <strong>
                        {{ $user ? $user->firstname . ' ' . substr($user->lastname, 0, 1) . '.' : 'STWAG User' }}</strong>!
                </div>
            @endif
            <div style="margin-top: 18px;">
                <p>
                    @if (isset($customMessage))
                        {{ $customMessage }}
                    @else
                        Thank you for being a valued member of our community.
                    @endif
                </p>

                @if (isset($customAction))
                    <div class="action" style="text-align: center;">
                        <a href="{{ $customURL }}" class="btn" target="_blank">
                            {{ $customAction ?? 'Click Here!' }}
                        </a>
                    </div>
                @endif
            </div>
        </div>
        <br>
        @if (isset($user))
            <div class="footer">
                Regards, <br><span>Your SWAG App Team</span>
            </div>
        @endif
        <div class="copyright">
            &copy; {{ date('Y') }} SWAG. All rights reserved.
        </div>
    </div>

    <script src="{{ asset('bootstrap/js/bootstrap.min.css') }}"></script>
</body>

</html>
