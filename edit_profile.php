<?php
session_start();
require_once "config.php";

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';

// --- Handle form submission ---
if($_SERVER['REQUEST_METHOD'] === 'POST'){

    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $contact = trim($_POST['contact']);

    // --- Handle file upload ---
    $profile_image = null;
    if(isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK){
        $fileTmpPath = $_FILES['profile_image']['tmp_name'];
        $fileName = $_FILES['profile_image']['name'];
        $fileSize = $_FILES['profile_image']['size'];
        $fileType = $_FILES['profile_image']['type'];
        $fileNameCmps = pathinfo($fileName);
        $fileExtension = strtolower($fileNameCmps['extension']);

        // Allowed extensions
        $allowedExts = ['jpg', 'jpeg', 'png', 'gif'];
        if(in_array($fileExtension, $allowedExts)){
            // Rename file to avoid conflicts
            $newFileName = $user_id . '_' . time() . '.' . $fileExtension;
            $uploadFileDir = 'uploads/';
            if(!is_dir($uploadFileDir)){
                mkdir($uploadFileDir, 0755, true);
            }
            $dest_path = $uploadFileDir . $newFileName;

            if(move_uploaded_file($fileTmpPath, $dest_path)){
                $profile_image = $newFileName;
            } else {
                $message = "❌ Error uploading the image.";
            }
        } else {
            $message = "❌ Invalid file type. Only JPG, PNG, GIF allowed.";
        }
    }

    // --- Update user info in DB ---
    if(empty($message)){
        if($profile_image){
            $stmt = $conn->prepare("UPDATE users SET name=?, email=?, contact=?, profile_image=? WHERE id=?");
            $stmt->bind_param("ssssi", $name, $email, $contact, $profile_image, $user_id);
        } else {
            $stmt = $conn->prepare("UPDATE users SET name=?, email=?, contact=? WHERE id=?");
            $stmt->bind_param("sssi", $name, $email, $contact, $user_id);
        }

        if($stmt->execute()){
            $message = "✅ Profile updated successfully!";
            $_SESSION['name'] = $name; // update session name
        } else {
            $message = "❌ Failed to update profile: " . $stmt->error;
        }
        $stmt->close();
    }
}
// Determine dashboard URL based on logged-in user role
$dashboard_url = 'dashboard.php'; // default
$user_role = $_SESSION['role'] ?? '';

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
    // Add more roles here if needed
}

// --- Fetch current user data ---
$stmt = $conn->prepare("SELECT name, email, contact, profile_image FROM users WHERE id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit Profile - City Jobs</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body{background:#050505;color:#fff;}
.card{background:#111;border:none;border-radius:12px;}
img.profile-pic{width:120px;height:120px;border-radius:50%;object-fit:cover;border:3px solid #00bfff;}
</style>
</head>
<body>
<div class="container py-5">
  <div class="card p-4 mx-auto" style="max-width:500px;">
    <h4 class="mb-3 text-center">Edit Profile</h4>

    <?php if($message): ?>
        <div class="alert alert-info"><?=$message?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="text-center mb-3">
            <img src="uploads/<?=htmlspecialchars($user['profile_image'] ?? 'default.png')?>" class="profile-pic mb-2" alt="Profile">
            <input type="file" name="profile_image" class="form-control form-control-sm mt-2">
        </div>
        <div class="mb-2">
            <label>Name</label>
            <input type="text" name="name" class="form-control" value="<?=htmlspecialchars($user['name'])?>" required>
        </div>
        <div class="mb-2">
            <label>Email</label>
            <input type="email" name="email" class="form-control" value="<?=htmlspecialchars($user['email'])?>" required>
        </div>
        <div class="mb-2">
            <label>Contact</label>
            <input type="text" name="contact" class="form-control" value="<?=htmlspecialchars($user['contact'])?>">
        </div>
        <button type="submit" class="btn btn-info w-100 mt-2">Update Profile</button>
        <br>
        <br>
        <div class="mb-3">
    <a href="<?= $dashboard_url ?>" class="btn btn-secondary w-100">⬅ Back to Dashboard</a>
</div>

    </form>
  </div>
</div>
</body>
</html>
