var appUrl = 'https://stwagapi-production.up.railway.app';

// Toggle Password Visibility with FontAwesome icons
document.querySelectorAll('.toggle-password').forEach(span => {
    span.addEventListener('click', function () {
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

// Handle form submission
const form = document.getElementById('resetPasswordForm');
const successMessage = document.getElementById('successMessage');

form.addEventListener('submit', function (event) {
    event.preventDefault(); // prevent normal form submit

    // const email = "{{ $email }}";
    // const token = "{{ $token }}";
    
    const token = document.getElementById('token').value;
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;
    const passwordConfirmation = document.getElementById('password_confirmation').value;

    fetch(`${this.apiUrl}/api/auth/reset-password`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            // 'X-CSRF-TOKEN': '{{ csrf_token() }}' // Not needed for API routes
        },
        body: JSON.stringify({
            email: email,
            token: token,
            password: password,
            password_confirmation: passwordConfirmation
        })
    })
        .then(response => {
            if (!response.ok) {
                return response.json().then(data => { throw data; });
            }
            return response.json();
        })
        .then(data => {
            form.style.display = 'none'; // Hide the form
            successMessage.style.display = 'block'; // Show the success message
            successMessage.innerText = data.message || 'Password reset successful!'; // Show backend message
        })
        .catch(error => {
            console.error('Error:', error);
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

/*
document.getElementById('resetPasswordForm').addEventListener('submit', function (event) {
    event.preventDefault();

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

    fetch(`{${this.apiUrl}/api/auth/reset-password`, { // Adjust the route if needed
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}' // not needed if API routes are exempt from CSRF
        },
        body: JSON.stringify({
            email: email,
            token: token,
            password: password,
            password_confirmation: passwordConfirmation
        })
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
            alert('Password reset successful! You can now log in.');
            
        })
        .catch(error => {
            console.error('Error:', error);
            if (error.errors) {
                let messages = Object.values(error.errors).flat().join('\n');
                alert('Validation Error:\n' + messages);
            } else {
                alert('An unexpected error occurred. Please try again.');
            }
        });
});
*/