<?php
session_start();
require_once "config.php";

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Get current user balance
$stmt = $conn->prepare("SELECT name, balance FROM users WHERE id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if($_SERVER['REQUEST_METHOD']==='POST'){
    $amount = floatval($_POST['amount']);
    $sender_name = trim($_POST['sender_name']);
    $receiver_name = trim($_POST['receiver_name']);
    $receiver_account = trim($_POST['receiver_account']);
    $payment_method = trim($_POST['payment_method']);
    $status = 'pending';

    // Validate amount
    if($amount <= 0){
        echo "<script>alert('Invalid amount');window.location='make_payment.php';</script>";
        exit;
    }

    // Check if user has enough balance
    if($amount > $user['balance']){
        echo "<script>alert('Insufficient balance');window.location='make_payment.php';</script>";
        exit;
    }

    // Deduct balance from payer immediately
    $stmt2 = $conn->prepare("UPDATE users SET balance = balance - ? WHERE id=?");
    $stmt2->bind_param("di", $amount, $user_id);
    $stmt2->execute();

    // Insert payment request (pending)
    $stmt3 = $conn->prepare("INSERT INTO payments 
        (user_id, sender_name, receiver_name, receiver_account, amount, status, payment_method, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt3->bind_param("isssdss", $user_id, $sender_name, $receiver_name, $receiver_account, $amount, $status, $payment_method);
    $stmt3->execute();

    echo "<script>alert('Payment submitted and deducted from your balance!');window.location='payment_dashboard.php';</script>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Make Payment</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body{background:#121212;color:#fff;font-family:Arial,sans-serif;}
.container{max-width:480px;margin-top:50px;}
input, button{width:100%;padding:10px;margin-bottom:12px;border-radius:5px;border:none;}
button{background:#0af;color:#000;font-weight:bold;}
button:hover{background:#08c;}
</style>
</head>
<body>
<div class="container">
<h2>💸 Make Payment</h2>
<p>Current Balance: $<?=number_format($user['balance'],2)?></p>
<form method="POST">
<input type="text" name="sender_name" placeholder="Your Name" required>
<input type="text" name="receiver_name" placeholder="Receiver Name" required>
<input type="text" name="receiver_account" placeholder="Receiver Account/Wallet" required>
<input type="number" step="0.01" name="amount" placeholder="Amount" required>
<input type="text" name="payment_method" placeholder="Payment Method" required>
<button type="submit">Submit Payment</button>
</form>
<a href="payment_dashboard.php" class="btn btn-info mt-2">← Back to Dashboard</a>
</div>
</body>
</html>
