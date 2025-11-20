<?php
session_start();
require_once "config.php";

// Make sure user is logged in
if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit;
}

// Get rated_user_id from URL
$rated_user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

// Prevent rating yourself
if($rated_user_id == $_SESSION['user_id']){
    die("You cannot rate yourself.");
}

// Handle form submission
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $rating = (int)$_POST['rating'];
    $comment = trim($_POST['comment']);

    // Check if user already rated
    $check = $conn->prepare("SELECT id FROM ratings WHERE rated_user_id=? AND rater_user_id=? LIMIT 1");
    $check->bind_param("ii", $rated_user_id, $_SESSION['user_id']);
    $check->execute();
    $check->store_result();

    if($check->num_rows > 0){
        $error = "You have already rated this user.";
    } else {
        $stmt = $conn->prepare("INSERT INTO ratings (rated_user_id, rater_user_id, rating, comment) VALUES (?,?,?,?)");
        $stmt->bind_param("iiis", $rated_user_id, $_SESSION['user_id'], $rating, $comment);
        if($stmt->execute()){
            $success = "Rating submitted successfully!";
        } else {
            $error = "Error submitting rating: " . $stmt->error;
        }
    }
}

// Fetch all ratings for the user
$stmt2 = $conn->prepare("SELECT r.*, u.name AS rater_name FROM ratings r JOIN users u ON r.rater_user_id = u.id WHERE r.rated_user_id=? ORDER BY r.created_at DESC");
$stmt2->bind_param("i", $rated_user_id);
$stmt2->execute();
$result = $stmt2->get_result();
$ratings = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Ratings - City Jobs</title>
<link rel="stylesheet" href="styles.css">
<style>
.rating-container {
    max-width: 700px;
    margin: 50px auto;
    background: #1e1e1e;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.5);
}
.rating-container h2 { margin-bottom: 15px; }
.rating-container form { margin-bottom: 30px; }
.rating-container select, .rating-container textarea {
    width: 100%;
    padding: 12px;
    margin: 8px 0;
    border-radius: 8px;
    border: none;
}
.rating-container button {
    padding: 12px 20px;
    background: #0af;
    border: none;
    border-radius: 8px;
    color: #fff;
    font-weight: bold;
    cursor: pointer;
    transition: 0.3s;
}
.rating-container button:hover { background: #08c; }
.rating-item {
    border-bottom: 1px solid #333;
    padding: 10px 0;
}
.rating-item p { margin: 5px 0; color: #ccc; }
.success { color: #0f0; }
.error { color: #f00; }
</style>
</head>
<body>

<header>
    <div class="logo">City Jobs</div>
</header>

<div class="rating-container">
    <h2>Rate User #<?php echo $rated_user_id; ?></h2>

    <?php if(isset($error)) echo "<div class='error'>$error</div>"; ?>
    <?php if(isset($success)) echo "<div class='success'>$success</div>"; ?>

    <form method="POST">
        <label for="rating">Rating (1 to 5):</label>
        <select name="rating" id="rating" required>
            <option value="">Select rating</option>
            <option value="1">1 - Very Bad</option>
            <option value="2">2 - Bad</option>
            <option value="3">3 - Average</option>
            <option value="4">4 - Good</option>
            <option value="5">5 - Excellent</option>
        </select>

        <label for="comment">Comment:</label>
        <textarea name="comment" id="comment" placeholder="Write your feedback..." required></textarea>

        <button type="submit">Submit Rating</button>
    </form>

    <h3>All Ratings for this user:</h3>
    <?php if(count($ratings) > 0): ?>
        <?php foreach($ratings as $r): ?>
            <div class="rating-item">
                <p><strong><?php echo htmlspecialchars($r['rater_name']); ?></strong> rated: <?php echo $r['rating']; ?>/5</p>
                <p><?php echo htmlspecialchars($r['comment']); ?></p>
                <small style="color:#888;"><?php echo $r['created_at']; ?></small>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>No ratings yet.</p>
    <?php endif; ?>
</div>

</body>
</html>
