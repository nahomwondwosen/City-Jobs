<?php
session_start();
require_once "config.php";

$user_id = $_SESSION['user_id'] ?? 0;
$chat_with = intval($_GET['chat_with'] ?? 0);

if ($user_id && $chat_with) {
    // Mark messages as read
    $conn->query("UPDATE messages SET is_read=1 WHERE sender_id=$chat_with AND receiver_id=$user_id");

    $stmt = $conn->prepare("SELECT * FROM messages WHERE (sender_id=? AND receiver_id=?) OR (sender_id=? AND receiver_id=?) ORDER BY created_at ASC");
    $stmt->bind_param("iiii", $user_id, $chat_with, $chat_with, $user_id);
    $stmt->execute();
    $messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    foreach($messages as $m){
        $class = $m['sender_id']==$user_id ? 'right' : 'left';
        echo '<div class="'.$class.'"><div class="message">';
        echo htmlspecialchars($m['message']);
        if($m['file']){
            echo '<br><a href="'.$m['file'].'" target="_blank" style="color:#0af;">Download</a>';
        }
        echo '</div></div>';
    }
}
?>
