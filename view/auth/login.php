<div class="container vh-100 d-flex align-items-center justify-content-center">
    <div class="row w-100 justify-content-center">
        <div class="col-md-6 d-flex flex-column align-items-center">
            <div class="text-center mb-4">
                <img src="public/images/logo.jpg" alt="Truck" width="200" height="200" class="rounded-circle">
                <div class="fw-bold fs-4 mt-2">BMoveXpress: Smart Movers</div>
            </div>
            <div class="card shadow p-4 w-100">

                <div class="card-body">

                    <?php if (isset($_GET['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?= match ($_GET['error']) {
                                'session_tampered' => 'Security violation detected. Please login again.',
                                'invalid_credentials' => 'Invalid email or password.',
                                'email_not_verified' => 'Please verify your email before logging in. Check your inbox for the verification link.',
                                default => 'An error occurred. Please try again.'
                            } ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_GET['verified']) && $_GET['verified'] == '1'): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            Email verified successfully! You can now log in.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    <h2 class="text-center mb-4">Login</h2>
                    <form id="loginForm" class="row g-3 needs-validation" novalidate>
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username:</label>
                            <input type="text" name="username" id="username" class="form-control"
                                placeholder="Enter username or email" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password:</label>
                            <div class="input-group">
                                <input type="password" name="password" id="password" class="form-control"
                                    placeholder="Password" required>
                                <button class="btn btn-outline-secondary" type="button"
                                    onclick="togglePasswordVisibility('password', 'toggle-password-icon')">
                                    <i class="fas fa-eye" id="toggle-password-icon"></i>
                                </button>
                            </div>
                        </div>
                        <div class="d-grid">
                            <input type="submit" value="Submit" class="btn btn-primary">
                        </div>
                    </form>
                    <?php if (isset($login_error)) { ?>
                        <div class="alert alert-danger mt-3 py-2 text-center"><?php echo $login_error; ?></div>
                    <?php } ?>
                    <?php if (isset($success_message)) { ?>
                        <div class="alert alert-success mt-3 py-2 text-center"><?php echo $success_message; ?></div>
                    <?php } ?>
                    <div class="text-center mt-3 small">
                        Don't have an account?
                        <a href="register" class="text-decoration-none">Sign Up here</a>
                    </div>

                    <!-- Social Login Divider -->
                    <div class="d-flex align-items-center my-3">
                        <hr class="flex-grow-1">
                        <span class="mx-3 text-muted small">or continue with</span>
                        <hr class="flex-grow-1">
                    </div>

                    <!-- Social Login Buttons -->
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-outline-danger btn-sm d-flex align-items-center justify-content-center" id="googleLoginBtn">
                            <i class="fab fa-google me-2"></i> Sign in with Google
                        </button>
                        <button type="button" class="btn btn-outline-primary btn-sm d-flex align-items-center justify-content-center" id="facebookLoginBtn">
                            <i class="fab fa-facebook-f me-2"></i> Sign in with Facebook
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
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

    document.getElementById('loginForm').addEventListener('submit', async (e) => {
        e.preventDefault();

        const formData = {
            email: document.getElementById('username').value,
            password: document.getElementById('password').value,
            csrf_token: document.querySelector('[name="csrf_token"]').value,
        };

        try {
            const response = await fetch('controller/auth/login.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(formData)
            });

            const data = await response.json();

            if (data.status === 'success') {
                window.location.href = 'dashboard';
            } else if (data.status === 'otp_required') {
                // Store masked email for display on OTP page
                if (data.masked_contact) {
                    sessionStorage.setItem('otp_masked_contact', data.masked_contact);
                }
                window.location.href = 'verify-otp';
            } else {
                showError(data.message || 'Login failed. Please try again.');
            }
        } catch (error) {
            showError('Network error. Please check your connection.');
        }
    });

    function showError(message) {
        const alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-danger alert-dismissible fade show';
        alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
        document.querySelector('.card-body').prepend(alertDiv);
    }

    // --- Google Sign-In ---
    // Load Google Identity Services library
    (function() {
        const script = document.createElement('script');
        script.src = 'https://accounts.google.com/gsi/client';
        script.async = true;
        script.defer = true;
        script.onload = function() {
            const googleClientId = '<?= getenv("GOOGLE_CLIENT_ID") ?: "" ?>';
            if (!googleClientId) {
                document.getElementById('googleLoginBtn').style.display = 'none';
                return;
            }
            google.accounts.id.initialize({
                client_id: googleClientId,
                callback: handleGoogleCredential
            });
            document.getElementById('googleLoginBtn').addEventListener('click', function() {
                google.accounts.id.prompt();
            });
        };
        document.head.appendChild(script);
    })();

    async function handleGoogleCredential(response) {
        try {
            const res = await fetch('controller/auth/social-login.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    provider: 'google',
                    id_token: response.credential,
                    csrf_token: document.querySelector('[name="csrf_token"]').value
                })
            });
            const data = await res.json();
            if (data.status === 'success') {
                window.location.href = 'dashboard';
            } else if (data.status === 'otp_required') {
                if (data.masked_contact) {
                    sessionStorage.setItem('otp_masked_contact', data.masked_contact);
                }
                window.location.href = 'verify-otp';
            } else {
                showError(data.message || 'Google login failed.');
            }
        } catch (err) {
            showError('Network error during Google login.');
        }
    }

    // --- Facebook Login ---
    (function() {
        const fbAppId = '<?= getenv("FACEBOOK_APP_ID") ?: "" ?>';
        if (!fbAppId) {
            document.getElementById('facebookLoginBtn').style.display = 'none';
            return;
        }
        window.fbAsyncInit = function() {
            FB.init({ appId: fbAppId, cookie: true, xfbml: true, version: 'v18.0' });
        };
        const script = document.createElement('script');
        script.src = 'https://connect.facebook.net/en_US/sdk.js';
        script.async = true;
        script.defer = true;
        document.head.appendChild(script);

        document.getElementById('facebookLoginBtn').addEventListener('click', function() {
            FB.login(function(response) {
                if (response.authResponse) {
                    handleFacebookLogin(response.authResponse.accessToken);
                }
            }, { scope: 'email,public_profile' });
        });
    })();

    async function handleFacebookLogin(accessToken) {
        try {
            const res = await fetch('controller/auth/social-login.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    provider: 'facebook',
                    access_token: accessToken,
                    csrf_token: document.querySelector('[name="csrf_token"]').value
                })
            });
            const data = await res.json();
            if (data.status === 'success') {
                window.location.href = 'dashboard';
            } else if (data.status === 'otp_required') {
                if (data.masked_contact) {
                    sessionStorage.setItem('otp_masked_contact', data.masked_contact);
                }
                window.location.href = 'verify-otp';
            } else {
                showError(data.message || 'Facebook login failed.');
            }
        } catch (err) {
            showError('Network error during Facebook login.');
        }
    }
</script>