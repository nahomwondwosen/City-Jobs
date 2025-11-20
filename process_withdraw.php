<?php
session_start();
require_once "config.php";

// Check admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$id = intval($_GET['id']);
$action = $_GET['action'] ?? '';

if ($action === 'approve' || $action === 'reject') {
    $status = $action === 'approve' ? 'approved' : 'rejected';

    // Get user_id and amount
    $stmt = $conn->prepare("SELECT user_id, amount FROM withdraw_requests WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($user_id, $amount);
    $stmt->fetch();
    $stmt->close();

    if ($status === 'approved') {
        // Deduct amount from user balance
        $stmt = $conn->prepare("UPDATE users SET balance = balance - ? WHERE id=?");
        $stmt->bind_param("di", $amount, $user_id);
        $stmt->execute();
        $stmt->close();
    }

    // Update request status
    $stmt = $conn->prepare("UPDATE withdraw_requests SET status=? WHERE id=?");
    $stmt->bind_param("si", $status, $id);
    $stmt->execute();
    $stmt->close();

    // Insert notification
    $message = "Your withdrawal request of $amount has been $status.";
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
    $stmt->bind_param("is", $user_id, $message);
    $stmt->execute();
    $stmt->close();
}

header("Location: admin_withdraw_requests.php");
exit;
?>
