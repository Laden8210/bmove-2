<?php
// Redirect if no pending social registration
if (!isset($_SESSION['pending_social_registration'])) {
    echo "<script>window.location.href = 'login';</script>";
    exit;
}
$pending = $_SESSION['pending_social_registration'];
?>

<style>
    .complete-profile-container {
        min-height: 80vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 30px 0;
    }

    .profile-card {
        background: #fff;
        border-radius: 15px;
        box-shadow: 0 7px 30px rgba(58, 59, 69, 0.15);
        overflow: hidden;
        width: 100%;
        max-width: 520px;
    }

    .profile-card-header {
        background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
        color: white;
        padding: 25px 30px;
        text-align: center;
    }

    .profile-card-body {
        padding: 30px;
    }

    .social-info {
        background: rgba(78, 115, 223, 0.08);
        border-radius: 10px;
        padding: 15px;
        margin-bottom: 20px;
        border-left: 4px solid #4e73df;
    }

    .social-info p {
        margin-bottom: 5px;
    }

    .password-requirements {
        font-size: 0.85em;
        color: #6c757d;
        margin-top: 5px;
    }

    .requirement {
        display: block;
        padding: 2px 0;
    }

    .requirement.valid {
        color: #1cc88a;
    }

    .requirement.invalid {
        color: #e74a3b;
    }

    .btn-register {
        background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
        border: none;
        padding: 12px 20px;
        font-weight: 600;
        border-radius: 10px;
        transition: all 0.3s ease;
        color: white;
    }

    .btn-register:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(78, 115, 223, 0.4);
        color: white;
    }
</style>

<div class="complete-profile-container">
    <div class="profile-card">
        <div class="profile-card-header">
            <h3 class="mb-1"><i class="bi bi-person-check me-2"></i>Complete Your Profile</h3>
            <p class="mb-0 opacity-75">Set up your account to get started</p>
        </div>
        <div class="profile-card-body">
            <div class="social-info">
                <p><strong><i class="bi bi-google me-2"></i>Google Account:</strong></p>
                <p class="mb-1"><i class="bi bi-person me-2"></i><?= htmlspecialchars($pending['full_name']) ?></p>
                <p class="mb-0"><i class="bi bi-envelope me-2"></i><?= htmlspecialchars($pending['email']) ?></p>
            </div>

            <form id="completeProfileForm" novalidate>
                <div class="mb-3">
                    <label for="username" class="form-label"><i class="bi bi-at me-1"></i>Username</label>
                    <input type="text" id="username" class="form-control form-control-lg" 
                           placeholder="Choose a username" required minlength="3" maxlength="20">
                    <small class="text-muted">3-20 characters, letters and numbers only</small>
                </div>

                <div class="mb-3">
                    <label for="contact_number" class="form-label"><i class="bi bi-phone me-1"></i>Contact Number <span class="text-muted">(optional)</span></label>
                    <input type="text" id="contact_number" class="form-control form-control-lg" 
                           placeholder="e.g., 09123456789" maxlength="11">
                    <small class="text-muted">Philippine mobile number starting with 09</small>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label"><i class="bi bi-lock me-1"></i>Password</label>
                    <div class="input-group">
                        <input type="password" id="password" class="form-control form-control-lg" 
                               placeholder="Create a password" required minlength="8" maxlength="20">
                        <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('password', this)">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                    <div class="password-requirements mt-2" id="passwordReqs">
                        <span class="requirement" id="req-length">✗ 8-20 characters</span>
                        <span class="requirement" id="req-upper">✗ One uppercase letter</span>
                        <span class="requirement" id="req-lower">✗ One lowercase letter</span>
                        <span class="requirement" id="req-number">✗ One number</span>
                        <span class="requirement" id="req-special">✗ One special character</span>
                    </div>
                </div>

                <div class="mb-4">
                    <label for="confirm_password" class="form-label"><i class="bi bi-lock-fill me-1"></i>Confirm Password</label>
                    <div class="input-group">
                        <input type="password" id="confirm_password" class="form-control form-control-lg" 
                               placeholder="Confirm your password" required>
                        <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('confirm_password', this)">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn btn-register btn-lg w-100" id="submitBtn">
                    <i class="bi bi-check-circle me-2"></i>Create Account
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    function togglePassword(fieldId, btn) {
        const field = document.getElementById(fieldId);
        const icon = btn.querySelector('i');
        if (field.type === 'password') {
            field.type = 'text';
            icon.className = 'bi bi-eye-slash';
        } else {
            field.type = 'password';
            icon.className = 'bi bi-eye';
        }
    }

    // Real-time password validation
    document.getElementById('password').addEventListener('input', function() {
        const pw = this.value;
        updateReq('req-length', pw.length >= 8 && pw.length <= 20);
        updateReq('req-upper', /[A-Z]/.test(pw));
        updateReq('req-lower', /[a-z]/.test(pw));
        updateReq('req-number', /[0-9]/.test(pw));
        updateReq('req-special', /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(pw));
    });

    function updateReq(id, valid) {
        const el = document.getElementById(id);
        el.className = 'requirement ' + (valid ? 'valid' : 'invalid');
        el.textContent = (valid ? '✓ ' : '✗ ') + el.textContent.substring(2);
    }

    // Form submission
    document.getElementById('completeProfileForm').addEventListener('submit', async function(e) {
        e.preventDefault();

        const username = document.getElementById('username').value.trim();
        const contactNumber = document.getElementById('contact_number').value.trim();
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirm_password').value;

        // Client-side validation
        if (username.length < 3 || username.length > 20) {
            Swal.fire({ icon: 'error', text: 'Username must be between 3 and 20 characters' });
            return;
        }
        if (!/^[a-zA-Z0-9]+$/.test(username)) {
            Swal.fire({ icon: 'error', text: 'Username can only contain letters and numbers' });
            return;
        }
        if (password !== confirmPassword) {
            Swal.fire({ icon: 'error', text: 'Passwords do not match' });
            return;
        }

        const btn = document.getElementById('submitBtn');
        const originalText = btn.innerHTML;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Creating account...';
        btn.disabled = true;

        try {
            const res = await fetch('controller/auth/complete-social-profile.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    username: username,
                    contact_number: contactNumber,
                    password: password,
                    confirm_password: confirmPassword
                })
            });

            const data = await res.json();

            if (data.status === 'success') {
                await Swal.fire({
                    icon: 'success',
                    title: 'Account Created!',
                    text: data.message,
                    timer: 2000,
                    showConfirmButton: false
                });
                window.location.href = 'customer-dashboard';
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: data.message });
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        } catch (err) {
            Swal.fire({ icon: 'error', text: 'Network error. Please try again.' });
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    });
</script>
