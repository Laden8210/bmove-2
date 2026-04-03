<div class="container vh-100 d-flex align-items-center justify-content-center">
    <div class="row w-100 justify-content-center">
        <div class="col-md-5 d-flex flex-column align-items-center">
            <div class="text-center mb-4">
                <img src="public/images/logo.jpg" alt="BMoveXpress" width="160" height="160" class="rounded-circle">
                <div class="fw-bold fs-5 mt-2">BMoveXpress: Smart Movers</div>
            </div>
            <div class="card shadow p-4 w-100">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="fas fa-shield-alt fa-3x text-primary"></i>
                    </div>
                    <h4 class="mb-2">Verify Your Identity</h4>
                    <p class="text-muted small mb-1">
                        A 6-digit verification code has been sent to
                    </p>
                    <p class="fw-bold mb-4" id="maskedContact"><i class="fas fa-envelope me-1"></i><span
                            id="maskedEmail"></span></p>

                    <div id="otpAlert" class="alert d-none" role="alert"></div>

                    <form id="otpForm" autocomplete="off">
                        <div class="d-flex justify-content-center gap-2 mb-4" id="otpInputs">
                            <input type="text" class="otp-digit form-control text-center fw-bold" maxlength="1"
                                inputmode="numeric" pattern="[0-9]" data-index="0" autofocus>
                            <input type="text" class="otp-digit form-control text-center fw-bold" maxlength="1"
                                inputmode="numeric" pattern="[0-9]" data-index="1">
                            <input type="text" class="otp-digit form-control text-center fw-bold" maxlength="1"
                                inputmode="numeric" pattern="[0-9]" data-index="2">
                            <input type="text" class="otp-digit form-control text-center fw-bold" maxlength="1"
                                inputmode="numeric" pattern="[0-9]" data-index="3">
                            <input type="text" class="otp-digit form-control text-center fw-bold" maxlength="1"
                                inputmode="numeric" pattern="[0-9]" data-index="4">
                            <input type="text" class="otp-digit form-control text-center fw-bold" maxlength="1"
                                inputmode="numeric" pattern="[0-9]" data-index="5">
                        </div>

                        <div class="d-grid mb-3">
                            <button type="submit" id="verifyBtn" class="btn btn-primary btn-lg" disabled>
                                <span id="verifyText">Verify Code</span>
                                <span id="verifySpinner" class="spinner-border spinner-border-sm d-none"
                                    role="status"></span>
                            </button>
                        </div>
                    </form>

                    <div class="text-muted small mb-2">
                        Didn't receive the code? Wait <span id="resendTimer">60</span>s
                    </div>
                    <div class="d-flex justify-content-center gap-2" id="resendControls">
                        <button type="button" id="resendSmsBtn" class="btn btn-outline-primary btn-sm" disabled>
                            <i class="fas fa-sms"></i> Resend via SMS
                        </button>
                        <button type="button" id="resendEmailBtn" class="btn btn-outline-secondary btn-sm" disabled>
                            <i class="fas fa-envelope"></i> Resend via Email
                        </button>
                    </div>

                    <div class="mt-3">
                        <a href="login" class="text-muted small text-decoration-none">
                            <i class="fas fa-arrow-left me-1"></i>Back to Login
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .otp-digit {
        width: 50px;
        height: 56px;
        font-size: 1.5rem;
        border: 2px solid #dee2e6;
        border-radius: 10px;
        transition: border-color 0.2s, box-shadow 0.2s;
    }

    .otp-digit:focus {
        border-color: #0d6efd;
        box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
        outline: none;
    }

    .otp-digit.is-invalid {
        border-color: #dc3545;
        animation: shake 0.4s ease;
    }

    @keyframes shake {

        0%,
        100% {
            transform: translateX(0);
        }

        25% {
            transform: translateX(-5px);
        }

        75% {
            transform: translateX(5px);
        }
    }
</style>

<script>
    (function () {
        const digits = document.querySelectorAll('.otp-digit');
        const verifyBtn = document.getElementById('verifyBtn');
        const verifyText = document.getElementById('verifyText');
        const verifySpinner = document.getElementById('verifySpinner');
        const resendSmsBtn = document.getElementById('resendSmsBtn');
        const resendEmailBtn = document.getElementById('resendEmailBtn');
        const resendTimerEl = document.getElementById('resendTimer');
        const otpAlert = document.getElementById('otpAlert');
        const maskedEmailEl = document.getElementById('maskedEmail');

        // Load masked email from session storage (set by login page)
        const maskedContact = sessionStorage.getItem('otp_masked_contact') || '****@****.com';
        maskedEmailEl.textContent = maskedContact;

        // --- OTP Input Logic ---
        digits.forEach((input, idx) => {
            input.addEventListener('input', (e) => {
                const val = e.target.value.replace(/[^0-9]/g, '');
                e.target.value = val;
                if (val && idx < 5) {
                    digits[idx + 1].focus();
                }
                checkComplete();
            });

            input.addEventListener('keydown', (e) => {
                if (e.key === 'Backspace' && !e.target.value && idx > 0) {
                    digits[idx - 1].focus();
                    digits[idx - 1].value = '';
                    checkComplete();
                }
            });

            // Handle paste
            input.addEventListener('paste', (e) => {
                e.preventDefault();
                const pasted = (e.clipboardData.getData('text') || '').replace(/[^0-9]/g, '').slice(0, 6);
                pasted.split('').forEach((ch, i) => {
                    if (digits[i]) digits[i].value = ch;
                });
                const focusIdx = Math.min(pasted.length, 5);
                digits[focusIdx].focus();
                checkComplete();
            });
        });

        function checkComplete() {
            const otp = getOtp();
            verifyBtn.disabled = otp.length !== 6;
            // Auto-submit when all 6 digits are filled
            if (otp.length === 6) {
                submitOtp();
            }
        }

        function getOtp() {
            return Array.from(digits).map(d => d.value).join('');
        }

        function showAlert(msg, type) {
            otpAlert.className = `alert alert-${type}`;
            otpAlert.classList.remove('d-none');
            otpAlert.textContent = msg;
        }

        function hideAlert() {
            otpAlert.classList.add('d-none');
        }

        // --- Submit OTP ---
        document.getElementById('otpForm').addEventListener('submit', (e) => {
            e.preventDefault();
            submitOtp();
        });

        async function submitOtp() {
            const otp = getOtp();
            if (otp.length !== 6) return;

            verifyBtn.disabled = true;
            verifyText.textContent = 'Verifying...';
            verifySpinner.classList.remove('d-none');
            hideAlert();

            try {
                const response = await fetch('controller/auth/verify-otp.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ otp: otp })
                });

                const data = await response.json();

                if (data.status === 'success') {
                    showAlert('Verification successful! Redirecting...', 'success');
                    sessionStorage.removeItem('otp_masked_contact');
                    setTimeout(() => {
                        window.location.href = 'dashboard';
                    }, 800);
                } else {
                    showAlert(data.message || 'Invalid OTP code. Please try again.', 'danger');
                    // Shake the inputs
                    digits.forEach(d => {
                        d.classList.add('is-invalid');
                        d.value = '';
                    });
                    digits[0].focus();
                    setTimeout(() => digits.forEach(d => d.classList.remove('is-invalid')), 500);
                    verifyBtn.disabled = true;
                }
            } catch (error) {
                showAlert('Network error. Please check your connection.', 'danger');
            } finally {
                verifyText.textContent = 'Verify Code';
                verifySpinner.classList.add('d-none');
            }
        }

        // --- Resend Timer ---
        let resendCooldown = 60;
        let resendInterval = null;

        function startResendTimer() {
            resendCooldown = 60;
            resendSmsBtn.disabled = true;
            resendEmailBtn.disabled = true;
            resendTimerEl.parentElement.style.display = 'block';
            resendTimerEl.textContent = resendCooldown;

            resendInterval = setInterval(() => {
                resendCooldown--;
                resendTimerEl.textContent = resendCooldown;
                if (resendCooldown <= 0) {
                    clearInterval(resendInterval);
                    resendSmsBtn.disabled = false;
                    resendEmailBtn.disabled = false;
                    resendTimerEl.parentElement.style.display = 'none';
                    resendSmsBtn.innerHTML = '<i class="fas fa-sms"></i> Resend via SMS';
                    resendEmailBtn.innerHTML = '<i class="fas fa-envelope"></i> Resend via Email';
                }
            }, 1000);
        }

        startResendTimer();

        async function handleResend(method) {
            const btn = method === 'sms' ? resendSmsBtn : resendEmailBtn;
            const origHtml = btn.innerHTML;
            
            resendSmsBtn.disabled = true;
            resendEmailBtn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Sending...';
            hideAlert();

            try {
                const response = await fetch('controller/auth/resend-otp.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ method: method })
                });

                const data = await response.json();

                if (data.status === 'success') {
                    showAlert(data.message, 'info');
                    if (data.masked_contact) {
                        maskedEmailEl.textContent = data.masked_contact;
                    }
                    // Clear inputs
                    digits.forEach(d => d.value = '');
                    digits[0].focus();
                    startResendTimer();
                } else {
                    showAlert(data.message || 'Failed to resend OTP.', 'danger');
                    resendSmsBtn.disabled = false;
                    resendEmailBtn.disabled = false;
                    btn.innerHTML = origHtml;
                }
            } catch (error) {
                showAlert('Network error. Please try again.', 'danger');
                resendSmsBtn.disabled = false;
                resendEmailBtn.disabled = false;
                btn.innerHTML = origHtml;
            }
        }

        resendSmsBtn.addEventListener('click', () => handleResend('sms'));
        resendEmailBtn.addEventListener('click', () => handleResend('email'));
    })();
</script>