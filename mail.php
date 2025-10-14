<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>

<form method="post">
    <button name="btn">Send Mail</button>
</form>

<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// if mail already sent, show alert using GET parameter
if (isset($_GET['sent'])) {
    echo "<script>alert('Email Has Been Sent')</script>";
}

if (isset($_POST['btn'])) {

    // mainually include phpmailer files
    require 'phpmailer/src/Exception.php';
    require 'phpmailer/src/PHPMailer.php';
    require 'phpmailer/src/SMTP.php';

    $mail = new PHPMailer(true);

    try {

        // server setting 
        $mail->isSMTP();  // set mailer to use smtp 
        $mail->Host = 'smtp.gmail.com'; // set the smtp server to send through (GMAIL)
        $mail->SMTPAuth  = true;  // Enable SMTP authantication
        $mail->Username = 'mhassansherazi152@gmail.com';  // SMTP username (Your Gmail Address) 
        $mail->Password = 'hxsa zrqh qkwj viyk';  // smtp password (your gmail app password)
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;  //  Also we use just "tls";   // enable tls encryption
        $mail->Port = 587;   // tcp port to connect  to 587 to tls

        // Recipitents
        $mail->setFrom('mhassansherazi152@gmail.com', 'Hassan Shirazi');  // sender's mail
        $mail->addAddress('hassan2409d@aptechsite.net', 'Hassan 2409d'); // add a recipt's mail

        // set mail format to html
        $mail->isHTML(true);
        $mail->Subject = "Shirazi Solution";
        $mail->Body = "<b>Notice</b> : <h1>hi sir</h1>";
        $mail->AltBody = "hello";

        $mail->send();

        // ✅ Redirect after sending mail to prevent resending on refresh
        header("Location: " . $_SERVER['PHP_SELF'] . "?sent=1");
        exit;

    } catch (Exception $e) {
        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
}
?> 
</body>
</html>
