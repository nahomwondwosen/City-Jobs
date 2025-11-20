<?php
session_start();
require_once "config.php";

// Check if user is logged in
if(!isset($_SESSION['user_id'])){
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch user details
$stmt = $conn->prepare("SELECT id, name, email, role, profile_pic, created_at FROM users WHERE id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$user_name  = $user['name'] ?? "User";
$user_email = $user['email'] ?? "No email";
$user_role  = ucfirst($user['role'] ?? "Member");
$user_pic   = $user['profile_pic'] ?? "default.png";
$user_experience = $user['experience'] ?? "Not specified";
$user_about      = $user['about'] ?? "No description yet";
$user_date  = $user['created_at'] ?? "";

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Account - City Jobs</title>
<style>
body { font-family: Arial, sans-serif; background:#121212; color:#fff; margin:0; }
header { background:#1f1f1f; padding:15px 25px; display:flex; justify-content:space-between; align-items:center; }
header .logo { font-size:20px; font-weight:bold; }
.container { max-width:800px; margin:40px auto; background:#1e1e1e; padding:20px; border-radius:8px; }
.profile-pic { width:100px; height:100px; border-radius:50%; object-fit:cover; }
h2 { margin-top:0; }
.info p { margin:8px 0; }
a.button { background:#0af; color:#fff; padding:10px 15px; border-radius:5px; text-decoration:none; margin-right:10px; }
a.button:hover { background:#08c; }
</style>
</head>
<body>

<header>
  <div class="logo">City Jobs</div>
  <div>
    <a href="edit_profile.php" class="button">Edit</a>
    <a href="logout.php" class="button" style="background:#f55;">Logout</a>
  </div>
</header>

<div class="container">
  <center>
    <img src="uploads/<?php echo htmlspecialchars($user_pic); ?>" alt="Profile Picture" class="profile-pic"><br>
    <h2><?php echo htmlspecialchars($user_name); ?></h2>
    <p><strong>Role:</strong> <?php echo htmlspecialchars($user_role); ?></p>
  </center>
  <div class="info">
    <p><strong>Email:</strong> <?php echo htmlspecialchars($user_email); ?></p>
    <p><strong>Member Since:</strong> <?php echo htmlspecialchars($user_date); ?></p>
    <p><strong>Experience:</strong> <?php echo htmlspecialchars($user_experience); ?></p>
    <p><strong>About:</strong> <?php echo nl2br(htmlspecialchars($user_about)); ?></p>
  </div>
</div>

</body>
</html>

