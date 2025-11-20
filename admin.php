<?php
session_start();
require_once "config.php";

// Handle logout
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: admin.php");
    exit;
}

$error = "";

// Handle login
if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_SESSION['admin_id'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE contact=? AND role='admin' LIMIT 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $res = $stmt->get_result();
    $admin = $res->fetch_assoc();

    if ($admin && password_verify($password, $admin['password_hash'])) {
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_name'] = $admin['name'];
    } else {
        $error = "Invalid admin login!";
    }
}

// If logged in → dashboard
if (isset($_SESSION['admin_id'])) {
    // Fetch stats
    $total_users = $conn->query("SELECT COUNT(*) AS c FROM users")->fetch_assoc()['c'];
    $total_jobs = $conn->query("SELECT COUNT(*) AS c FROM jobs")->fetch_assoc()['c'];
    $total_messages = $conn->query("SELECT COUNT(*) AS c FROM messages")->fetch_assoc()['c'];
    $total_payments = $conn->query("SELECT SUM(amount) AS total FROM payments")->fetch_assoc()['total'] ?? 0;
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <style>
    body { font-family: Arial, sans-serif; background:#121212; color:#fff; margin:0; }
    header { display:flex; justify-content:space-between; align-items:center; padding:15px 25px; background:#1f1f1f; }
    header .logo { font-size:20px; font-weight:bold; }
    header .nav { display:flex; gap:20px; align-items:center; }
    header .nav a { color:#0af; text-decoration:none; }
    .container { padding:20px; }
    .stats { display:grid; grid-template-columns: repeat(auto-fit,minmax(200px,1fr)); gap:20px; margin-top:20px; }
    .stat-card { background:#1e1e1e; padding:20px; border-radius:10px; text-align:center; }
    </style>
    </head>
    <body>
    <header>
      <div class="logo">City Jobs - Admin</div>
      <div class="nav">
        <div>👋 Welcome, <?php echo $_SESSION['admin_name']; ?></div>
        <a href="admin.php?logout=1">Logout</a>
      </div>
    </header>
    <div class="container">
      <h2>Dashboard Overview</h2>
      <div class="stats">
        <div class="stat-card"><h2><?php echo $total_users; ?></h2><p>Users</p></div>
        <div class="stat-card"><h2><?php echo $total_jobs; ?></h2><p>Jobs</p></div>
        <div class="stat-card"><h2><?php echo $total_messages; ?></h2><p>Messages</p></div>
        <div class="stat-card"><h2>$<?php echo number_format($total_payments,2); ?></h2><p>Payments</p></div>
      </div>
    </div>
    </body>
    </html>
    <?php
    exit;
}

// If not logged in → show login form
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Login - City Jobs</title>
<style>
body { font-family: Arial, sans-serif; background:#121212; color:#fff; display:flex; justify-content:center; align-items:center; height:100vh; }
form { background:#1e1e1e; padding:30px; border-radius:10px; width:300px; }
input { width:100%; padding:10px; margin:10px 0; border:none; border-radius:5px; }
button { width:100%; padding:10px; background:#0af; border:none; border-radius:5px; color:#fff; font-weight:bold; }
.error { color:red; text-align:center; }
</style>
</head>
<body>
<form method="post">
  <h2>Admin Login</h2>
  <?php if($error): ?><p class="error"><?php echo $error; ?></p><?php endif; ?>
  <input type="text" name="username" placeholder="Email or Contact" required>
  <input type="password" name="password" placeholder="Password" required>
  <button type="submit">Login</button>
</form>
</body>
</html>
