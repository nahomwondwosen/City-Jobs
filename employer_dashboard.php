<?php
session_start();
require_once "config.php";

// ✅ Ensure employer is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employer') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// --- Handle new job post ---
$msg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_job'])) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $category = trim($_POST['category']);
    $budget = floatval($_POST['budget']);

    $stmt = $conn->prepare("INSERT INTO jobs (employer_id, title, description, category, budget, status) VALUES (?,?,?,?,?, 'open')");
    $stmt->bind_param("isssd", $user_id, $title, $description, $category, $budget);
    if ($stmt->execute()) {
        $msg = "<div class='alert alert-success mt-3'>✅ Job posted successfully!</div>";
    } else {
        $msg = "<div class='alert alert-danger mt-3'>❌ Error posting job.</div>";
    }
}

// --- Handle search ---
$search_query = isset($_GET['search']) ? trim($_GET['search']) : "";

// --- Fetch jobs posted by employer with applications count ---
$jobs_stmt = $conn->prepare("
    SELECT j.id, j.title, j.category, j.created_at, j.status, COUNT(a.id) AS applications
    FROM jobs j
    LEFT JOIN applications a ON a.job_id = j.id
    WHERE j.employer_id = ?
    GROUP BY j.id
    ORDER BY j.created_at DESC
");
$jobs_stmt->bind_param("i", $user_id);
$jobs_stmt->execute();
$jobs = $jobs_stmt->get_result();

// --- Fetch freelancers ---
$freelancers_sql = "SELECT id, name, email, contact FROM users WHERE role='freelancer'";
if (!empty($search_query)) {
    $freelancers_sql .= " AND (name LIKE ? OR email LIKE ?)";
    $freelancers_stmt = $conn->prepare($freelancers_sql);
    $like = "%$search_query%";
    $freelancers_stmt->bind_param("ss", $like, $like);
} else {
    $freelancers_stmt = $conn->prepare($freelancers_sql);
}
$freelancers_stmt->execute();
$freelancers = $freelancers_stmt->get_result();

// --- Fetch applications for employer jobs ---
$applications_sql = "
    SELECT a.id AS app_id, a.status AS app_status, a.created_at, 
           j.title AS job_title, f.name AS freelancer_name, f.email AS freelancer_email, f.contact AS freelancer_contact
    FROM applications a
    JOIN jobs j ON a.job_id = j.id
    JOIN users f ON a.freelancer_id = f.id
    WHERE j.employer_id = ?
";
if (!empty($search_query)) {
    $applications_sql .= " AND (j.title LIKE ? OR f.name LIKE ?)";
}
$app_stmt = $conn->prepare($applications_sql);
if (!empty($search_query)) {
    $like = "%$search_query%";
    $app_stmt->bind_param("iss", $user_id, $like, $like);
} else {
    $app_stmt->bind_param("i", $user_id);
}
$app_stmt->execute();
$applications = $app_stmt->get_result();

// --- Fetch notifications ---
$notif_stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 10");
$notif_stmt->bind_param("i", $user_id);
$notif_stmt->execute();
$notifications = $notif_stmt->get_result();

// --- Count unread notifications ---
$unread_stmt = $conn->prepare("SELECT COUNT(*) AS unread FROM notifications WHERE user_id=? AND is_read=0");
$unread_stmt->bind_param("i", $user_id);
$unread_stmt->execute();
$unread_count = $unread_stmt->get_result()->fetch_assoc()['unread'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Employer Dashboard - City Jobs</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://unpkg.com/aos@2.3.4/dist/aos.css" rel="stylesheet">
<style>
body { background: #050505; color: #e0e0e0; font-family: 'Poppins', sans-serif; scroll-behavior: smooth; }
.navbar { background: #0b0b0b; box-shadow: 0 2px 5px rgba(0,0,0,0.5); }
.navbar-brand { color: #00bfff !important; font-weight: 600; letter-spacing: 1px; }
.nav-link { color: #b0b0b0 !important; }
.nav-link:hover { color: #00bfff !important; }
.notification-bell { position: relative; }
.notification-badge { position: absolute; top: -6px; right: -8px; background: #ff4757; color: white; font-size: 11px; border-radius: 50%; padding: 2px 5px; }
.card { background: #111111; color: #e0e0e0; border: 1px solid rgba(0,191,255,0.1); border-radius: 12px; transition: transform 0.3s ease, box-shadow 0.3s ease; }
.card:hover { transform: translateY(-6px); box-shadow: 0 8px 20px rgba(0,191,255,0.25); }
.btn-primary { background: linear-gradient(90deg, #007bff, #00bfff); border: none; }
.btn-primary:hover { background: linear-gradient(90deg, #0066cc, #00aaff); }
.btn-outline-info { color: #00bfff; border-color: #00bfff; }
.btn-outline-info:hover { background-color: #00bfff; color: #fff; }
.badge-pending { background: #ffc107; color: #000; }
.badge-accepted { background: #28a745; }
.badge-rejected { background: #dc3545; }
.section-title { color: #00bfff; font-weight: 600; margin-bottom: 20px; }
hr { border-top: 1px solid rgba(0,191,255,0.1); margin: 40px 0; }
footer { background: #0b0b0b; padding: 40px 0; text-align: center; color: #6caeff; margin-top: 60px; }
footer p { margin: 0; }
.search-input { background: #1a1a1a; border: 1px solid #333; color: #fff; }
.search-input:focus { background: #1f1f1f; color: #fff; border-color: #00bfff; box-shadow: none; }
</style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark sticky-top">
<div class="container">
  <a class="navbar-brand" href="employer_dashboard.php">City Jobs</a>

  <!-- Search Form -->
  <form class="d-flex me-3" method="GET">
      <input class="form-control me-2 search-input" type="search" name="search" value="<?= htmlspecialchars($search_query); ?>" placeholder="Search Jobs & Freelancers...">
      <button class="btn btn-primary" type="submit">Search</button>
  </form>

  <ul class="navbar-nav ms-auto align-items-center">

  <!-- 🔔 Notifications -->
  <li class="nav-item me-3 notification-bell dropdown">
    <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
      <i class="bi bi-bell text-info fs-5"></i>
      <?php if ($unread_count > 0): ?>
      <span class="notification-badge"><?= $unread_count; ?></span>
      <?php endif; ?>
    </a>
    <ul class="dropdown-menu dropdown-menu-dark">
      <?php if ($notifications->num_rows > 0): ?>
        <?php while($n = $notifications->fetch_assoc()): ?>
          <li><a class="dropdown-item" href="#"><?= htmlspecialchars($n['message']); ?><br><small class="text-muted"><?= $n['created_at']; ?></small></a></li>
        <?php endwhile; ?>
      <?php else: ?>
        <li><span class="dropdown-item text-muted">No new notifications</span></li>
      <?php endif; ?>
    </ul>
  </li>

  <!-- ➕ Post Job -->
  <li class="nav-item"><a href="#" class="nav-link" data-bs-toggle="modal" data-bs-target="#jobModal">➕ Post Job</a></li>

  <!-- 💬 Messages -->
  <li class="nav-item"><a href="messages.php" class="nav-link">💬 Messages</a></li>

  <!-- 💳 Payment -->
  <li class="nav-item"><a href="payment_dashboard.php" class="nav-link">💳 Payment</a></li>

  <!-- 👤 Profile Dropdown -->
  <li class="nav-item dropdown">
    <a href="#" class="nav-link dropdown-toggle d-flex align-items-center" id="profileDropdown" data-bs-toggle="dropdown" aria-expanded="false">
      <img src="uploads/<?= htmlspecialchars($_SESSION['profile_image'] ?? 'default.png'); ?>" 
           alt="Profile" width="35" height="35" 
           class="rounded-circle me-2" style="object-fit:cover;">
      <span><?= htmlspecialchars($_SESSION['name'] ?? 'Employer'); ?></span>
    </a>
    <ul class="dropdown-menu dropdown-menu-end dropdown-menu-dark" aria-labelledby="profileDropdown">
      <li><h6 class="dropdown-header text-info"><?= ucfirst($_SESSION['role']); ?></h6></li>
      <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person me-2"></i> View Profile</a></li>
      <li><a class="dropdown-item" href="settings.php"><i class="bi bi-gear me-2"></i> Settings</a></li>
      <li><hr class="dropdown-divider"></li>
      <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i> Logout</a></li>
    </ul>
  </li>
</ul>

</div>
</nav>

<div class="container py-5">
<?= $msg; ?>

<!-- Posted Jobs -->
<h2 class="section-title" data-aos="fade-right">Your Posted Jobs</h2>
<?php if ($jobs->num_rows > 0): ?>
<div class="row g-4">
<?php while($job = $jobs->fetch_assoc()): ?>
<div class="col-md-6" data-aos="fade-up">
  <div class="card p-4 h-100">
    <h5 class="fw-bold text-info"><?= htmlspecialchars($job['title']); ?></h5>
    <p>Category: <?= htmlspecialchars($job['category']); ?></p>
    <p>Status: <?= htmlspecialchars($job['status']); ?></p>
    <p>Applications: <?= $job['applications']; ?></p>
    <div class="d-flex gap-2 mt-2">
        <a href="edit-job.php?id=<?= $job['id']; ?>" class="btn btn-outline-info flex-fill">Edit</a>
        <a href="delete-job.php?id=<?= $job['id']; ?>" class="btn btn-danger flex-fill" onclick="return confirm('Are you sure?')">Delete</a>
    </div>
  </div>
</div>
<?php endwhile; ?>
</div>
<?php else: ?>
<p data-aos="fade-up">No jobs posted yet.</p>
<?php endif; ?>

<hr data-aos="fade-in">

<!-- Applications -->
<h2 class="section-title" data-aos="fade-right">Freelancer Applications</h2>
<?php if ($applications->num_rows > 0): ?>
<div class="row g-4">
<?php while($app = $applications->fetch_assoc()): ?>
<div class="col-md-6" data-aos="fade-up">
  <div class="card p-4 h-100">
    <h5 class="fw-bold text-info"><?= htmlspecialchars($app['freelancer_name']); ?></h5>
    <p><strong>Job Applied:</strong> <?= htmlspecialchars($app['job_title']); ?></p>
    <p>Email: <?= htmlspecialchars($app['freelancer_email']); ?><br>Contact: <?= htmlspecialchars($app['freelancer_contact']); ?></p>
    <p>Status: 
      <?php 
      $status = $app['app_status'];
      if($status == 'pending') echo "<span class='badge badge-pending'>Pending</span>";
      elseif($status == 'accepted') echo "<span class='badge badge-accepted'>Accepted</span>";
      else echo "<span class='badge badge-rejected'>Rejected</span>";
      ?>
    </p>
    <a href="view-application.php?id=<?= $app['app_id']; ?>" class="btn btn-primary btn-sm mt-2">View Application</a>
  </div>
</div>
<?php endwhile; ?>
</div>
<?php else: ?>
<p data-aos="fade-up">No applications yet.</p>
<?php endif; ?>

<hr data-aos="fade-in">

<!-- Freelancers List -->
<h2 class="section-title" data-aos="fade-right">Freelancers</h2>
<?php if ($freelancers->num_rows > 0): ?>
<div class="row g-4">
<?php while($f = $freelancers->fetch_assoc()): ?>
<div class="col-md-6" data-aos="fade-up">
  <div class="card p-3">
    <h5><?= htmlspecialchars($f['name']); ?></h5>
    <p>Email: <?= htmlspecialchars($f['email']); ?></p>
    <p>Contact: <?= htmlspecialchars($f['contact']); ?></p>
    <a href="view_profile.php?id=<?= $f['id']; ?>" class="btn btn-outline-info btn-sm">View Profile</a>
  </div>
</div>
<?php endwhile; ?>
</div>
<?php else: ?>
<p data-aos="fade-up">No freelancers found.</p>
<?php endif; ?>
</div>

<!-- Post Job Modal -->
<div class="modal fade" id="jobModal" tabindex="-1">
<div class="modal-dialog">
  <div class="modal-content p-4" style="background:#121212; color:#fff; border-radius:12px;">
    <h4>Post a New Job</h4>
    <form method="POST">
      <input type="text" name="title" class="form-control my-2" placeholder="Job Title" required>
      <textarea name="description" class="form-control my-2" placeholder="Job Description" required></textarea>
      <input type="text" name="category" class="form-control my-2" placeholder="Category" required>
      <input type="number" step="0.01" name="budget" class="form-control my-2" placeholder="Budget (ETB)" required>
      <div class="text-end mt-2">
        <button type="submit" name="post_job" class="btn btn-info">Post Job</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
      </div>
    </form>
  </div>
</div>
</div>

<footer data-aos="fade-up">
  <p>© 2025 City Jobs | Empowering Ethiopian Freelancers & Businesses</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>
<script>
AOS.init({ duration: 900, offset: 120, easing: 'ease-in-out' });
</script>
</body>
</html>
