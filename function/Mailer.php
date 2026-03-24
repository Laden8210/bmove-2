<?php
date_default_timezone_set('Asia/Manila');

require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Mailer
{
    private $mailer;

    public function __construct()
    {
        $this->mailer = new PHPMailer(true);
        $this->mailer->isSMTP();
        $this->mailer->Host = 'smtp.gmail.com';
        $this->mailer->SMTPAuth = true;
        $this->mailer->Username = 'bmovexpressofficial@gmail.com';
        $this->mailer->Password = 'drqgkqozomxjvjhc';
        $this->mailer->Port = 587;
        $this->mailer->setFrom('bmovexpressofficial@gmail.com', 'BmoveXpress');
        $this->mailer->isHTML(true);
    }

    public function sendOtp($email, $otp)
    {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($email);
            $this->mailer->Subject = 'Your OTP From BmoveXpress';
            $this->mailer->Body = "Your OTP is: $otp";
            $this->mailer->send();
            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $this->mailer->ErrorInfo];
        }
    }

    public function sendMessage($email, $subject, $message)
    {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($email);
            $this->mailer->Subject = $subject;
            $this->mailer->Body = $message;
            $this->mailer->send();
            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $this->mailer->ErrorInfo];
        }
    }

    public function sendVerificationEmail($email, $verificationUrl)
    {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($email);
            $this->mailer->Subject = 'Verify Your BmoveXpress Account';
            $this->mailer->Body = '
                <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
                    <div style="text-align: center; margin-bottom: 30px;">
                        <h1 style="color: #333;">BmoveXpress</h1>
                        <p style="color: #666; font-size: 16px;">Smart Movers</p>
                    </div>
                    <div style="background: #f8f9fa; border-radius: 8px; padding: 30px; text-align: center;">
                        <h2 style="color: #333; margin-bottom: 15px;">Verify Your Email Address</h2>
                        <p style="color: #666; margin-bottom: 25px;">
                            Thank you for registering! Please click the button below to verify your email address and activate your account.
                        </p>
                        <a href="' . $verificationUrl . '" 
                           style="display: inline-block; background-color: #0d6efd; color: #ffffff; text-decoration: none; padding: 12px 30px; border-radius: 6px; font-size: 16px; font-weight: bold;">
                            Verify Email Address
                        </a>
                        <p style="color: #999; font-size: 13px; margin-top: 25px;">
                            If the button does not work, copy and paste the following link into your browser:<br>
                            <a href="' . $verificationUrl . '" style="color: #0d6efd;">' . $verificationUrl . '</a>
                        </p>
                    </div>
                    <p style="color: #999; font-size: 12px; text-align: center; margin-top: 20px;">
                        If you did not create an account, please ignore this email.
                    </p>
                </div>
            ';
            $this->mailer->send();
            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $this->mailer->ErrorInfo];
        }
    }
}
