<?php
date_default_timezone_set('Asia/Manila');


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
}
