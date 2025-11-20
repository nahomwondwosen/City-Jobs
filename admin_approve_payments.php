<?php
session_start();
require_once "config.php";

if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin'){
    header("Location: login.php");
    exit;
}

// Approve payment
if(isset($_GET['approve'])){
    $payment_id = intval($_GET['approve']);

    // Get payment info
    $stmt = $conn->prepare("SELECT * FROM payments WHERE id=? AND status='pending'");
    $stmt->bind_param("i", $payment_id);
    $stmt->execute();
    $payment = $stmt->get_result()->fetch_assoc();

    if($payment){
        // Get receiver
        $receiver_stmt = $conn->prepare("SELECT id FROM users WHERE name=? LIMIT 1");
        $receiver_stmt->bind_param("s", $payment['receiver_name']);
        $receiver_stmt->execute();
        $receiver = $receiver_stmt->get_result()->fetch_assoc();

        if($receiver){
            // Deduct from sender
            $deduct_stmt = $conn->prepare("
                INSERT INTO payments (user_id, sender_name, receiver_name, receiver_account, amount, status, payment_method)
                VALUES (?, ?, ?, ?, ?, 'approved', ?)
            ");
            $neg_amount = -$payment['amount'];
            $deduct_stmt->bind_param("isssds", $payment['user_id'], $payment['sender_name'], $payment['receiver_name'], $payment['receiver_account'], $neg_amount, $payment['payment_method']);
            $deduct_stmt->execute();

            // Add to receiver
            $add_stmt = $conn->prepare("
                INSERT INTO payments (user_id, sender_name, receiver_name, receiver_account, amount, status, payment_method)
                VALUES (?, ?, ?, ?, ?, 'approved', ?)
            ");
            $add_stmt->bind_param("isssds", $receiver['id'], $payment['sender_name'], $payment['receiver_name'], $payment['receiver_account'], $payment['amount'], $payment['payment_method']);
            $add_stmt->execute();

            // Mark original as approved
            $conn->query("UPDATE payments SET status='approved' WHERE id=$payment_id");
            $msg = "✅ Payment approved successfully!";
        }
    }
}

// Fetch pending payments
$pending = $conn->query("SELECT p.*, u.name AS payer_name 
                         FROM payments p 
                         JOIN users u ON p.user_id=u.id 
                         WHERE p.status='pending' 
                         ORDER BY p.created_at DESC");
?>
<!DOCTYPE html>
<html>
<head>
<title>Admin Approve Payments</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body{background:#121212;color:#fff;}
.table{background:#1e1e1e;}
a.btn{margin-right:5px;}
</style>
</head>
<body class="p-4">
<h3>🛠 Pending Payments</h3>

<?php if(isset($msg)): ?>
<div class="alert alert-success"><?=$msg?></div>
<?php endif; ?>

<table class="table table-hover table-bordered text-white mt-3">
    <thead>
        <tr>
            <th>ID</th>
            <th>Payer</th>
            <th>Receiver</th>
            <th>Amount</th>
            <th>Method</th>
            <th>Status</th>
            <th>Date</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
    <?php while($p=$pending->fetch_assoc()): ?>
    <tr>
        <td>#<?=$p['id']?></td>
        <td><?=$p['payer_name']?></td>
        <td><?=$p['receiver_name']?></td>
        <td>$<?=number_format($p['amount'],2)?></td>
        <td><?=$p['payment_method']?></td>
        <td><span class="text-warning">Pending</span></td>
        <td><?=$p['created_at']?></td>
        <td>
            <a href="?approve=<?=$p['id']?>" class="btn btn-success btn-sm">Approve</a>
        </td>
    </tr>
    <?php endwhile; ?>
    </tbody>
</table>
</body>
</html>
