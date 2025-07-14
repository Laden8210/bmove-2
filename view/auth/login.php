<div class="container vh-100 d-flex align-items-center justify-content-center">
    <div class="row w-100 justify-content-center">
        <div class="col-md-6 d-flex flex-column align-items-center">
            <div class="text-center mb-4">
                <img src="public/images/logo.jpg" alt="Truck" width="200" height="200" class="rounded-circle">
                <div class="fw-bold fs-4 mt-2">BMoveXpress: Smart Movers</div>
            </div>
            <div class="card shadow p-4 w-100">

                <div class="card-body">

                    <?php if (isset($_GET['error'])) : ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?= match ($_GET['error']) {
                                'session_tampered' => 'Security violation detected. Please login again.',
                                'invalid_credentials' => 'Invalid email or password.',
                                default => 'An error occurred. Please try again.'
                            } ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    <h2 class="text-center mb-4">Login</h2>
                    <form id="loginForm" class="row g-3 needs-validation" novalidate>
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username:</label>
                            <input type="text" name="username" id="username" class="form-control" placeholder="Enter username or email" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password:</label>
                            <input type="password" name="password" id="password" class="form-control" placeholder="Password" required>
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
                </div>
            </div>
        </div>
    </div>
</div>

<script>
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
</script>