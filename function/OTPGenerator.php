<?php
class OTPGenerator
{
    private $length;

    public function __construct($length = 6)
    {
        $this->length = $length;
    }

    public function generateOTP()
    {
        $otp = '';
        for ($i = 0; $i < $this->length; $i++) {
            $otp .= mt_rand(0, 9);
        }
        $_SESSION['otp'] = $otp; 
        $_SESSION['otp_expiry'] = time() + 300;

        if (!isset($_SESSION['otp_requests'])) {
            $_SESSION['otp_requests'] = 0;
        }
        $_SESSION['otp_requests']++;

        return $otp;
    }


    public function validateOTP($inputOtp)
    {
        if (isset($_SESSION['otp']) && isset($_SESSION['otp_expiry'])) {
            if (time() > $_SESSION['otp_expiry']) {
                return false; // OTP expired
            }
            if ($_SESSION['otp'] === $inputOtp) {
                unset($_SESSION['otp'], $_SESSION['otp_expiry']); 
                return true; 
            }
        }
        return false; 
    }

    public function resetOTP()
    {
        unset($_SESSION['otp'], $_SESSION['otp_expiry']);
        unset($_SESSION['otp_requests']);
    }


    public function getOtpExpiry()
    {
        return isset($_SESSION['otp_expiry']) ? $_SESSION['otp_expiry'] : null;
    }


    public function getOtpRequests()
    {
        return isset($_SESSION['otp_requests']) ? $_SESSION['otp_requests'] : 0;
    }



}