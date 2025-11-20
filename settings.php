<?php
session_start();
require_once "config.php";

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit;
}

$user_id   = $_SESSION['user_id'];
$user_role = $_SESSION['role'];
$user_name = $_SESSION['name'] ?? '';
$message   = '';

// Determine dashboard URL
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

// --- Fetch Current User Info ---
$stmt = $conn->prepare("SELECT name, email, contact, role, created_at FROM users WHERE id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// --- Handle Profile Update ---
if(isset($_POST['update_profile'])){
    $name    = trim($_POST['name']);
    $email   = trim($_POST['email']);
    $contact = trim($_POST['contact']);

    if(empty($name) || empty($email) || empty($contact)){
        $message = "⚠️ All fields are required.";
    } else {
        $stmt = $conn->prepare("UPDATE users SET name=?, email=?, contact=? WHERE id=?");
        $stmt->bind_param("sssi", $name, $email, $contact, $user_id);
        if($stmt->execute()){
            $_SESSION['name'] = $name;
            $message = "✅ Profile updated successfully.";
        } else {
            $message = "❌ Failed to update profile.";
        }
        $stmt->close();
    }
}

// --- Handle Password Change ---
if(isset($_POST['change_password'])){
    $current = $_POST['current_password'];
    $new     = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];

    if(empty($current) || empty($new) || empty($confirm)){
        $message = "⚠️ All password fields are required.";
    } elseif($new !== $confirm){
        $message = "⚠️ New passwords do not match.";
    } else {
        $stmt = $conn->prepare("SELECT password_hash FROM users WHERE id=?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->bind_result($hashed);
        $stmt->fetch();
        $stmt->close();

        if(!password_verify($current, $hashed)){
            $message = "❌ Current password is incorrect.";
        } else {
            $new_hash = password_hash($new, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password_hash=? WHERE id=?");
            $stmt->bind_param("si", $new_hash, $user_id);
            if($stmt->execute()){
                $message = "✅ Password changed successfully.";
            } else {
                $message = "❌ Failed to change password.";
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Account Settings - City Jobs</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body {
    background: #121212;
    color: #fff;
}
header {
    background: #1f1f1f;
    padding: 15px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
header a {
    color: #fff;
    margin-left: 10px;
    text-decoration: none;
}
header a:hover {
    color: #0af;
}
.form-label, label {
    color: #fff;
}
.form-control {
    background: #1b1b1b;
    color: #fff;
    border: 1px solid #333;
}
.form-control::placeholder {
    color: #ccc;
}
.btn-primary {
    background: #0af;
    border: none;
    color: #fff;
}
.btn-primary:hover {
    background: #08c;
}
.btn-secondary {
    background: #555;
    border: none;
    color: #fff;
}
.btn-secondary:hover {
    background: #777;
    color: #fff;
}
.btn-danger {
    background: #d33;
    border: none;
}
.btn-danger:hover {
    background: #b22;
}
.card {
    background: #1e1e1e;
    border: none;
    color: #fff;
}
.alert {
    color: #000;
}
</style>
</head>
<body>
<header>
    <div>⚙️ Account Settings</div>
    <div>
        <a href="<?= $dashboard_url ?>" class="btn btn-secondary btn-sm me-2">⬅ Back to Dashboard</a>
        <a href="logout.php" class="btn btn-danger btn-sm">Logout</a>
    </div>
</header>

<div class="container mt-4">
    <?php if($message): ?>
        <div class="alert alert-info"><?=$message?></div>
    <?php endif; ?>

    <!-- Profile Info -->
    <div class="card p-4 mb-4">
        <h4>👤 Profile Information</h4>
        <form method="POST">
            <div class="row mt-3">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" class="form-control" value="<?=htmlspecialchars($user['name'])?>" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="<?=htmlspecialchars($user['email'])?>" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Contact</label>
                    <input type="text" name="contact" class="form-control" value="<?=htmlspecialchars($user['contact'])?>" required>
                </div>
            </div>
            <button type="submit" name="update_profile" class="btn btn-primary mt-2">Update Profile</button>
        </form>
    </div>

    <!-- Password Change -->
    <div class="card p-4 mb-4">
        <h4>🔒 Change Password</h4>
        <form method="POST">
            <div class="row mt-3">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Current Password</label>
                    <input type="password" name="current_password" class="form-control" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">New Password</label>
                    <input type="password" name="new_password" class="form-control" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Confirm New Password</label>
                    <input type="password" name="confirm_password" class="form-control" required>
                </div>
            </div>
            <button type="submit" name="change_password" class="btn btn-primary mt-2">Change Password</button>
        </form>
    </div>

    <!-- Account Info -->
    <div class="card p-4">
        <h4>📄 Account Details</h4>
        <p><strong>Role:</strong> <?=htmlspecialchars(ucfirst($user['role']))?></p>
        <p><strong>Joined:</strong> <?=htmlspecialchars($user['created_at'])?></p>
        <p class="text-light">You can manage your personal information and security settings here.</p>
    </div>
</div>
</body>
</html>
