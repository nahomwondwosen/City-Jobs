<?php
session_start();
require_once "config.php";

// ✅ Check login
if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit;
}

// ✅ Collect POST data
$user_id = $_SESSION['user_id'];  // sender (employer)
$receiver_id = intval($_POST['receiver_id']); // receiver (freelancer)
$amount = floatval($_POST['amount']);
$method = $_POST['method_id'] ?? 'Chapa';
$reference = "CJ-" . time() . "-" . $user_id;

// ✅ Insert payment record
$stmt = $conn->prepare("
    INSERT INTO payments (user_id, amount, payment_method, status, reference)
    VALUES (?, ?, ?, 'pending', ?)
");
$stmt->bind_param("idss", $user_id, $amount, $method, $reference);

if ($stmt->execute()) {

    // ✅ Notify admin (optional)
    $admin_notify = $conn->prepare("
        INSERT INTO notifications (user_id, message, created_at)
        VALUES (1, ?, NOW())
    ");
    $msg = "Employer #$user_id paid $amount ETB to Freelancer #$receiver_id (pending approval)";
    @$admin_notify->bind_param("s", $msg);
    @$admin_notify->execute();

    echo "
    <html><head><title>Payment Success</title>
    <style>
    body { background:#121212; color:#fff; text-align:center; font-family:Arial; padding-top:100px; }
    .box { background:#1e1e1e; padding:25px; border-radius:10px; display:inline-block; }
    a { color:#0af; text-decoration:none; }
    </style></head><body>
    <div class='box'>
      <h2>✅ Payment Recorded!</h2>
      <p>Transaction Ref: <b>$reference</b></p>
      <p>Amount: <b>$amount ETB</b></p>
      <p>Status: <b>Pending</b></p>
      <a href='account.php'>← Back to Account</a>
    </div>
    </body></html>";
} else {
    echo "❌ Error: " . $conn->error;
}
?>
