<?php
session_start();
require_once "config.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = "";

// Get user balance
$stmt = $conn->prepare("SELECT balance FROM users WHERE id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($balance);
$stmt->fetch();
$stmt->close();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = floatval($_POST['amount']);

    if ($amount <= 0) {
        $message = "Please enter a valid amount.";
    } elseif ($amount > $balance) {
        $message = "Insufficient balance. Your current balance is $balance.";
    } else {
        $stmt = $conn->prepare("INSERT INTO withdraw_requests (user_id, amount) VALUES (?, ?)");
        $stmt->bind_param("id", $user_id, $amount);

        if ($stmt->execute()) {
            $message = "Withdrawal request submitted successfully. Admin will review it.";
        } else {
            $message = "Error submitting request. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Withdraw Money</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h2>Withdraw Money</h2>
    <p>Your current balance: <strong><?php echo number_format($balance, 2); ?></strong></p>
    <?php if($message): ?>
        <div class="alert alert-info"><?php echo $message; ?></div>
    <?php endif; ?>
    <form method="POST">
        <div class="mb-3">
            <label for="amount" class="form-label">Amount to Withdraw</label>
            <input type="number" step="0.01" name="amount" id="amount" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary">Submit Request</button>
    </form>
</div>
</body>
</html>
