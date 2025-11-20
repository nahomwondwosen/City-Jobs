<?php
session_start();
require_once "config.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $contact  = trim($_POST['contact'] ?? '');
    $password = $_POST['password'] ?? '';
    $role     = $_POST['role'] ?? 'freelancer';

    // Validate required fields
    if (empty($name) || empty($email) || empty($password)) {
        $_SESSION['error'] = "Name, email, and password are required!";
        header("Location: register.php");
        exit();
    }

    // Check duplicate email
    $check = $conn->prepare("SELECT id FROM users WHERE email=? LIMIT 1");
    $check->bind_param("s", $email);
    $check->execute();
    $check->store_result();
    if ($check->num_rows > 0) {
        $_SESSION['error'] = "Email already exists!";
        header("Location: register.php");
        exit();
    }

    // Check duplicate contact if provided
    if (!empty($contact)) {
        $check = $conn->prepare("SELECT id FROM users WHERE contact=? LIMIT 1");
        $check->bind_param("s", $contact);
        $check->execute();
        $check->store_result();
        if ($check->num_rows > 0) {
            $_SESSION['error'] = "Contact already exists!";
            header("Location: register.php");
            exit();
        }
    }

    // Hash password
    $password_hash = password_hash($password, PASSWORD_BCRYPT);

    // Handle optional profile picture
    $profile_pic = NULL;
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === 0) {
        $target_dir = "uploads/profile_pics/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0755, true);

        $file_ext = pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION);
        $allowed  = ['jpg','jpeg','png','gif'];
        if (!in_array(strtolower($file_ext), $allowed)) {
            $_SESSION['error'] = "Invalid profile picture format!";
            header("Location: register.php");
            exit();
        }

        $profile_pic = uniqid() . "." . $file_ext;
        move_uploaded_file($_FILES['profile_pic']['tmp_name'], $target_dir . $profile_pic);
    }

    // Insert into database
    $stmt = $conn->prepare("INSERT INTO users (name, email, contact, password_hash, role, profile_pic) VALUES (?,?,?,?,?,?)");
    $stmt->bind_param("ssssss", $name, $email, $contact, $password_hash, $role, $profile_pic);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Registration successful! Please login.";
        header("Location: login.php");
        exit();
    } else {
        $_SESSION['error'] = "Database error: " . $stmt->error;
        header("Location: register.php");
        exit();
    }
}
?>
