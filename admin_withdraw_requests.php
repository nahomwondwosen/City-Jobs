<?php
session_start();
require_once "config.php";

// Check if admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Fetch withdrawal requests
$result = $conn->query("
    SELECT w.*, u.name, u.email 
    FROM withdraw_requests w 
    JOIN users u ON w.user_id = u.id
    ORDER BY w.created_at DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Withdrawal Requests</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h2>Withdrawal Requests</h2>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>User</th>
                <th>Email</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Requested At</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                    <td><?php echo $row['amount']; ?></td>
                    <td><?php echo $row['status']; ?></td>
                    <td><?php echo $row['created_at']; ?></td>
                    <td>
                        <?php if($row['status'] === 'pending'): ?>
                            <a href="process_withdraw.php?id=<?php echo $row['id']; ?>&action=approve" class="btn btn-success btn-sm">Approve</a>
                            <a href="process_withdraw.php?id=<?php echo $row['id']; ?>&action=reject" class="btn btn-danger btn-sm">Reject</a>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
</body>
</html>
