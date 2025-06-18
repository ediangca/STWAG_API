<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" href="{{ asset('img/stwag-logo.png') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">


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
            background: #2a7270 !important;
        }

        .toggle-password {
            top: 55px !important;
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

                <form id="resetPasswordForm" class="w-100 mx-auto mt-5 p-4 border rounded shadow-sm"
                    style="max-width: 400px;">
                    @csrf

                    <input type="hidden" id="token" name="token" value="{{ $token }}">
                    <input type="hidden" id="email" name="email" value="{{ $email }}">

                    @if (isset($customSubject))
                        <div class="subject text-center fw-bold mb-3">
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

                    <button id="submit" type="submit" class="btn btn-primary w-100">Reset Password</button>
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

                    alert(
                        'Please check your email for the password reset link. If you did not receive an email, please check your spam folder or contact support.'
                    );
                    // Handle form submission
                    const form = document.getElementById('resetPasswordForm');
                    const successMessage = document.getElementById('successMessage');



                    form.addEventListener('submit', function(event) {
                        event.preventDefault(); // prevent normal form submit

                        // const email = "{{ $email }}";
                        // const token = "{{ $token }}";

                        const token = document.getElementById('token').value;
                        const email = document.getElementById('email').value;
                        const password = document.getElementById('password').value;
                        const passwordConfirmation = document.getElementById('password_confirmation').value;

                        // Basic check if passwords match
                        if (password !== passwordConfirmation) {
                            const errorDiv = document.getElementById('password-match-error');
                            errorDiv.style.display = 'block';
                            return;
                        } else {
                            document.getElementById('password-match-error').style.display = 'none';
                        }


                        // Prepare payload for API
                        const payload = {
                            email: email,
                            token: token,
                            password: password,
                            password_confirmation: passwordConfirmation
                        };

                        alert(
                            'Please wait while we process your request. This may take a few seconds.'
                        );
                        
                        
                       // The code below is unrelated to your Blade file's password reset logic.
                        // Your error is about the sessions table, which is a Laravel backend/database issue.
                        // The code here is for frontend password reset and does not affect sessions table structure or queries.

                        fetch('https://stwagapi-production.up.railway.app/api/auth/reset-password', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify(payload)
                            })
                            .then(response => {
                            if (!response.ok) {
                                return response.json().then(data => {
                                throw data;
                                });
                            }
                            return response.json();
                            })
                            .then(data => {
                            form.style.display = 'none';
                            // Show a success message (you can customize this)
                            alert(data.message || 'Password reset successful!');
                            })
                            .catch(error => {
                            if (error.errors) {
                                let messages = Object.values(error.errors).flat().join('\n');
                                alert('Validation Error:\n' + messages);
                            } else if (error.message) {
                                alert(error.message);
                            } else {
                                alert('An unexpected error occurred. Please try again.');
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
            &copy; {{ date('Y') }} STWAG. All rights reserved.
        </div>
    </div>

    // {{-- <script src="{{ asset('js/reset-password.js') }}"></script> --}}
</body>

</html>
