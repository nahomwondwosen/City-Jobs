<?php
session_start();
require_once "config.php";

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT name, email, role, contact, profile_image, created_at FROM users WHERE id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Profile - City Jobs</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body{background:#050505;color:#fff;}
.card{background:#111;border:none;border-radius:12px;}
img.profile-pic{width:120px;height:120px;border-radius:50%;object-fit:cover;border:3px solid #00bfff;}
</style>
</head>
<body>
<div class="container py-5">
  <div class="card p-4 text-center mx-auto" style="max-width:500px;">
    <img src="uploads/<?=htmlspecialchars($user['profile_image'] ?? 'default.png');?>" class="profile-pic mb-3" alt="Profile">
    <h4><?=htmlspecialchars($user['name']);?></h4>
    <p class="text-secondary"><?=htmlspecialchars($user['email']);?></p>
    <span class="badge bg-info text-dark"><?=ucfirst($user['role']);?></span>
    <p class="mt-3">📞 <?=htmlspecialchars($user['contact']);?></p>
    <p><small>Joined on <?=htmlspecialchars($user['created_at']);?></small></p>
    <a href="edit_profile.php" class="btn btn-outline-info mt-3">Edit Profile</a>
  </div>
</div>
</body>
</html>
