<?php
session_start();
require_once "config.php";

$error = '';

// Handle POST login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login    = trim($_POST['email']);  // email or contact
    $password = $_POST['password'];

    // Fetch user by email or contact
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
            } elseif ($user['role'] === "admin") {
                header("Location: admin_dashboard.php");
                exit();
            } else {
                $error = "Your account role is invalid. Please contact support.";
            }
        } else {
            $error = "Invalid email/contact or password.";
        }
    } else {
        $error = "Invalid email/contact or password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - City Jobs</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { background-color: #0e0e0e; color: #fff; font-family: 'Poppins', sans-serif; }
.card { background-color: #1a1a1a; border-radius: 10px; color: #fff; }
.btn-primary { background-color: #0d6efd; border: none; }
.btn-primary:hover { background-color: #0069d9; }
input.form-control { background-color: #2a2a2a; color: #fff; border: none; }
.alert { color:#fff; background-color:#dc3545; border:none; }
</style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark mb-5">
  <div class="container">
    <a class="navbar-brand fw-bold" href="index.html">City Jobs</a>
  </div>
</nav>

<div class="container">
  <div class="row justify-content-center mt-5">
    <div class="col-md-5">
      <div class="card p-4 shadow-lg">
        <h2 class="text-center text-primary mb-4">Login</h2>

        <?php if($error): ?>
            <div class="alert"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST">
          <div class="mb-3">
            <label class="form-label">Email or Contact</label>
            <input type="text" class="form-control" name="email" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" class="form-control" name="password" required>
          </div>
          <button type="submit" class="btn btn-primary w-100">Login</button>
        </form>
        <br>
        <p class="text-center text-muted">Don't have an account? <a href="register.php">Register here</a></p>
      </div>
    </div>
  </div>
</div>
</body>
</html>
