<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
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

    <title>STWAG</title>
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
            color: #006A71;
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
            background: #008d97 !important;
        }

        .toggle-password {
            top: 55px !important;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <img src="{{ asset('img/stwaglogo.png') }}" alt="STWAG-Logo" style="height: 100px;">
            <h2>SWAG</h2>
            <em>(Spin to Win and Gain)</em>
        </div>
        <div class="body-content">

            <div>
                Hello, <strong>
                    {{ $user ? $user->firstname . ' ' . substr($user->lastname, 0, 1) . '.' : 'STWAG User' }}</strong>!
            </div>
            <div style="margin-top: 18px;">

                <p id="message">
                    @if (isset($customMessage))
                        {{ $customMessage }}
                    @else
                        Thank you for being a valued member of our community.
                    @endif
                </p>

                <form id="resetPasswordForm" class="w-100 mx-auto mt-5 p-4 border rounded shadow-sm"
                    style="max-width: 400px;">
                    @csrf

                    <input type="hidden" id="token" name="token" value="{{ $token }}">
                    <input type="hidden" id="email" name="email" value="{{ $email }}">

                    @if (isset($customSubject))
                        <div id="message" class="subject text-center fw-bold mb-3">
                            {{ $customSubject }}
                        </div>
                    @endif

                    <div class="mb-3 position-relative">
                        <label for="password" class="form-label">New Password</label>
                        <input type="password" name="password" id="password" class="form-control" required
                            placeholder="Enter new password">
                        <span class="position-absolute top-50 end-0 translate-middle-y me-3 toggle-password"
                            data-target="#password" style="cursor: pointer;">
                            <i class="fa-solid fa-eye"></i>
                        </span>
                        <div class="invalid-feedback">
                            Please enter your new password.
                        </div>
                    </div>

                    <div class="mb-4 position-relative">
                        <label for="password_confirmation" class="form-label">Confirm Password</label>
                        <input type="password" name="password_confirmation" id="password_confirmation"
                            class="form-control" required placeholder="Confirm new password">
                        <span class="position-absolute top-50 end-0 translate-middle-y me-3 toggle-password"
                            data-target="#password_confirmation" style="cursor: pointer;">
                            <i class="fa-solid fa-eye"></i>
                        </span>
                        <div class="invalid-feedback" id="password-match-error">
                            Passwords do not match.
                        </div>
                    </div>

                    <button id="submitBtn" type="submit" class="btn btn-primary w-100">Reset Password</button>

                </form>
                <script>
                    // Toggle Password Visibility with FontAwesome icons
                    document.querySelectorAll('.toggle-password').forEach(span => {
                        span.addEventListener('click', function() {
                            const targetInput = document.querySelector(this.getAttribute('data-target'));
                            const icon = this.querySelector('i');

                            if (targetInput.type === 'password') {
                                targetInput.type = 'text';
                                icon.classList.remove('fa-eye');
                                icon.classList.add('fa-eye-slash');
                            } else {
                                targetInput.type = 'password';
                                icon.classList.remove('fa-eye-slash');
                                icon.classList.add('fa-eye');
                            }
                        });
                    });

                    // alert(
                    //     'Please check your email for the password reset link. If you did not receive an email, please check your spam folder or contact support.'
                    // );
                    // Handle form submission
                    const form = document.getElementById('resetPasswordForm');
                    const message = document.getElementById('message');



                    form.addEventListener('submit', function(event) {
                        event.preventDefault(); // prevent normal form submit

                        const submitBtn = document.getElementById('submitBtn');
                        submitBtn.disabled = true;
                        submitBtn.innerText = 'Processing...';

                        const token = document.getElementById('token').value;
                        const email = document.getElementById('email').value;
                        const password = document.getElementById('password').value;
                        const passwordConfirmation = document.getElementById('password_confirmation').value;

                        // Basic check if passwords match
                        if (password !== passwordConfirmation) {
                            const errorDiv = document.getElementById('password-match-error');
                            errorDiv.style.display = 'block';
                            submitBtn.disabled = false;
                            submitBtn.innerText = 'Reset Password';
                            return;
                        } else {
                            document.getElementById('password-match-error').style.display = 'none';
                        }
                        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute(
                            'content');

                        // Prepare payload for API
                        const payload = {
                            email: email,
                            token: token,
                            password: password,
                            password_confirmation: passwordConfirmation
                        };

                        // 'X-CSRF-TOKEN': csrfToken
                        // credentials: 'same-origin', // send cookies/CSRF if same origin
                        // credentials: 'include', // send cookies/CSRF if same origin
                        // credentials: 'same-origin', // send cookies/CSRF if same origin


                        console.log('Payload:', payload);
                        fetch('https://stwagapi-production.up.railway.app/api/auth/resetpassword', {
                                method: 'POST',
                                body: JSON.stringify(payload),
                                headers: {
                                    'Accept': 'application/json',
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': csrfToken // CSRF token for security,
                                },
                                credentials: 'omit' // send cookies/CSRF if same origin
                            })
                            .then(response => {
                                console.log('Payload Response:', payload);
                                if (!response.ok) {
                                    return response.json().then(data => {
                                        throw data;
                                    });
                                }
                                return response.json();
                            })
                            .then(data => {
                                // console.log('Payload Data:', payload);
                                form.style.display = 'none';
                                // alert(data.message || 'Password reset successful!');
                                if (message) {
                                    message.innerText = data.message || 'Password reset successful!';
                                }
                            })
                            .catch(error => {
                                // console.log('Payload Catch:', payload);
                                if (error.errors) {
                                    let messages = Object.values(error.errors).flat().join('\n');
                                    alert('Validation Error:\n' + messages);
                                } else if (error.message) {
                                    alert('Error Message: ' + error.message);
                                } else {
                                    alert('An unexpected error occurred. Please try again.');
                                }
                                submitBtn.disabled = false;
                            })
                            .finally(() => {
                                if (form.style.display !== 'none') {
                                    submitBtn.disabled = false;
                                    submitBtn.innerText = 'Reset Password';
                                }
                            });

                    });
                </script>


            </div>
        </div>
        <div class="footer">
            {{-- Regards, <br><span>Your STWAG App Team</span> --}}
        </div>
        <div class="copyright">
            &copy; {{ date('Y') }} SWAG. All rights reserved.
        </div>
    </div>

    <script src="{{ asset('bootstrap/js/bootstrap.min.css') }}">
</body>

</html>
