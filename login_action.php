<?php
session_start();
require_once "config.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $login    = trim($_POST['email']);  // email or contact
    $password = $_POST['password'];

    // Fetch user by email
    $stmt = $conn->prepare("SELECT id, name, email, contact, password_hash, role FROM users WHERE email=? OR contact=? LIMIT 1");
    $stmt->bind_param("ss", $login, $login);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password_hash'])) {
            // Login success
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name']    = $user['name'];
            $_SESSION['role']    = $user['role'];

            // Redirect based on role
            if ($user['role'] === "employer") {
                header("Location: employer_dashboard.php");
                exit();
            } elseif ($user['role'] === "freelancer") {
                header("Location: freelancer_dashboard.php");
                exit();
            } else {
                header("Location: admin_dashboard.php");
                exit();
            }
        } else {
            $_SESSION['error'] = "Invalid credentials!";
            header("Location: login.php");
            exit();
        }
    } else {
        $_SESSION['error'] = "Invalid credentials!";
        header("Location: login.php");
        exit();
    }
}
?>
