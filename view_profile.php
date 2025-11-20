<?php
session_start();
require_once "config.php";

// Ensure logged in
if(!isset($_SESSION['user_id'])){
    header("Location: index.php");
    exit;
}

$viewer_id = $_SESSION['user_id'];
$viewer_role = $_SESSION['role'];

// Get profile ID
if(!isset($_GET['id']) || !is_numeric($_GET['id'])){
    die("Invalid user ID.");
}

$profile_id = intval($_GET['id']);

// Fetch profile user
$stmt = $conn->prepare("SELECT id, name, contact, profile_pic, role, created_at FROM users WHERE id=? LIMIT 1");
$stmt->bind_param("i",$profile_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if(!$user){
    die("User not found.");
}

$profile_pic = $user['profile_pic'] ? $user['profile_pic'] : 'default.png';

// --- Handle sending message (Employer -> Freelancer)
$msg = "";
if($_SERVER['REQUEST_METHOD'] == 'POST'){
    if($viewer_role == 'employer' && $user['role'] == 'freelancer' && isset($_POST['send_message'])){
        $message_text = trim($_POST['message']);
        if($message_text != ""){
            $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?,?,?)");
            $stmt->bind_param("iis", $viewer_id, $profile_id, $message_text);
            if($stmt->execute()){
                $msg = "✅ Message sent successfully!";
            } else {
                $msg = "❌ Failed to send message.";
            }
        }
    }
}

// --- Handle applying to employer job (Freelancer -> Employer)
if($viewer_role == 'freelancer' && $user['role'] == 'employer' && isset($_POST['apply_job'])){
    $job_id = intval($_POST['job_id']);
    $stmt = $conn->prepare("INSERT INTO applications (freelancer_id, job_id, status) VALUES (?,?,?)");
    $status = "pending";
    $stmt->bind_param("iis", $viewer_id, $job_id, $status);
    if($stmt->execute()){
        $msg = "✅ Applied to job successfully!";
    } else {
        $msg = "❌ Failed to apply.";
    }
}

// Fetch employer jobs (if viewing employer)
$jobs = [];
if($user['role'] == 'employer'){
    $stmt = $conn->prepare("SELECT id, title, description, status FROM jobs WHERE employer_id=?");
    $stmt->bind_param("i",$profile_id);
    $stmt->execute();
    $jobs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?php echo htmlspecialchars($user['name']); ?> - City Jobs</title>
<style>
body{font-family:Arial,sans-serif;background:#121212;color:#fff;margin:0;padding:0;}
.container{max-width:700px;margin:30px auto;padding:20px;background:#1e1e1e;border-radius:10px;}
.profile-pic{width:150px;height:150px;border-radius:50%;object-fit:cover;margin-bottom:15px;border:2px solid #0af;}
h2{color:#0af;text-align:center;}
.info{margin-bottom:10px;}
button, input, textarea, select{padding:10px;margin-top:10px;border:none;border-radius:5px;width:100%;}
button{background:#0af;color:#fff;cursor:pointer;}
button:hover{background:#08c;}
.job-card{background:#2a2a2a;padding:15px;margin-bottom:15px;border-radius:8px;}
.message-box{margin-top:15px;}
.message-success{color:#0f0;text-align:center;}
</style>
</head>
<body>

<div class="container">
    <div style="text-align:center;">
        <img src="<?php echo htmlspecialchars($profile_pic); ?>" class="profile-pic" alt="Profile Picture">
        <h2><?php echo htmlspecialchars($user['name']); ?></h2>
    </div>

    <?php if($msg): ?>
        <p class="message-success"><?php echo $msg; ?></p>
    <?php endif; ?>

    <div class="info"><strong>Contact / Email:</strong> <?php echo htmlspecialchars($user['contact']); ?></div>
    <div class="info"><strong>Role:</strong> <?php echo htmlspecialchars($user['role']); ?></div>
    <div class="info"><strong>Joined:</strong> <?php echo $user['created_at']; ?></div>

    <?php if($viewer_role=='employer' && $user['role']=='freelancer'): ?>
        <!-- Employer sends message to freelancer -->
        <div class="message-box">
            <h3>Send Message to Freelancer</h3>
            <form method="post">
                <textarea name="message" placeholder="Type your message..." required></textarea>
                <button type="submit" name="send_message">Send Message</button>
            </form>
        </div>

    <?php elseif($viewer_role=='freelancer' && $user['role']=='employer'): ?>
        <!-- Freelancer applies to employer jobs -->
        <div class="jobs-section">
            <h3>Available Jobs from <?php echo htmlspecialchars($user['name']); ?></h3>
            <?php if(count($jobs)>0): ?>
                <?php foreach($jobs as $job): ?>
                    <div class="job-card">
                        <h4><?php echo htmlspecialchars($job['title']); ?> (<?php echo $job['status']; ?>)</h4>
                        <p><?php echo nl2br(htmlspecialchars($job['description'])); ?></p>
                        <form method="post">
                            <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                            <button type="submit" name="apply_job">Apply</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No jobs available.</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <a href="employer_dashboard.php"><button>← Back to Dashboard</button></a>
</div>

</body>
</html>
