<?php
session_start();
require_once "config.php";

// ✅ Check if logged in as freelancer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'freelancer') {
    header("Location: login.php");
    exit;
}

$freelancer_id = $_SESSION['user_id'];

// --- Handle comment form submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment_submit'])) {
    $job_id = intval($_POST['job_id']);
    $employer_id = intval($_POST['employer_id']);
    $comment = trim($_POST['comment']);

    if ($comment !== '' && $job_id > 0 && $employer_id > 0) {
        $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?,?,?)");
        $stmt->bind_param("iis", $freelancer_id, $employer_id, $comment);
        $stmt->execute();

        // Create notification for employer
        $notify = $conn->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
        $msg = "New message from a freelancer about your job post.";
        $notify->bind_param("is", $employer_id, $msg);
        $notify->execute();

        $_SESSION['success'] = "Your comment has been sent to the employer.";
        header("Location: freelancer_dashboard.php");
        exit;
    }
}

// --- Search Query ---
$search_query = isset($_GET['search']) ? trim($_GET['search']) : "";

// --- Fetch available jobs ---
$jobs_sql = "
    SELECT j.id, j.title, j.description, j.category, j.status, j.created_at,
           u.id AS employer_id, u.name AS employer_name, u.email AS employer_email, u.contact AS employer_contact
    FROM jobs j
    JOIN users u ON j.employer_id = u.id
    WHERE j.status = 'open'
";
if(!empty($search_query)){
    $jobs_sql .= " AND (j.title LIKE ? OR j.description LIKE ? OR j.category LIKE ? OR u.name LIKE ?)";
}
$jobs_sql .= " ORDER BY j.created_at DESC";

$jobs_stmt = $conn->prepare($jobs_sql);
if(!empty($search_query)){
    $like = "%$search_query%";
    $jobs_stmt->bind_param("ssss", $like, $like, $like, $like);
}
$jobs_stmt->execute();
$available_jobs = $jobs_stmt->get_result();

// --- Fetch jobs applied by freelancer ---
$applied_sql = "
    SELECT j.id AS job_id, j.title, j.category, a.status AS app_status, a.created_at
    FROM applications a
    JOIN jobs j ON a.job_id = j.id
    WHERE a.freelancer_id = ?
";
if(!empty($search_query)){
    $applied_sql .= " AND (j.title LIKE ? OR j.category LIKE ?)";
}

$applied_stmt = $conn->prepare($applied_sql);
if(!empty($search_query)){
    $like = "%$search_query%";
    $applied_stmt->bind_param("iss", $freelancer_id, $like, $like);
} else {
    $applied_stmt->bind_param("i", $freelancer_id);
}
$applied_stmt->execute();
$applied_jobs = $applied_stmt->get_result();

// --- Fetch notifications ---
$notif_stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
$notif_stmt->bind_param("i", $freelancer_id);
$notif_stmt->execute();
$notifications = $notif_stmt->get_result();

// --- Count unread notifications ---
$unread_stmt = $conn->prepare("SELECT COUNT(*) AS unread FROM notifications WHERE user_id=? AND is_read=0");
$unread_stmt->bind_param("i", $freelancer_id);
$unread_stmt->execute();
$unread_count = $unread_stmt->get_result()->fetch_assoc()['unread'] ?? 0;

// --- Fetch ratings received ---
$ratings_stmt = $conn->prepare("
    SELECT r.rating, r.comment, r.created_at, u.name AS rater_name
    FROM ratings r
    JOIN users u ON r.rater_user_id = u.id
    WHERE r.rated_user_id = ?
    ORDER BY r.created_at DESC
");
$ratings_stmt->bind_param("i", $freelancer_id);
$ratings_stmt->execute();
$ratings = $ratings_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Freelancer Dashboard - City Jobs</title>
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
textarea { background: #1a1a1a; color: #fff; border: none; border-radius: 6px; padding: 10px; width: 100%; resize: none; }
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
  <a class="navbar-brand" href="freelancer_dashboard.php">City Jobs</a>

  <!-- Search Form -->
  <form class="d-flex me-3" method="GET">
      <input class="form-control me-2 search-input" type="search" name="search" value="<?= htmlspecialchars($search_query); ?>" placeholder="Search Jobs & Applications...">
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

<!-- My Applications -->
<h2 class="section-title" data-aos="fade-right">My Applications</h2>
<?php if ($applied_jobs->num_rows > 0): ?>
<div class="row g-4">
<?php while($app = $applied_jobs->fetch_assoc()): ?>
<div class="col-md-6" data-aos="fade-up">
  <div class="card p-4 h-100">
    <h5 class="fw-bold text-info"><?= htmlspecialchars($app['title']); ?></h5>
    <p>Category: <?= htmlspecialchars($app['category']); ?></p>
    <p>Status: 
      <?php 
      $status = $app['app_status'];
      if($status == 'pending') echo "<span class='badge badge-pending'>Pending</span>";
      elseif($status == 'accepted') echo "<span class='badge badge-accepted'>Accepted</span>";
      else echo "<span class='badge badge-rejected'>Rejected</span>";
      ?>
    </p>
    <a href="job-details.php?id=<?= $app['job_id']; ?>" class="btn btn-primary btn-sm mt-2">View Job</a>
  </div>
</div>
<?php endwhile; ?>
</div>
<?php else: ?>
<p data-aos="fade-up">No job applications yet.</p>
<?php endif; ?>




<hr data-aos="fade-in">

<!-- Available Jobs -->
<h2 class="section-title" data-aos="fade-right">Available Jobs</h2>
<?php if ($available_jobs->num_rows > 0): ?>
<div class="row g-4">
<?php while($job = $available_jobs->fetch_assoc()): ?>
<div class="col-md-6" data-aos="fade-up" data-aos-delay="100">
  <div class="card p-4 h-100">
    <h5 class="fw-bold text-info"><?= htmlspecialchars($job['title']); ?></h5>
    <p><?= nl2br(htmlspecialchars($job['description'])); ?></p>
    <p><strong>Category:</strong> <?= htmlspecialchars($job['category']); ?></p>
    <div class="p-3 bg-dark rounded mb-3">
      <strong>Employer:</strong> <?= htmlspecialchars($job['employer_name']); ?><br>
      <small>Email: <?= htmlspecialchars($job['employer_email']); ?></small><br>
      <small>Contact: <?= htmlspecialchars($job['employer_contact']); ?></small>
    </div>

    <form method="POST">
      <textarea name="comment" rows="3" placeholder="Write a message to the employer..."></textarea>
      <input type="hidden" name="job_id" value="<?= $job['id']; ?>">
      <input type="hidden" name="employer_id" value="<?= $job['employer_id']; ?>">
      <div class="d-flex gap-2 mt-2">
        <button type="submit" name="comment_submit" class="btn btn-primary flex-fill">Send Comment</button>
        <a href="apply-job.php?job_id=<?= $job['id']; ?>" class="btn btn-outline-info flex-fill">Apply</a>
      </div>
    </form>
  </div>
</div>
<?php endwhile; ?>
</div>
<?php else: ?>
<p data-aos="fade-up">No open jobs available right now.</p>
<?php endif; ?>

</div>

<footer data-aos="fade-up">
  <p>© 2025 City Jobs | Empowering Jimma’s Freelancers</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>
<script>
AOS.init({ duration: 900, offset: 120, easing: 'ease-in-out' });
</script>
</body>
</html>
