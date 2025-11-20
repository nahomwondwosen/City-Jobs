<?php
session_start();
require_once "config.php";

$message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    
    // Check if email exists
    $stmt = $conn->prepare("SELECT id, name FROM users WHERE contact=? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $res = $stmt->get_result();
    $user = $res->fetch_assoc();

    if($user){
        // Generate token
        $token = bin2hex(random_bytes(50));
        $expire = date("Y-m-d H:i:s", strtotime("+1 hour"));
        
        // Save token in DB
        $stmt2 = $conn->prepare("UPDATE users SET reset_token=?, reset_token_expire=? WHERE id=?");
        $stmt2->bind_param("ssi", $token, $expire, $user['id']);
        $stmt2->execute();

        // Send reset link (use mail() or a mail library)
        $reset_link = "http://yourdomain.com/reset_password.php?token=".$token;
        $subject = "City Jobs Password Reset";
        $body = "Hi ".$user['name'].",<br>Click this link to reset your password: <a href='$reset_link'>$reset_link</a><br>Link expires in 1 hour.";
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: no-reply@cityjobs.com" . "\r\n";
        
        if(mail($email,$subject,$body,$headers)){
            $message = "A password reset link has been sent to your email.";
        } else {
            $message = "Failed to send email. Please try again.";
        }

    } else {
        $message = "Email/contact not found.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Forgot Password - City Jobs</title>
<style>
body{font-family:Arial;background:#121212;color:#fff;display:flex;justify-content:center;align-items:center;height:100vh;}
form{background:#1e1e1e;padding:30px;border-radius:10px;width:350px;}
input{width:100%;padding:10px;margin:10px 0;border:none;border-radius:5px;}
button{width:100%;padding:10px;background:#0af;border:none;border-radius:5px;color:#fff;font-weight:bold;}
.message{color:#0f0;text-align:center;margin-bottom:10px;}
</style>
</head>
<body>
<form method="post">
    <h2>Forgot Password</h2>
    <?php if($message): ?><p class="message"><?php echo $message; ?></p><?php endif; ?>
    <input type="text" name="email" placeholder="Enter your email/contact" required>
    <button type="submit">Send Reset Link</button>
</form>
</body>
</html>
