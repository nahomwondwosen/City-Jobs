<?php
session_start();
require_once "config.php";

// ✅ Make sure employer is logged in
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employer'){
    header("Location: index.php");
    exit;
}

$employer_id = $_SESSION['user_id'];

// ✅ Get job_id from URL
if(!isset($_GET['job_id']) || empty($_GET['job_id'])){
    header("Location: employer_dashboard.php");
    exit;
}

$job_id = intval($_GET['job_id']);

// ✅ Handle application status update
if(isset($_POST['update_status'])){
    $app_id = intval($_POST['app_id']);
    $new_status = $_POST['status'] === 'accepted' ? 'accepted' : 'rejected';

    $stmt = $conn->prepare("UPDATE applications a
                            JOIN jobs j ON a.job_id = j.id
                            SET a.status = ?
                            WHERE a.id = ? AND j.employer_id = ?");
    $stmt->bind_param("sii", $new_status, $app_id, $employer_id);
    $stmt->execute();
    $_SESSION['success'] = "Application status updated!";
    header("Location: view-applications.php?job_id=$job_id");
    exit;
}

// ✅ Fetch job info
$job_stmt = $conn->prepare("SELECT * FROM jobs WHERE id=? AND employer_id=? LIMIT 1");
$job_stmt->bind_param("ii", $job_id, $employer_id);
$job_stmt->execute();
$job_result = $job_stmt->get_result();
if($job_result->num_rows === 0){
    die("Job not found or you don't have permission.");
}
$job = $job_result->fetch_assoc();

// ✅ Fetch applications for this job
$app_stmt = $conn->prepare("
    SELECT a.id AS app_id, a.status, a.created_at, u.id AS freelancer_id, u.name, u.contact, u.email
    FROM applications a
    JOIN users u ON a.freelancer_id = u.id
    WHERE a.job_id = ?
    ORDER BY a.created_at DESC
");
$app_stmt->bind_param("i", $job_id);
$app_stmt->execute();
$applications = $app_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Applications for <?php echo htmlspecialchars($job['title']); ?> - City Jobs</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { background:#121212; color:#fff; font-family: 'Poppins', sans-serif; }
.navbar { background:#1f1f1f; }
.card { background:#1e1e1e; border-radius:10px; padding:15px; margin-bottom:15px; }
.btn-primary { background:#0af; border:none; }
.btn-primary:hover { background:#08c; }
.btn-success { background:#28a745; border:none; }
.btn-danger { background:#dc3545; border:none; }
.badge-pending { background:#ffc107; color:#000; }
.badge-accepted { background:#28a745; }
.badge-rejected { background:#dc3545; }
footer { background:#1a1a1a; padding:30px 0; text-align:center; color:#aaa; margin-top:50px; }
</style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark mb-4">
<div class="container">
<a class="navbar-brand fw-bold" href="employer_dashboard.php">City Jobs</a>
<ul class="navbar-nav ms-auto">
<li class="nav-item"><a href="messages.php" class="nav-link">💬 Messages</a></li>
<li class="nav-item"><a href="payment_dashboard.php" class="nav-link">💳 Payment</a></li>
<li class="nav-item"><a href="logout.php" class="nav-link text-danger">Logout</a></li>
</ul>
</div>
</nav>

<div class="container">
<?php if(isset($_SESSION['success'])): ?>
<div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
<?php endif; ?>

<h2 class="mb-3 text-primary">Applications for "<?php echo htmlspecialchars($job['title']); ?>"</h2>

<?php if($applications->num_rows > 0): ?>
<div class="row">
<?php while($app = $applications->fetch_assoc()): ?>
<div class="col-md-6">
<div class="card">
<h5><?php echo htmlspecialchars($app['name']); ?></h5>
<p>Email: <?php echo htmlspecialchars($app['email']); ?></p>
<p>Contact: <?php echo htmlspecialchars($app['contact']); ?></p>
<p>Applied on: <?php echo date("M d, Y", strtotime($app['created_at'])); ?></p>
<p>Status: 
<?php 
$status = $app['status'];
if($status == 'pending') echo "<span class='badge badge-pending'>Pending</span>";
elseif($status == 'accepted') echo "<span class='badge badge-accepted'>Accepted</span>";
else echo "<span class='badge badge-rejected'>Rejected</span>";
?>
</p>
<form method="POST" class="d-flex gap-2 mt-2">
<input type="hidden" name="app_id" value="<?php echo $app['app_id']; ?>">
<select name="status" class="form-select form-select-sm w-50">
<option value="pending" <?php if($status=='pending') echo 'selected'; ?>>Pending</option>
<option value="accepted" <?php if($status=='accepted') echo 'selected'; ?>>Accept</option>
<option value="rejected" <?php if($status=='rejected') echo 'selected'; ?>>Reject</option>
</select>
<button type="submit" name="update_status" class="btn btn-primary btn-sm">Update</button>
</form>
<a href="view_profile.php?id=<?php echo $app['freelancer_id']; ?>" class="btn btn-primary btn-sm mt-2">View Profile</a>
</div>
</div>
<?php endwhile; ?>
</div>
<?php else: ?>
<p>No applications yet for this job.</p>
<?php endif; ?>

</div>

<footer>
<p>© 2025 City Jobs | Built for Jimma Freelancers & Businesses</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
