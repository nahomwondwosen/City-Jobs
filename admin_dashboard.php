<?php
session_start();
require_once "config.php";

// Check if admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// --- Handle actions ---
// Delete user
if (isset($_GET['delete_user'])) {
    $id = intval($_GET['delete_user']);
    $conn->query("DELETE FROM users WHERE id=$id");
    header("Location: admin_dashboard.php");
    exit;
}

// Ban / Unban user
if (isset($_GET['ban_user'])) {
    $id = intval($_GET['ban_user']);
    $conn->query("UPDATE users SET role='banned' WHERE id=$id");
    header("Location: admin_dashboard.php");
    exit;
}
if (isset($_GET['unban_user'])) {
    $id = intval($_GET['unban_user']);
    $conn->query("UPDATE users SET role='freelancer' WHERE id=$id");
    header("Location: admin_dashboard.php");
    exit;
}

// Delete job
if (isset($_GET['delete_job'])) {
    $id = intval($_GET['delete_job']);
    $conn->query("DELETE FROM jobs WHERE id=$id");
    header("Location: admin_dashboard.php");
    exit;
}

// Delete message
if (isset($_GET['delete_message'])) {
    $id = intval($_GET['delete_message']);
    $conn->query("DELETE FROM messages WHERE id=$id");
    header("Location: admin_dashboard.php");
    exit;
}

// --- Fetch data ---
$users = $conn->query("SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC LIMIT 20");
$jobs = $conn->query("SELECT jobs.id, jobs.title, jobs.status, users.name AS employer 
                      FROM jobs JOIN users ON jobs.employer_id = users.id ORDER BY jobs.created_at DESC LIMIT 20");

// Messages for table
$messages = $conn->query("SELECT m.id, m.message, u1.name AS sender, u2.name AS receiver, m.created_at 
                          FROM messages m JOIN users u1 ON m.sender_id = u1.id
                          JOIN users u2 ON m.receiver_id = u2.id ORDER BY m.created_at DESC LIMIT 20");

// Messages count for header badge
$header_messages = $conn->query("SELECT m.id FROM messages m WHERE m.receiver_id=".$_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Dashboard - City Jobs</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body {
    font-family: Arial, sans-serif;
    background:#121212;
    color:#fff;
    margin:0;
    overflow-x:hidden;
    scroll-behavior: smooth;
}
header {
    display:flex;
    justify-content:space-between;
    align-items:center;
    padding:15px;
    background:#1f1f1f;
}
header .logo { font-size:20px; font-weight:bold; }
header .nav a {
    color:#fff;
    text-decoration:none;
    margin-left:20px;
    position:relative;
    transition: color 0.3s;
}
header .nav a:hover { color:#0af; }
header .badge {
    position:absolute;
    top:-5px;
    right:-10px;
    background:red;
    color:#fff;
    font-size:12px;
    padding:2px 6px;
    border-radius:50%;
}
a.button {
    background:#0af;
    color:#fff;
    padding:10px 15px;
    border-radius:5px;
    text-decoration:none;
    margin-right:10px;
    transition: background 0.3s;
}
a.button:hover { background:#08c; }
.container { padding:20px; }
.section {
    margin-bottom:40px;
    opacity:0;
    transform: translateY(30px);
    transition: opacity 0.8s ease, transform 0.8s ease;
}
.section.visible {
    opacity:1;
    transform: translateY(0);
}
table {
    width:100%;
    border-collapse:collapse;
    margin-top:10px;
    background:#1e1e1e;
    transition: all 0.3s;
}
th, td { padding:10px; border:1px solid #333; }
th { background:#333; }
a.action { color:#0af; text-decoration:none; margin-right:10px; transition: color 0.3s; }
a.action:hover { color:#66b2ff; }
a.delete { color:#f55; transition: color 0.3s; }
a.delete:hover { color:#ff8888; }
a.ban { color:orange; transition: color 0.3s; }
a.ban:hover { color:#ffb84d; }
table tr:hover { background:#2a2a2a; }
</style>
</head>
<body>

<header>
  <div class="logo">City Jobs</div>
  <div class="nav">
    <a href="messages.php">💬 Messages
        <?php if($header_messages->num_rows > 0): ?>
            <span class="badge"><?php echo $header_messages->num_rows; ?></span>
        <?php endif; ?>
    </a>
    <a href="payment_dashboard.php">💳 Payment</a>
    <a href="logout.php" class="button" style="background:#f55;">Logout</a>
  </div>
</header>

<div class="container">

  <!-- Users Section -->
  <div class="section">
    <h2>👤 Users</h2>
    <table>
      <tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Joined</th><th>Actions</th></tr>
      <?php while($u = $users->fetch_assoc()): ?>
        <tr>
          <td><?php echo $u['id']; ?></td>
          <td><?php echo htmlspecialchars($u['name']); ?></td>
          <td><?php echo htmlspecialchars($u['email']); ?></td>
          <td><?php echo $u['role']; ?></td>
          <td><?php echo $u['created_at']; ?></td>
          <td>
            <?php if($u['role'] !== 'banned'): ?>
                <a class="ban" href="?ban_user=<?php echo $u['id']; ?>" onclick="return confirm('Ban this user?');">Ban</a>
            <?php else: ?>
                <a class="ban" href="?unban_user=<?php echo $u['id']; ?>" onclick="return confirm('Unban this user?');">Unban</a>
            <?php endif; ?>
            <a class="delete" href="?delete_user=<?php echo $u['id']; ?>" onclick="return confirm('Delete this user?');">Delete</a>
          </td>
        </tr>
      <?php endwhile; ?>
    </table>
  </div>

  <!-- Jobs Section -->
  <div class="section">
    <h2>💼 Jobs</h2>
    <table>
      <tr><th>ID</th><th>Title</th><th>Status</th><th>Employer</th><th>Actions</th></tr>
      <?php while($j = $jobs->fetch_assoc()): ?>
        <tr>
          <td><?php echo $j['id']; ?></td>
          <td><?php echo htmlspecialchars($j['title']); ?></td>
          <td><?php echo $j['status']; ?></td>
          <td><?php echo htmlspecialchars($j['employer']); ?></td>
          <td>
            <a class="delete" href="?delete_job=<?php echo $j['id']; ?>" onclick="return confirm('Delete this job?');">Delete</a>
          </td>
        </tr>
      <?php endwhile; ?>
    </table>
  </div>

  <!-- Messages Section -->
  <div class="section">
    <h2>💬 Messages</h2>
    <table>
      <tr><th>ID</th><th>Sender</th><th>Receiver</th><th>Message</th><th>Time</th><th>Actions</th></tr>
      <?php while($m = $messages->fetch_assoc()): ?>
        <tr>
          <td><?php echo $m['id']; ?></td>
          <td><?php echo htmlspecialchars($m['sender']); ?></td>
          <td><?php echo htmlspecialchars($m['receiver']); ?></td>
          <td><?php echo htmlspecialchars($m['message']); ?></td>
          <td><?php echo $m['created_at']; ?></td>
          <td>
            <a class="delete" href="?delete_message=<?php echo $m['id']; ?>" onclick="return confirm('Delete this message?');">Delete</a>
          </td>
        </tr>
      <?php endwhile; ?>
    </table>
  </div>

</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function(){
    // Fade-in sections sequentially
    $('.section').each(function(i){
        $(this).delay(i * 200).queue(function(next){
            $(this).addClass('visible');
            next();
        });
    });
});
</script>
</body>
</html>
