<?php
session_start();
require_once "config.php";

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];
$user_name = $_SESSION['name'] ?? '';
$message = '';

// Determine dashboard URL for Back button
$dashboard_url = 'dashboard.php';
switch($user_role){
    case 'freelancer':
        $dashboard_url = 'freelancer_dashboard.php';
        break;
    case 'employer':
        $dashboard_url = 'employer_dashboard.php';
        break;
    case 'admin':
        $dashboard_url = 'dashboard.php';
        break;
}

// --- Handle Admin Approve/Reject Withdrawals ---
if($user_role==='admin'){
    if(isset($_GET['approve_withdraw'])){
        $wid = intval($_GET['approve_withdraw']);
        // Get withdraw info
        $stmt = $conn->prepare("SELECT user_id, amount FROM withdraw_requests WHERE id=?");
        $stmt->bind_param("i", $wid);
        $stmt->execute();
        $stmt->bind_result($w_user_id, $w_amount);
        $stmt->fetch();
        $stmt->close();

        // Deduct from user balance
        $stmt = $conn->prepare("INSERT INTO payments (user_id, sender_name, receiver_name, receiver_account, amount, status, payment_method) VALUES (?, 'System', 'Withdrawal', 'N/A', ?, 'approved', 'Withdrawal')");
        $neg_amount = -$w_amount;
        $stmt->bind_param("id", $w_user_id, $neg_amount);
        $stmt->execute();
        $stmt->close();

        // Update request status
        $stmt = $conn->prepare("UPDATE withdraw_requests SET status='approved' WHERE id=?");
        $stmt->bind_param("i", $wid);
        $stmt->execute();
        $stmt->close();

        $message = "✅ Withdrawal request approved.";
        header("Location: payment_dashboard.php");
        exit;
    }

    if(isset($_GET['reject_withdraw'])){
        $wid = intval($_GET['reject_withdraw']);
        $stmt = $conn->prepare("UPDATE withdraw_requests SET status='rejected' WHERE id=?");
        $stmt->bind_param("i", $wid);
        $stmt->execute();
        $stmt->close();
        $message = "❌ Withdrawal request rejected.";
        header("Location: payment_dashboard.php");
        exit;
    }
}

// --- Handle Payment / Withdraw Submission for normal users ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['make_payment'])) {
        $receiver_name = trim($_POST['receiver_name']);
        $receiver_account = trim($_POST['receiver_account']);
        $amount = floatval($_POST['amount']);
        $method = $_POST['payment_method'];

        $receiver_stmt = $conn->prepare("SELECT id, name FROM users WHERE name=? OR email=? OR contact=? LIMIT 1");
        $receiver_stmt->bind_param("sss", $receiver_name, $receiver_name, $receiver_account);
        $receiver_stmt->execute();
        $receiver = $receiver_stmt->get_result()->fetch_assoc();

        if(!$receiver){
            $message = "❌ Receiver not found.";
        } elseif ($receiver['id'] == $user_id) {
            $message = "❌ You cannot send money to yourself.";
        } else {
            $bal_stmt = $conn->prepare("SELECT SUM(amount) AS total FROM payments WHERE user_id=? AND status='approved'");
            $bal_stmt->bind_param("i", $user_id);
            $bal_stmt->execute();
            $sender_balance = $bal_stmt->get_result()->fetch_assoc()['total'] ?? 0;

            if($sender_balance < $amount){
                $message = "⚠️ Insufficient balance. You only have $" . number_format($sender_balance,2);
            } else {
                // Deduct from sender
                $deduct_stmt = $conn->prepare("INSERT INTO payments (user_id, sender_name, receiver_name, receiver_account, amount, status, payment_method) VALUES (?, ?, ?, ?, ?, 'approved', ?)");
                $neg_amount = -$amount;
                $deduct_stmt->bind_param("isssds", $user_id, $user_name, $receiver['name'], $receiver_account, $neg_amount, $method);
                $deduct_stmt->execute();

                // Add to receiver
                $add_stmt = $conn->prepare("INSERT INTO payments (user_id, sender_name, receiver_name, receiver_account, amount, status, payment_method) VALUES (?, ?, ?, ?, ?, 'approved', ?)");
                $add_stmt->bind_param("isssds", $receiver['id'], $user_name, $receiver['name'], $receiver_account, $amount, $method);
                $add_stmt->execute();

                $message = "✅ Payment of $" . number_format($amount,2) . " sent to " . htmlspecialchars($receiver['name']) . " successfully!";
            }
        }
    }

    if (isset($_POST['withdraw_money'])) {
        $withdraw_amount = floatval($_POST['withdraw_amount']);
        $bal_stmt = $conn->prepare("SELECT SUM(amount) AS total FROM payments WHERE user_id=? AND status='approved'");
        $bal_stmt->bind_param("i", $user_id);
        $bal_stmt->execute();
        $balance = $bal_stmt->get_result()->fetch_assoc()['total'] ?? 0;

        if ($withdraw_amount <= 0) {
            $message = "❌ Enter a valid amount.";
        } elseif ($withdraw_amount > $balance) {
            $message = "⚠️ Insufficient balance. You have $" . number_format($balance,2);
        } else {
            $stmt = $conn->prepare("INSERT INTO withdraw_requests (user_id, amount) VALUES (?, ?)");
            $stmt->bind_param("id", $user_id, $withdraw_amount);
            $stmt->execute();
            $message = "✅ Withdrawal request of $" . number_format($withdraw_amount,2) . " submitted. Admin will review it.";
        }
    }
}

// --- Fetch Transactions ---
if($user_role === 'admin'){
    $stmt = $conn->prepare("SELECT p.*, u.name AS payer_name FROM payments p JOIN users u ON p.user_id=u.id ORDER BY p.created_at DESC");
} else {
    $stmt = $conn->prepare("SELECT p.*, u.name AS payer_name FROM payments p JOIN users u ON p.user_id=u.id WHERE p.user_id=? ORDER BY p.created_at DESC");
    $stmt->bind_param("i", $user_id);
}
$stmt->execute();
$transactions = $stmt->get_result();

// --- Calculate Balance ---
$bal_stmt = $conn->prepare("SELECT SUM(amount) AS total FROM payments WHERE user_id=? AND status='approved'");
$bal_stmt->bind_param("i", $user_id);
$bal_stmt->execute();
$balance = $bal_stmt->get_result()->fetch_assoc()['total'] ?? 0;

// --- Fetch Withdraw Requests ---
$withdraw_stmt = $conn->prepare($user_role==='admin' ? "SELECT w.*, u.name FROM withdraw_requests w JOIN users u ON w.user_id=u.id ORDER BY w.created_at DESC" : "SELECT * FROM withdraw_requests WHERE user_id=? ORDER BY created_at DESC");
if($user_role!=='admin') $withdraw_stmt->bind_param("i",$user_id);
$withdraw_stmt->execute();
$withdraw_requests = $withdraw_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Payment Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body{background:#121212;color:#fff;}
header{background:#1f1f1f;padding:15px;display:flex;justify-content:space-between;}
header a{color:#fff;margin-left:15px;text-decoration:none;}
header a:hover{color:#0af;}
.table{background:#1e1e1e;}
.form-control, .form-select{background:#1b1b1b;color:#fff;border:1px solid #333;}
.btn-primary{background:#0af;border:none;}
.btn-primary:hover{background:#08c;}
.btn-secondary{background:#555;border:none;color:#fff;}
.btn-secondary:hover{background:#777;color:#fff;}
</style>
</head>
<body>
<header>
    <div>💳 City Jobs Payment </div>
    <div>
        <a href="<?= $dashboard_url ?>" class="btn btn-secondary btn-sm me-2">⬅ Back to Dashboard</a>
        <?php if($user_role==='admin'): ?>
            <a href="admin_add_money.php" class="btn btn-primary btn-sm me-2">Add Money</a>
        <?php endif; ?>
        <a href="logout.php" class="btn btn-danger btn-sm">Logout</a>
    </div>
</header>

<div class="container mt-4">
<div class="card bg-dark text-white p-3 mb-3">
    <h4>Your Balance: ETB <?=number_format($balance,2)?></h4>
</div>

<?php if($message): ?>
<div class="alert alert-info"><?=$message?></div>
<?php endif; ?>

<!-- Back Button -->

<?php if($user_role!=='admin'): ?>
<!-- Make Payment Form -->
<div class="card bg-dark text-white p-3 mb-4">
<h5>🧾 Make a Payment</h5>
<form method="POST">
<div class="row">
<div class="col-md-4 mb-3">
<label>Receiver Name / Email</label>
<input type="text" name="receiver_name" class="form-control" required>
</div>
<div class="col-md-4 mb-3">
<label>Receiver Account</label>
<input type="text" name="receiver_account" class="form-control" required>
</div>
<div class="col-md-2 mb-3">
<label>Amount (ETB)</label>
<input type="number" step="0.01" name="amount" class="form-control" required>
</div>
<div class="col-md-2 mb-3">
<label>Method</label>
<select name="payment_method" class="form-select" required>
<option value="">Select</option>
<option value="Chapa">Chapa</option>
<option value="Telebirr">Telebirr</option>
<option value="Cash">Cash</option>
</select>
</div>
</div>
<button type="submit" name="make_payment" class="btn btn-primary mt-2">Send Payment</button>
</form>
</div>

<!-- Withdraw Form -->
<div class="card bg-dark text-white p-3 mb-4">
<h5>💸 Withdraw Money</h5>
<form method="POST">
<div class="row">
<div class="col-md-4 mb-3">
<label>Amount (ETB)</label>
<input type="number" step="0.01" name="withdraw_amount" class="form-control" required>
</div>
</div>
<button type="submit" name="withdraw_money" class="btn btn-primary mt-2">Request Withdraw</button>
</form>
</div>
<?php endif; ?>

<!-- Withdrawal Requests Table -->
<div class="card bg-dark text-white p-3 mb-4">
<h5>📄 Withdrawal Requests</h5>
<table class="table table-hover table-bordered text-white">
<thead>
<tr>
<th>ID</th>
<?php if($user_role==='admin') echo "<th>User</th>"; ?>
<th>Amount</th>
<th>Status</th>
<th>Requested At</th>
<?php if($user_role==='admin') echo "<th>Action</th>"; ?>
</tr>
</thead>
<tbody>
<?php if($withdraw_requests->num_rows>0): ?>
<?php while($w=$withdraw_requests->fetch_assoc()): ?>
<tr>
<td>#<?=$w['id']?></td>
<?php if($user_role==='admin') echo "<td>".htmlspecialchars($w['name'])."</td>"; ?>
<td>$<?=number_format($w['amount'],2)?></td>
<td>
<?php if($w['status']=='approved'): ?><span class="text-success fw-bold">Approved</span>
<?php elseif($w['status']=='pending'): ?><span class="text-warning fw-bold">Pending</span>
<?php else: ?><span class="text-danger fw-bold">Rejected</span>
<?php endif; ?>
</td>
<td><?=$w['created_at']?></td>
<?php if($user_role==='admin'): ?>
<td>
<?php if($w['status']=='pending'): ?>
<a href="?approve_withdraw=<?=$w['id']?>" class="btn btn-success btn-sm">Approve</a>
<a href="?reject_withdraw=<?=$w['id']?>" class="btn btn-danger btn-sm">Reject</a>
<?php else: ?>-
<?php endif; ?>
</td>
<?php endif; ?>
</tr>
<?php endwhile; ?>
<?php else: ?>
<tr><td colspan="<?= $user_role==='admin' ? 6 : 4 ?>" class="text-center text-secondary">No withdrawal requests yet.</td></tr>
<?php endif; ?>
</tbody>
</table>
</div>

<!-- Transactions Table -->
<div class="card bg-dark text-white p-3 mb-4">
<h5>💰 Transactions</h5>
<table class="table table-hover table-bordered text-white">
<thead>
<tr>
<th>ID</th>
<th>Payer</th>
<th>Sender</th>
<th>Receiver</th>
<th>Account</th>
<th>Amount</th>
<th>Status</th>
<th>Method</th>
<th>Date</th>
</tr>
</thead>
<tbody>
<?php if($transactions->num_rows>0): ?>
<?php while($t=$transactions->fetch_assoc()): ?>
<tr>
<td>#<?=$t['id']?></td>
<td><?=htmlspecialchars($t['payer_name'])?></td>
<td><?=htmlspecialchars($t['sender_name'] ?? '-')?></td>
<td><?=htmlspecialchars($t['receiver_name'] ?? '-')?></td>
<td><?=htmlspecialchars($t['receiver_account'] ?? '-')?></td>
<td>
<?php if($t['amount'] < 0): ?>
<span class="text-danger">-ETB <?=number_format(abs($t['amount']),2)?></span>
<?php else: ?>
<span class="text-success">+ETB <?=number_format($t['amount'],2)?></span>
<?php endif; ?>
</td>
<td>
<?php if($t['status']=='approved'): ?><span class="text-success fw-bold">Approved</span>
<?php elseif($t['status']=='pending'): ?><span class="text-warning fw-bold">Pending</span>
<?php else: ?><span class="text-danger fw-bold">Failed</span>
<?php endif; ?>
</td>
<td><?=htmlspecialchars($t['payment_method'] ?? '-')?></td>
<td><?=$t['created_at']?></td>
</tr>
<?php endwhile; ?>
<?php else: ?>
<tr><td colspan="9" class="text-center text-secondary">No transactions yet.</td></tr>
<?php endif; ?>
</tbody>
</table>
</div>
</div>
</body>
</html>
