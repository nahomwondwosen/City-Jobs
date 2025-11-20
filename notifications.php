<?php
session_start();
require_once "config.php";

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'] ?? '';
$message = '';

// --- Determine Dashboard URL for Back Button ---
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

// --- Mark all as read if requested ---
if(isset($_GET['mark_read'])){
    $stmt = $conn->prepare("UPDATE notifications SET is_read=1 WHERE user_id=?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();
    $message = "✅ All notifications marked as read.";
}

// --- Fetch latest 50 notifications ---
$stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 50");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$notifications = $stmt->get_result();
$stmt->close();

// --- Count unread notifications ---
$unread_count = $conn->query("SELECT COUNT(*) as cnt FROM notifications WHERE user_id=$user_id AND is_read=0")->fetch_assoc()['cnt'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Notifications - City Jobs</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { background:#121212; color:#fff; }
header { background:#1f1f1f; padding:15px; display:flex; justify-content:space-between; align-items:center; }
header a { color:#fff; text-decoration:none; margin-left:15px; }
header a:hover { color:#0af; }
.card { background:#1e1e1e; border-radius:12px; }
.list-group-item { background:#1b1b1b; color:#fff; border:none; }
.list-group-item:hover { background:#2a2a2a; }
.badge-new { background:#0af; color:#000; font-size:0.8rem; }
</style>
</head>
<body>

<header>
    <div>🔔 Notifications (<?= $unread_count ?> New)</div>
    <div>
        <a href="<?= $dashboard_url ?>">⬅ Back to Dashboard</a>
        <a href="?mark_read=1" class="ms-2">Mark All Read</a>
        <a href="logout.php" class="ms-2" style="color:#f55;">Logout</a>
    </div>
</header>

<div class="container py-4">

    <?php if($message): ?>
    <div class="alert alert-info"><?= $message ?></div>
    <?php endif; ?>

    <div class="card p-3">
        <h5 class="mb-3">Your Notifications</h5>
        <?php if($notifications->num_rows>0): ?>
            <ul class="list-group list-group-flush">
            <?php while($n = $notifications->fetch_assoc()): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                        <?= htmlspecialchars($n['message']) ?>
                        <br><small class="text-secondary"><?= $n['created_at'] ?></small>
                    </div>
                    <?php if(!$n['is_read']): ?>
                        <span class="badge badge-new">New</span>
                    <?php endif; ?>
                </li>
            <?php endwhile; ?>
            </ul>
        <?php else: ?>
            <p class="text-secondary text-center mt-3">No notifications yet.</p>
        <?php endif; ?>
    </div>

</div>
</body>
</html>
