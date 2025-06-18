<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="{{ asset('img/stwag-logo.png') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

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
            color: #49b5b2;
            letter-spacing: 1px;
        }

        .subject {
            font-size: 20px;
            font-weight: bold;
            color: #222;
            margin-bottom: 18px;
            text-align: center;
            font-style: italic;
            font-family: 'Lucida Sans', 'Lucida Sans Regular', 'Lucida Grande', 'Lucida Sans Unicode', Geneva, Verdana, sans-serif;
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
            background: #49b5b2;
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
            background: #49b5b2;
            color: #fff !important;
            border-radius: 5px;
            text-decoration: none;
            font-size: 16px;
            margin: 24px auto;
            transition: background 0.2s;
        }

        .btn:hover {
            background: #2a7270;
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

            <div>
                Hello, <strong>
                    {{ $user ? $user->firstname . ' ' . substr($user->lastname, 0, 1) . '.' : 'STWAG User' }}</strong>!
            </div>
            <div style="margin-top: 18px;">

                @if (isset($customMessage))
                    {{ $customMessage }}
                @else
                    Thank you for being a valued member of our community.
                @endif

                <form method="POST" action="{{ route('password.update') }}"
                    class="w-100 mx-auto mt-5 p-4 border rounded shadow-sm" style="max-width: 400px;">
                    @csrf

                    <input type="hidden" name="token" value="{{ $token }}">
                    <input type="hidden" name="email" value="{{ $email }}">

                    @if (isset($customSubject))
                        <div class="subject">
                            {{ $customSubject }}
                        </div>
                    @endif

                    <div class="mb-3">
                        <label for="password" class="form-label">New Password</label>
                        <input type="password" name="password" id="password" class="form-control" required
                            placeholder="Enter new password">
                        @if ($errors->has('password'))
                            <span class="text-danger">{{ $errors->first('password') }}</span>
                        @endif
                    </div>

                    <div class="mb-4">
                        <label for="password_confirmation" class="form-label">Confirm Password</label>
                        <input type="password" name="password_confirmation" id="password_confirmation"
                            class="form-control" required placeholder="Confirm new password">
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Reset Password</button>

                </form>

            </div>
        </div>
        <div class="footer">
            {{-- Regards, <br><span>Your STWAG App Team</span> --}}
        </div>
        <div class="copyright">
            &copy; {{ date('Y') }} STWAG. All rights reserved.
        </div>
    </div>
</body>

</html>
