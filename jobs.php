<?php
require_once "config.php"; // DB connection

$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
$location = isset($_GET['location']) ? trim($_GET['location']) : '';

$sql = "SELECT * FROM jobs WHERE 1";

$params = [];
$types = "";

if ($keyword !== '') {
    $sql .= " AND (title LIKE ? OR description LIKE ?)";
    $params[] = "%$keyword%";
    $params[] = "%$keyword%";
    $types .= "ss";
}
if ($location !== '') {
    $sql .= " AND location LIKE ?";
    $params[] = "%$location%";
    $types .= "s";
}

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>City Jobs | Find Jobs</title>
  
  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  
  <style>
    body {
      background-color: #0e0e0e;
      color: #f8f9fa;
      font-family: 'Poppins', sans-serif;
    }

    .navbar {
      background-color: #141414;
    }

    .navbar-brand {
      color: #0d6efd !important;
      font-weight: 700;
    }

    .nav-link {
      color: #ccc !important;
    }
    .nav-link:hover {
      color: #0d6efd !important;
    }

    .search-section {
      background: #101010;
      padding: 60px 0;
      text-align: center;
    }

    .search-section h2 {
      color: #fff;
      margin-bottom: 30px;
      font-weight: 700;
    }

    .card {
      background-color: #1a1a1a;
      border: none;
      border-radius: 10px;
      transition: 0.3s ease;
    }

    .card:hover {
      transform: translateY(-5px);
      box-shadow: 0 0 10px rgba(13, 110, 253, 0.4);
    }

    .btn-primary {
      background-color: #0d6efd;
      border: none;
    }

    .btn-primary:hover {
      background-color: #0b57d0;
    }

    footer {
      background-color: #141414;
      padding: 40px 0;
      text-align: center;
      color: #aaa;
      margin-top: 60px;
    }
  </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark shadow-sm">
  <div class="container">
    <a class="navbar-brand" href="index.html">CITY JOBS</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navMenu">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a href="index.html" class="nav-link">Home</a></li>
        <li class="nav-item"><a href="jobs.php" class="nav-link active">Find Jobs</a></li>
        <li class="nav-item"><a href="login.php" class="nav-link">Post Job</a></li>
        <li class="nav-item"><a href="login.php" class="nav-link">Login</a></li>
      </ul>
    </div>
  </div>
</nav>

<!-- Search Section -->
<section class="search-section">
  <div class="container">
    <h2>Search Jobs in Jimma</h2>
    <form class="row g-2 justify-content-center" method="GET" action="jobs.php">
      <div class="col-md-5">
        <input type="text" name="keyword" class="form-control" placeholder="Job title or keyword" value="<?php echo htmlspecialchars($keyword); ?>">
      </div>
      <div class="col-md-3">
        <input type="text" name="location" class="form-control" placeholder="Location" value="<?php echo htmlspecialchars($location); ?>">
      </div>
      <div class="col-md-2">
        <button class="btn btn-primary w-100">Search</button>
      </div>
    </form>
  </div>
</section>

<!-- Jobs List -->
<section class="py-5">
  <div class="container">
    <h3 class="mb-4 text-center text-light">Available Jobs</h3>
    <div class="row">
      <?php if ($result->num_rows > 0): ?>
        <?php while ($job = $result->fetch_assoc()): ?>
          <div class="col-md-4 mb-4">
            <div class="card p-4">
              <h5 class="text-primary mb-2"><?php echo htmlspecialchars($job['title']); ?></h5>
              <p class="text-muted mb-1"><i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($job['location']); ?></p>
              <p class="small"><?php echo htmlspecialchars(substr($job['description'], 0, 100)); ?>...</p>
              <a href="job-details.php?id=<?php echo $job['id']; ?>" class="btn btn-outline-primary mt-2">View Details</a>
            </div>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <div class="col-12 text-center">
          <p class="text-muted mt-4">No jobs found. Try another search term.</p>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- Footer -->
<footer>
  <div class="container">
    <p>© 2025 City Jobs — Connecting youth, freelancers, and businesses in Jimma.</p>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
