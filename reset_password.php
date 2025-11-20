<?php
session_start();
require_once "config.php";

$message = "";

// Check token
if(isset($_GET['token'])){
    $token = $_GET['token'];

    $stmt = $conn->prepare("SELECT id FROM users WHERE reset_token=? AND reset_token_expire > NOW() LIMIT 1");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $res = $stmt->get_result();
    $user = $res->fetch_assoc();

    if(!$user){
        die("Invalid or expired token.");
    }

    // Handle new password
    if($_SERVER['REQUEST_METHOD']=='POST'){
        $pass1 = $_POST['password'];
        $pass2 = $_POST['confirm_password'];

        if($pass1 !== $pass2){
            $message = "Passwords do not match!";
        } else {
            $pass_hash = password_hash($pass1, PASSWORD_DEFAULT);
            $stmt2 = $conn->prepare("UPDATE users SET password_hash=?, reset_token=NULL, reset_token_expire=NULL WHERE id=?");
            $stmt2->bind_param("si", $pass_hash, $user['id']);
            $stmt2->execute();
            $message = "Password updated successfully! <a href='index.php'>Login</a>";
        }
    }

}else{
    die("Token not provided.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Reset Password - City Jobs</title>
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
    <h2>Reset Password</h2>
    <?php if($message): ?><p class="message"><?php echo $message; ?></p><?php endif; ?>
    <input type="password" name="password" placeholder="New Password" required>
    <input type="password" name="confirm_password" placeholder="Confirm Password" required>
    <button type="submit">Update Password</button>
</form>
</body>
</html>
