<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
    <h2 class="text-center">Reset Password</h2>

    <div id="alert-box" class="alert d-none" role="alert"></div>

    <form id="resetPasswordForm">
        <div class="mb-3">
            <label>Email address</label>
            <input type="email" class="form-control" name="email" required placeholder="Enter your email" value="{{ $email }}">
        </div>

        <div class="mb-3">
            <label>Token</label>
            <input type="text" class="form-control" name="token" required placeholder="Enter reset token" value="{{ $token }}">
        </div>

        <div class="mb-3">
            <label>New Password</label>
            <input type="password" class="form-control" name="password" required minlength="8" placeholder="New password">
        </div>

        <div class="mb-3">
            <label>Confirm New Password</label>
            <input type="password" class="form-control" name="password_confirmation" required minlength="8" placeholder="Confirm new password">
        </div>

        <button type="submit" class="btn btn-primary w-100">Reset Password</button>
    </form>
</div>

<script>
document.getElementById('resetPasswordForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const form = e.target;
    const formData = new FormData(form);

    const payload = {
        email: formData.get('email'),
        token: formData.get('token'),
        password: formData.get('password'),
        password_confirmation: formData.get('password_confirmation'),
    };

    try {
        const response = await fetch('/api/reset-password', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify(payload)
        });

        const data = await response.json();

        const alertBox = document.getElementById('alert-box');
        if (response.ok) {
            alertBox.className = 'alert alert-success';
            alertBox.innerText = data.message;
            alertBox.classList.remove('d-none');
            form.reset();
        } else {
            let errorMsg = data.message || 'Validation failed';
            if (data.errors) {
                errorMsg = Object.values(data.errors).flat().join(' ');
            }
            alertBox.className = 'alert alert-danger';
            alertBox.innerText = errorMsg;
            alertBox.classList.remove('d-none');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('An unexpected error occurred.');
    }
});
</script>

</body>
</html>
