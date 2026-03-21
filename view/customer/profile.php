<?php
$current_uid = $_SESSION['auth']['user_id'] ?? null;

// Fetch user details
$stmt = $conn->prepare("SELECT full_name, username, email, phone, account_status, created_at FROM users WHERE uid = ? AND is_deleted = 0");
$stmt->bind_param("s", $current_uid);
$stmt->execute();
$result = $stmt->get_result();
$profile = $result->fetch_assoc();
$stmt->close();

if (!$profile) {
    header('Location: login');
    exit;
}

// Count bookings
$stmt = $conn->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed, SUM(CASE WHEN status IN ('pending','confirmed','in_transit') THEN 1 ELSE 0 END) as active FROM bookings WHERE user_id = ?");
$stmt->bind_param("s", $current_uid);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>

<style>
    .profile-card { border-radius: 15px; box-shadow: 0 7px 20px rgba(0,0,0,0.08); }
    .account-action-card { border-radius: 12px; transition: transform 0.2s; }
    .account-action-card:hover { transform: translateY(-3px); box-shadow: 0 8px 16px rgba(0,0,0,0.1); }
    .stat-number { font-size: 1.5rem; font-weight: 700; }
</style>

<div class="container mt-4">
    <div class="row">
        <!-- Profile Info -->
        <div class="col-lg-8 mb-4">
            <div class="card profile-card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-person-circle me-2"></i>My Profile</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Full Name</label>
                            <p class="fw-semibold mb-0"><?= htmlspecialchars($profile['full_name']) ?></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Username</label>
                            <p class="fw-semibold mb-0"><?= htmlspecialchars($profile['username']) ?></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Email</label>
                            <p class="fw-semibold mb-0"><?= htmlspecialchars($profile['email']) ?></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Phone</label>
                            <p class="fw-semibold mb-0"><?= htmlspecialchars($profile['phone'] ?: 'Not set') ?></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Account Status</label>
                            <p class="mb-0"><span class="badge bg-<?= $profile['account_status'] === 'Active' ? 'success' : 'warning' ?>"><?= htmlspecialchars($profile['account_status']) ?></span></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Member Since</label>
                            <p class="fw-semibold mb-0"><?= date('F j, Y', strtotime($profile['created_at'])) ?></p>
                        </div>
                    </div>

                    <!-- Booking Stats -->
                    <hr>
                    <div class="row text-center">
                        <div class="col-4">
                            <div class="stat-number text-primary"><?= (int)$stats['total'] ?></div>
                            <small class="text-muted">Total Bookings</small>
                        </div>
                        <div class="col-4">
                            <div class="stat-number text-success"><?= (int)$stats['completed'] ?></div>
                            <small class="text-muted">Completed</small>
                        </div>
                        <div class="col-4">
                            <div class="stat-number text-warning"><?= (int)$stats['active'] ?></div>
                            <small class="text-muted">Active</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Account Actions -->
        <div class="col-lg-4">
            <!-- Deactivate Account -->
            <div class="card account-action-card mb-3 border-warning">
                <div class="card-body">
                    <h6 class="card-title text-warning"><i class="bi bi-pause-circle me-2"></i>Deactivate Account</h6>
                    <p class="card-text small text-muted">
                        Temporarily disable your account. You won't be able to log in or make bookings until reactivated by support.
                    </p>
                    <button class="btn btn-outline-warning btn-sm w-100" onclick="handleAccountAction('deactivate')">
                        <i class="bi bi-pause-circle me-1"></i> Deactivate My Account
                    </button>
                </div>
            </div>

            <!-- Delete Account -->
            <div class="card account-action-card border-danger">
                <div class="card-body">
                    <h6 class="card-title text-danger"><i class="bi bi-trash me-2"></i>Delete Account</h6>
                    <p class="card-text small text-muted">
                        Permanently delete your account and all associated data. This action cannot be undone.
                    </p>
                    <button class="btn btn-outline-danger btn-sm w-100" onclick="handleAccountAction('delete')">
                        <i class="bi bi-trash me-1"></i> Delete My Account
                    </button>
                </div>
            </div>

            <div class="card mt-3 border-info">
                <div class="card-body">
                    <h6 class="card-title text-info"><i class="bi bi-info-circle me-2"></i>Need Help?</h6>
                    <p class="card-text small text-muted mb-0">
                        Contact our support team if you need to reactivate your account or have any concerns.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function handleAccountAction(action) {
    const isDelete = action === 'delete';
    const title = isDelete ? 'Delete Account' : 'Deactivate Account';
    const warningColor = isDelete ? '#dc3545' : '#ffc107';

    // Step 1: Initial warning with consequences
    Swal.fire({
        title: `⚠️ ${title}`,
        html: isDelete
            ? `<div class="text-start">
                <p><strong>Warning:</strong> This action is <span class="text-danger fw-bold">permanent and irreversible</span>.</p>
                <ul class="text-muted">
                    <li>Your account will be permanently removed</li>
                    <li>All your booking history will become inaccessible</li>
                    <li>You will need to create a new account to use our services</li>
                    <li>Any pending refunds may be affected</li>
                </ul>
                <p class="text-danger fw-bold">This cannot be undone.</p>
               </div>`
            : `<div class="text-start">
                <p><strong>Note:</strong> Your account will be temporarily disabled.</p>
                <ul class="text-muted">
                    <li>You will be logged out immediately</li>
                    <li>You won't be able to log in until reactivated</li>
                    <li>Your booking history will be preserved</li>
                    <li>Contact support to reactivate your account</li>
                </ul>
               </div>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: warningColor,
        confirmButtonText: 'I understand, continue',
        cancelButtonText: 'Cancel',
        reverseButtons: true,
        focusCancel: true
    }).then((result) => {
        if (!result.isConfirmed) return;

        // Step 2: Password confirmation
        Swal.fire({
            title: 'Confirm Your Identity',
            html: `<p class="text-muted">Enter your password to confirm this action.</p>
                   <input type="password" id="confirmPassword" class="swal2-input" placeholder="Your password" autocomplete="current-password">`,
            icon: 'lock',
            showCancelButton: true,
            confirmButtonColor: warningColor,
            confirmButtonText: 'Verify',
            cancelButtonText: 'Cancel',
            reverseButtons: true,
            focusCancel: true,
            preConfirm: () => {
                const password = document.getElementById('confirmPassword').value;
                if (!password) {
                    Swal.showValidationMessage('Please enter your password');
                    return false;
                }
                return password;
            }
        }).then((result) => {
            if (!result.isConfirmed) return;
            const password = result.value;

            // Step 3: Final confirmation
            Swal.fire({
                title: 'Final Confirmation',
                text: isDelete
                    ? 'Are you absolutely sure you want to permanently delete your account? This is your last chance to cancel.'
                    : 'Are you sure you want to deactivate your account?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: warningColor,
                confirmButtonText: isDelete ? 'Yes, delete permanently' : 'Yes, deactivate',
                cancelButtonText: 'No, keep my account',
                reverseButtons: true,
                focusCancel: true
            }).then((result) => {
                if (!result.isConfirmed) return;

                // Execute the action
                Swal.fire({
                    title: 'Processing...',
                    html: 'Please wait.',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });

                axios.post('controller/user/account-actions.php', {
                    action: action,
                    password: password
                }).then(response => {
                    const data = response.data;
                    if (data.status === 'success') {
                        Swal.fire({
                            title: isDelete ? 'Account Deleted' : 'Account Deactivated',
                            text: data.message,
                            icon: 'success',
                            confirmButtonText: 'OK',
                            allowOutsideClick: false
                        }).then(() => {
                            window.location.href = 'login';
                        });
                    } else {
                        Swal.fire({
                            title: 'Error',
                            text: data.message || 'Something went wrong.',
                            icon: 'error'
                        });
                    }
                }).catch(error => {
                    const errorMsg = (error.response && error.response.data && error.response.data.message)
                        || 'Something went wrong. Please try again.';
                    Swal.fire({
                        title: 'Error',
                        text: errorMsg,
                        icon: 'error'
                    });
                });
            });
        });
    });
}
</script>
