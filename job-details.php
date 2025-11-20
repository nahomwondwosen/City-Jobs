<?php
require_once "config.php"; // your database connection

// Get job ID from URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: jobs.php");
    exit;
}

$job_id = intval($_GET['id']);
$sql = "SELECT j.*, u.name AS employer 
        FROM jobs j 
        JOIN users u ON j.employer_id = u.id 
        WHERE j.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $job_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "Job not found.";
    exit;
}

$job = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($job['title']); ?> - City Jobs</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body {
      background-color: #0e0e0e;
      color: #f8f9fa;
      font-family: 'Poppins', sans-serif;
    }
    .navbar {
      background-color: #1a1a1a;
    }
    .card {
      background-color: #1f1f1f;
      border: none;
    }
    .btn-primary {
      background-color: #0d6efd;
      border: none;
    }
    footer {
      background-color: #1a1a1a;
      padding: 30px 0;
      text-align: center;
    }
    a {
      text-decoration: none;
      color: #f8f9fa;
    }
    a:hover {
      color: #0d6efd;
    }
  </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark">
  <div class="container">
    <a class="navbar-brand fw-bold" href="index.html">City Jobs</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navMenu">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a href="index.html" class="nav-link">Home</a></li>
        <li class="nav-item"><a href="jobs.php" class="nav-link active">Find Jobs</a></li>
        <li class="nav-item"><a href="post-job.php" class="nav-link">Post Job</a></li>
        <li class="nav-item"><a href="login.php" class="nav-link">Login</a></li>
      </ul>
    </div>
  </div>
</nav>

<!-- Job Details -->
<section class="py-5">
  <div class="container">
    <div class="card p-4">
      <h2 class="fw-bold text-primary"><?php echo htmlspecialchars($job['title']); ?></h2>
      <h5 class="text-muted mb-3"><?php echo htmlspecialchars($job['category']); ?></h5>
      <p class="small text-secondary mb-1"><i class="bi bi-person-circle"></i> Employer: <?php echo htmlspecialchars($job['employer']); ?></p>
      <p class="small text-secondary mb-3"><i class="bi bi-clock"></i> Posted on: <?php echo date("M d, Y", strtotime($job['created_at'])); ?></p>
      <hr>
      <h5 class="fw-bold">Job Description</h5>
      <p><?php echo nl2br(htmlspecialchars($job['description'])); ?></p>
      <a href="apply-job.php?job_id=<?php echo $job['id']; ?>" class="btn btn-primary mt-3">Apply Now</a>
      <a href="jobs.php" class="btn btn-outline-light mt-3 ms-2">Back to Jobs</a>
    </div>
  </div>
</section>

<!-- Footer -->
<footer>
  <p>© 2025 City Jobs | Built for Jimma Freelancers & Businesses</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
