<?php
session_start();
require_once "config.php";

// ensure employer is logged in
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employer'){
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// fetch employer profile
$stmt = $conn->prepare("SELECT name, profile_pic FROM users WHERE id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$employer = $stmt->get_result()->fetch_assoc();
$employer_name = $employer['name'] ?? "Employer";
$employer_pic  = $employer['profile_pic'] ?? "default.png";

// fetch freelancers
$stmt = $conn->prepare("SELECT id, name, role FROM users WHERE role='freelancer' LIMIT 10");
$stmt->execute();
$freelancers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// demo notifications
$notifications = [
    ["text" => "Freelancer applied to Job #7", "time" => "3h ago"],
    ["text" => "Payment pending for Job #5", "time" => "2d ago"]
];

$employer_balance = 420.50;
$employer_rating = 4.9;

// handle job post
$msg = "";
if($_SERVER['REQUEST_METHOD']=="POST" && isset($_POST['post_job'])){
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $category = trim($_POST['category']);
    $budget = floatval($_POST['budget']);

    $stmt = $conn->prepare("INSERT INTO jobs (employer_id, title, description, category, budget) VALUES (?,?,?,?,?)");
    $stmt->bind_param("isssd", $user_id, $title, $description, $category, $budget);
    if($stmt->execute()){
        $msg = "<p style='color:green;'>✅ Job posted successfully!</p>";
    } else {
        $msg = "<p style='color:red;'>❌ Error posting job.</p>";
    }
}

// handle rating
if($_SERVER['REQUEST_METHOD']=="POST" && isset($_POST['rate_user'])){
    $freelancer_id = intval($_POST['freelancer_id']);
    $rating = intval($_POST['rating']);
    if($rating >=1 && $rating <=5){
        $stmt = $conn->prepare("INSERT INTO ratings (rater_id, rated_id, rating) VALUES (?,?,?)");
        $stmt->bind_param("iii", $user_id, $freelancer_id, $rating);
        $stmt->execute();
        $msg = "<p style='color:green;'>⭐ You rated this freelancer $rating/5</p>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Employer Dashboard - City Jobs</title>
<style>
body { font-family: Arial, sans-serif; background:#121212; color:#fff; margin:0; }
header { display:flex; justify-content:space-between; align-items:center; padding:15px 25px; background:#1f1f1f; }
header .logo { font-size:20px; font-weight:bold; }
header .nav { display:flex; gap:20px; align-items:center; }
header .nav div { position:relative; cursor:pointer; }
header .badge { position:absolute; top:-5px; right:-10px; background:red; color:#fff; font-size:12px; padding:2px 6px; border-radius:50%; }
header .profile { display:flex; align-items:center; gap:10px; }
header .profile img { width:35px; height:35px; border-radius:50%; }

.container { padding:20px; }
.section { margin-bottom:40px; }
.freelancer-card { background:#1e1e1e; padding:15px; margin-bottom:15px; border-radius:8px; }
.freelancer-card h3 { margin:0 0 10px; }
.rating { color:gold; }

.modal { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.7); justify-content:center; align-items:center; }
.modal-content { background:#1e1e1e; padding:20px; border-radius:8px; width:400px; }
.modal-content input, .modal-content textarea { width:100%; margin-bottom:10px; padding:8px; border:none; border-radius:4px; }
.modal-content button { background:#0af; border:none; padding:10px 15px; border-radius:5px; cursor:pointer; }
.modal-content button:hover { background:#08c; }
</style>
</head>
<body>

<header>
  <div class="logo">City Jobs</div>
  <div class="nav">
    <div><a href="messages.php" style="color:#fff;">💬 Messages <span class="badge">4</span></a></div>
    <div><a href="notifications.php" style="color:#fff;">🔔 Notifications <span class="badge"><?php echo count($notifications); ?></span></a></div>
    <div><a href="balance.php" style="color:#fff;">💳 Balance: $<?php echo number_format($employer_balance,2); ?></a></div>
    <div><a href="ratings.php" style="color:#fff;">⭐ <?php echo $employer_rating; ?>/5</a></div>
    <div onclick="openModal()" style="color:#0af;">➕ Post Job</div>
    <div class="profile">
        <img src="uploads/<?php echo htmlspecialchars($employer_pic); ?>" alt="Profile">
        <span><?php echo htmlspecialchars($employer_name); ?></span>
    </div>
    <a href="logout.php" style="color:#f55;">Logout</a>
  </div>
</header>

<div class="container">
  <?php echo $msg; ?>
  <div class="section">
    <h2>Available Freelancers</h2>
    <?php foreach($freelancers as $f): ?>
      <div class="freelancer-card">
        <h3><?php echo htmlspecialchars($f['name']); ?></h3>
        <p>Role: <?php echo $f['role']; ?></p>
        <p>⭐ 4.5 / 5</p>

        <!-- Rating Form -->
        <form method="POST">
          <input type="hidden" name="freelancer_id" value="<?php echo $f['id']; ?>">
          <select name="rating">
            <option value="1">⭐ 1</option>
            <option value="2">⭐ 2</option>
            <option value="3">⭐ 3</option>
            <option value="4">⭐ 4</option>
            <option value="5">⭐ 5</option>
          </select>
          <button type="submit" name="rate_user">Rate</button>
        </form>

        <a href="profile.php?id=<?php echo $f['id']; ?>" style="color:#0af;">View Profile</a>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- Job Post Modal -->
<div id="jobModal" class="modal">
  <div class="modal-content">
    <h2>Post a Job</h2>
    <form method="POST">
      <input type="text" name="title" placeholder="Job Title" required>
      <textarea name="description" placeholder="Job Description" required></textarea>
      <input type="text" name="category" placeholder="Category">
      <input type="number" step="0.01" name="budget" placeholder="Budget" required>
      <button type="submit" name="post_job">Post Job</button>
      <button type="button" onclick="closeModal()">Cancel</button>
    </form>
  </div>
</div>

<script>
function openModal(){ document.getElementById('jobModal').style.display='flex'; }
function closeModal(){ document.getElementById('jobModal').style.display='none'; }
</script>

</body>
</html>
