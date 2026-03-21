<div class="container position-relative mt-5">
    <div class="row">
        <div class="col-md-6">
            <img src="public/images/truck.gif" alt="" width="75" height="46" class="mb-3">
            <div class="mb-4 fs-4 fw-bold">BMoveXpress: Smart Movers</div>
            <img src="public/images/logo.jpg" class="img-fluid mb-4" style="max-width: 100%; height: auto;"
                class="rounded">
        </div>
        <div class="col-md-6">
            <div class="text-center mb-4" style="font-size: 2.5rem;">SignUp</div>
            <form method="post" onsubmit="return validateForm()" class="needs-validation" novalidate id="register-form"
                action="controller/auth/confirm-registration.php">
                <div class="mb-3">
                    <label for="full_name" class="form-label">Full Name:</label>
                    <input type="text" class="form-control" id="full_name" name="full_name"
                        placeholder="FirstName MI. Lastname" required oninput="validateFullName()">
                    <div id="fullname-warning" class="form-text text-danger d-none">
                        <i class="fas fa-exclamation-triangle"></i> Only letters, spaces, dashes (-), and periods (.)
                        are allowed.
                    </div>
                </div>
                <div class="mb-3">
                    <label for="contact_number" class="form-label">Contact Number:</label>
                    <input type="text" class="form-control" id="contact_number" name="contact_number"
                        placeholder="09123456789" required inputmode="numeric" maxlength="11" pattern="\d{11}"
                        value="09" oninput="validateContactNumber()">
                    <div class="form-text text-danger d-none" id="contact-warning">
                        <i class="fas fa-exclamation-triangle"></i> Invalid format. Must start with "09" followed by 9
                        digits (e.g., 09123456789).
                    </div>
                </div>
                <div class="mb-3">
                    <label for="emailInput" class="form-label">Email Address:</label>
                    <input type="text" class="form-control" id="emailInput" name="email_address"
                        placeholder="Samplemail@gmail.com" required>
                </div>
                <div class="mb-3">
                    <label for="username" class="form-label">User Name:</label>
                    <input type="text" class="form-control" id="username" name="username" placeholder="User Name"
                        required oninput="validateUsername()" pattern="[a-zA-Z0-9]+" maxlength="20">
                    <div id="username-warning" class="form-text text-danger d-none">
                        <i class="fas fa-exclamation-triangle"></i> Only letters and numbers are allowed. No special characters or spaces.
                    </div>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password:</label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="password" name="password" placeholder="Password"
                            required oninput="checkPasswordStrength()">
                        <button class="btn btn-outline-secondary" type="button"
                            onclick="togglePasswordVisibility('password', 'toggle-password-icon')">
                            <i class="fas fa-eye" id="toggle-password-icon"></i>
                        </button>
                    </div>
                    <div class="form-text" id="password-requirements">
                        Password must be at least 8 characters and include:
                        <ul class="mb-0 mt-1" style="font-size: 0.85em;">
                            <li id="req-length" class="text-muted">Minimum 8 characters</li>
                            <li id="req-uppercase" class="text-muted">At least one uppercase letter (A-Z)</li>
                            <li id="req-lowercase" class="text-muted">At least one lowercase letter (a-z)</li>
                            <li id="req-number" class="text-muted">At least one number (0-9)</li>
                            <li id="req-special" class="text-muted">At least one special character (!@#$%^&*)</li>
                        </ul>
                    </div>
                    <div class="form-text" id="password-strength"></div>
                </div>
                <div class="mb-3">
                    <label for="confirm_password" class="form-label">Confirm Password:</label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password"
                            placeholder="Confirm Password" required oninput="checkPasswordMatch()">
                        <button class="btn btn-outline-secondary" type="button"
                            onclick="togglePasswordVisibility('confirm_password', 'toggle-confirm-password-icon')">
                            <i class="fas fa-eye" id="toggle-confirm-password-icon"></i>
                        </button>
                    </div>
                    <div class="form-text" id="password-match"></div>
                </div>

                <button type="submit" class="btn btn-primary w-100" id="submit-btn">Submit</button>
            </form>
        </div>
    </div>
</div>


<script>
    function validateUsername() {
        var usernameInput = document.getElementById('username');
        var warning = document.getElementById('username-warning');
        var value = usernameInput.value;

        // Allow only letters and numbers (no special characters or spaces)
        var sanitizedValue = value.replace(/[^a-zA-Z0-9]/g, '');

        if (value !== sanitizedValue) {
            usernameInput.value = sanitizedValue;
            warning.classList.remove('d-none');
        } else {
            warning.classList.add('d-none');
        }
    }

    function validateFullName() {
        var fullNameInput = document.getElementById('full_name');
        var warning = document.getElementById('fullname-warning');
        var value = fullNameInput.value;

        // Allow only letters, spaces, dashes, and periods
        var sanitizedValue = value.replace(/[^a-zA-Z\s\-\.]/g, '');

        if (value !== sanitizedValue) {
            fullNameInput.value = sanitizedValue;
            warning.classList.remove('d-none');
        } else {
            warning.classList.add('d-none');
        }
    }

    function togglePasswordVisibility(targetId, iconId) {
        var input = document.getElementById(targetId);
        var icon = document.getElementById(iconId);
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }

    function validateContactNumber() {
        var contact = document.getElementById('contact_number').value;
        var warning = document.getElementById('contact-warning');

        // Prevent removing the "09" prefix
        if (!contact.startsWith('09')) {
            document.getElementById('contact_number').value = '09';
            contact = '09';
        }

        // Only allow digits
        document.getElementById('contact_number').value = contact.replace(/[^0-9]/g, '');
        contact = document.getElementById('contact_number').value;

        // Show warning if not empty and doesn't match valid format
        if (contact.length > 2 && !/^09\d{9}$/.test(contact)) {
            warning.classList.remove('d-none');
        } else {
            warning.classList.add('d-none');
        }
    }

    function checkPasswordStrength() {
        var password = document.getElementById('password').value;

        // Check each requirement
        var checks = {
            'req-length': password.length >= 8,
            'req-uppercase': /[A-Z]/.test(password),
            'req-lowercase': /[a-z]/.test(password),
            'req-number': /[0-9]/.test(password),
            'req-special': /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password)
        };

        for (var id in checks) {
            var el = document.getElementById(id);
            if (checks[id]) {
                el.classList.remove('text-muted');
                el.classList.add('text-success');
            } else {
                el.classList.remove('text-success');
                el.classList.add('text-muted');
            }
        }

        // Overall strength indicator
        var metCount = Object.values(checks).filter(Boolean).length;
        var strengthEl = document.getElementById('password-strength');
        if (password.length === 0) {
            strengthEl.textContent = '';
        } else if (metCount <= 2) {
            strengthEl.textContent = 'Weak password';
            strengthEl.className = 'form-text text-danger';
        } else if (metCount <= 4) {
            strengthEl.textContent = 'Moderate password';
            strengthEl.className = 'form-text text-warning';
        } else {
            strengthEl.textContent = 'Strong password';
            strengthEl.className = 'form-text text-success';
        }
    }

    function checkPasswordMatch() {
        var password = document.getElementById('password').value;
        var confirm = document.getElementById('confirm_password').value;
        var matchEl = document.getElementById('password-match');

        if (confirm.length === 0) {
            matchEl.textContent = '';
        } else if (password === confirm) {
            matchEl.textContent = 'Passwords match';
            matchEl.className = 'form-text text-success';
        } else {
            matchEl.textContent = 'Passwords do not match';
            matchEl.className = 'form-text text-danger';
        }
    }

    function validateForm() {
        var fullName = document.getElementById('full_name').value;
        if (!/^[a-zA-Z\s\-\.]+$/.test(fullName)) {
            Swal.fire('Invalid Full Name', 'Full name can only contain letters, spaces, dashes, and periods.', 'warning');
            return false;
        }

        var username = document.getElementById('username').value;
        if (!/^[a-zA-Z0-9]+$/.test(username) || username.length < 3 || username.length > 20) {
            Swal.fire('Invalid Username', 'Username must be 3-20 characters and contain only letters and numbers.', 'warning');
            return false;
        }

        var contact = document.getElementById('contact_number').value;
        if (!/^09\d{9}$/.test(contact)) {
            Swal.fire('Invalid Contact Number', 'Contact number must start with "09" followed by 9 digits.', 'warning');
            return false;
        }

        var password = document.getElementById('password').value;
        if (password.length < 8 || !/[A-Z]/.test(password) || !/[a-z]/.test(password) || !/[0-9]/.test(password) || !/[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password)) {
            Swal.fire('Weak Password', 'Password must meet all the listed requirements.', 'warning');
            return false;
        }

        return true;
    }

    const createRequest = new CreateRequest({
        formSelector: "#register-form",
        submitButtonSelector: "#submit-btn",
        promptMessage: "Are you sure you want to register?",
        callback: (err, res) => {
            if (!err) {
                Swal.fire({
                    title: 'Registration Successful!',
                    text: 'A verification link has been sent to your email. Please check your inbox and click the link to verify your account before logging in.',
                    icon: 'success',
                    confirmButtonText: 'Go to Login'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = 'login';
                    }
                });
            } else {
                console.error("Form submission error:", err);
            }
        }
    });
</script>