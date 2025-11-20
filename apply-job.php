<?php
session_start();
require_once "config.php";

// Check if freelancer is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'freelancer') {
    header("Location: login.php");
    exit;
}

$freelancer_id = $_SESSION['user_id'];

// Check if job ID is provided
if (!isset($_GET['job_id']) || empty($_GET['job_id'])) {
    header("Location: jobs.php");
    exit;
}

$job_id = intval($_GET['job_id']);

// Fetch job details
$stmt = $conn->prepare("SELECT title FROM jobs WHERE id=?");
$stmt->bind_param("i", $job_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    echo "Job not found.";
    exit;
}
$job = $result->fetch_assoc();

// Handle form submission
$success = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cover_letter = trim($_POST['cover_letter']);

    // Check if already applied
    $check = $conn->prepare("SELECT id FROM applications WHERE job_id=? AND freelancer_id=?");
    $check->bind_param("ii", $job_id, $freelancer_id);
    $check->execute();
    $check_result = $check->get_result();

    if ($check_result->num_rows > 0) {
        $error = "You have already applied for this job.";
    } else {
        $stmt = $conn->prepare("INSERT INTO applications (job_id, freelancer_id, cover_letter) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $job_id, $freelancer_id, $cover_letter);
        if ($stmt->execute()) {
            $success = "Application submitted successfully!";
        } else {
            $error = "Failed to submit application. Try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Apply for <?php echo htmlspecialchars($job['title']); ?> - City Jobs</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { background-color: #0e0e0e; color: #f8f9fa; font-family: 'Poppins', sans-serif; }
.navbar { background-color: #1a1a1a; }
.card { background-color: #1f1f1f; border: none; }
.btn-primary { background-color: #0d6efd; border: none; }
footer { background-color: #1a1a1a; padding: 30px 0; text-align: center; }
label { font-weight: 500; }
</style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark">
<div class="container">
<a class="navbar-brand fw-bold" href="index.html">City Jobs</a>
<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
<span class="navbar-toggler-icon"></span>
</button>
<div class="collapse navbar-collapse" id="navMenu">
<ul class="navbar-nav ms-auto">
<li class="nav-item"><a href="index.html" class="nav-link">Home</a></li>
<li class="nav-item"><a href="jobs.php" class="nav-link">Find Jobs</a></li>
<li class="nav-item"><a href="login.php" class="nav-link">Login</a></li>
</ul>
</div>
</div>
</nav>

<section class="py-5">
<div class="container">
<div class="card p-4 mx-auto" style="max-width: 700px;">
<h2 class="fw-bold text-primary mb-3">Apply for <?php echo htmlspecialchars($job['title']); ?></h2>

<?php if ($success): ?>
<div class="alert alert-success"><?php echo $success; ?></div>
<?php elseif ($error): ?>
<div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<form method="POST">
<div class="mb-3">
<label for="cover_letter" class="form-label">Cover Letter</label>
<textarea class="form-control" id="cover_letter" name="cover_letter" rows="6"></textarea>
</div>
<button type="submit" class="btn btn-primary w-100">Submit Application</button>
</form>

<a href="job-details.php?id=<?php echo $job_id; ?>" class="btn btn-outline-light w-100 mt-2">Back to Job Details</a>
</div>
</div>
</section>

<footer>
<p>© 2025 City Jobs | Built for Jimma Freelancers & Businesses</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
