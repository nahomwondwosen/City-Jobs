<?php
session_start();
require_once "config.php";

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = $_POST['role'];

    // Check password match
    if ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email=?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $error = "Email is already registered.";
        } else {
            // Hash password
            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            // Insert user
            $stmt = $conn->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $name, $email, $password_hash, $role);
            if ($stmt->execute()) {
                $success = "Registration successful! You can now <a href='login.php'>login</a>.";
            } else {
                $error = "Failed to register. Try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register - City Jobs</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body {
    background-color: #0e0e0e;
    color: #ffffff;
    font-family: 'Poppins', sans-serif;
}
.navbar { background-color: #0b0b0b; }
.navbar-brand { color: #ffffff !important; }
.card { background-color: #1a1a1a; border: none; border-radius: 10px; color: #ffffff; }
.btn-primary { background-color: #0d6efd; border: none; color: #ffffff; }
.btn-primary:hover { background-color: #0069d9; }
a { color: #0d6efd; }
a:hover { text-decoration: none; color: #66b2ff; }
label { font-weight: 500; color: #ffffff; }
input.form-control, select.form-select { background-color: #2a2a2a; color: #ffffff; border: none; }
input.form-control:focus, select.form-select:focus { background-color: #2a2a2a; border-color: #0d6efd; box-shadow: none; color: #ffffff; }
.alert { color: #ffffff; background-color: #dc3545; border: none; }
footer { background-color: #0b0b0b; padding: 30px 0; text-align: center; color: #ffffff; margin-top: 50px; }
</style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark mb-5">
  <div class="container">
    <a class="navbar-brand fw-bold" href="index.html">City Jobs</a>
  </div>
</nav>

<!-- Register Form -->
<div class="container">
  <div class="row justify-content-center">
    <div class="col-md-6">
      <div class="card p-4 shadow-lg">
        <h2 class="text-center text-primary fw-bold mb-4">Register</h2>

        <?php if ($success): ?>
          <div class="alert alert-success"><?php echo $success; ?></div>
        <?php elseif ($error): ?>
          <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST">
          <div class="mb-3">
            <label for="name" class="form-label">Full Name</label>
            <input type="text" class="form-control" id="name" name="name" required>
          </div>

          <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" class="form-control" id="email" name="email" required>
          </div>

          <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input type="password" class="form-control" id="password" name="password" required>
          </div>

          <div class="mb-3">
            <label for="confirm_password" class="form-label">Confirm Password</label>
            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
          </div>

          <div class="mb-3">
            <label for="role" class="form-label">I am a</label>
            <select class="form-select" id="role" name="role" required>
              <option value="freelancer">Freelancer</option>
              <option value="employer">Employer</option>
              
            </select>
          </div>

          <button type="submit" class="btn btn-primary w-100 mb-3">Register</button>
        </form>

        <p class="text-center text-muted">Already have an account? <a href="login.php">Login here</a></p>
      </div>
    </div>
  </div>
</div>

<!-- Footer -->
<footer>
  <p>© 2025 City Jobs | Built for Jimma Freelancers & Businesses</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
