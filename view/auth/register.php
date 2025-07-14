<div class="container position-relative mt-5">
    <div class="row">
        <div class="col-md-6">
            <img src="public/images/truck.gif" alt="" width="75" height="46" class="mb-3">
            <div class="mb-4 fs-4 fw-bold">BMoveXpress: Smart Movers</div>
            <img src="public/images/logo.jpg" class="img-fluid mb-4" style="max-width: 100%; height: auto;" class="rounded">
        </div>
        <div class="col-md-6">
            <div class="text-center mb-4" style="font-size: 2.5rem;">SignUp</div>
            <form method="post" onsubmit="return validateForm()" class="needs-validation" novalidate id="register-form" action="controller/auth/confirm-registration.php">
                <div class="mb-3">
                    <label for="username" class="form-label">User Name:</label>
                    <input type="text" class="form-control" id="username" name="username" placeholder="User Name" required>
                </div>
                <div class="mb-3">
                    <label for="full_name" class="form-label">Full Name:</label>
                    <input type="text" class="form-control" id="full_name" name="full_name" placeholder="FirstName MI. Lastname" required>
                </div>
                <div class="mb-3">
                    <label for="contact_number" class="form-label">Contact Number:</label>
                    <input type="text" class="form-control" id="contact_number" name="contact_number" placeholder="09123456789" required inputmode="numeric" maxlength="11" pattern="\d{11}">
                </div>
                <div class="mb-3">
                    <label for="emailInput" class="form-label">Email Address:</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="emailInput" name="email_address" placeholder="Samplemail@gmail.com" required>
                        <button type="button" class="btn btn-outline-secondary" id="sendOtpButton" onclick="sendOtp()">Send OTP</button>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="otp_code" class="form-label">OTP Code:</label>
                    <input type="text" class="form-control" id="otp_code" name="otp_code" placeholder="Enter OTP" required>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Password:</label>
                    <input type="password" class="form-control" id="password" name="password" placeholder="Password" required oninput="checkPasswordStrength()">
                    <div class="form-text" id="password-strength"></div>
                </div>
                <div class="mb-3">
                    <label for="confirm_password" class="form-label">Confirm Password:</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Confirm Password" required oninput="checkPasswordMatch()">
                    <div class="form-text" id="password-match"></div>
                </div>


                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="privacy_agreement" name="privacy_agreement" disabled>
                    <label class="form-check-label" for="privacy_agreement">
                        I agree to the <span class="text-primary" style="cursor:pointer;" onclick="togglePrivacyPanel()">Privacy Policy</span>
                    </label>
                </div>



                <button type="submit" class="btn btn-primary w-100" id="submit-btn">Submit</button>
            </form>
        </div>
    </div>
</div>


<script>
    function sendOtp() {
        var email = document.getElementById("emailInput").value;
        if (email) {
            Swal.fire({
                title: 'Sending OTP...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            $.ajax({
                url: 'controller/auth/request-otp.php',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({
                    email: email
                }),
                success: function(response) {
                    Swal.close();
                    var result = JSON.parse(response);
                    if (result.status === 'success') {
                        Swal.fire('Success', 'OTP sent to your email!', 'success');
                    } else {
                        Swal.fire('Error', 'Error sending OTP: ' + result.message, 'error');
                    }
                },
                error: function(xhr, status, error) {
                    Swal.close();
                    Swal.fire('Error', 'An error occurred while sending OTP.', 'error');
                    console.error(error);
                }
            });
        } else {
            Swal.fire('Warning', 'Please enter a valid email address.', 'warning');
        }
    }

    const createRequest = new CreateRequest({
        formSelector: "#register-form",
        submitButtonSelector: "#submit-btn",
        callback: (err, res) => err ? console.error("Form submission error:", err) : console.log(
            "Form submitted successfully:", res)
    });
</script>