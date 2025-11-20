<?php
session_start();
require_once "config.php";

if(!isset($_SESSION['user_id'])){
    header("Location: index.php");
    exit;
}
$user_id = $_SESSION['user_id'];

// Get chat user
$chat_with = $_GET['chat_with'] ?? 0;

// Fetch current user info
$stmt = $conn->prepare("SELECT id, name, role FROM users WHERE id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$current_user = $stmt->get_result()->fetch_assoc();

// Decide dashboard link by role
$dashboard_link = "dashboard.php";
if ($current_user['role'] === 'freelancer') $dashboard_link = "freelancer_dashboard.php";
elseif ($current_user['role'] === 'employer') $dashboard_link = "employer_dashboard.php";
elseif ($current_user['role'] === 'admin') $dashboard_link = "admin_dashboard.php";

// Fetch all users with last message + unread count
$users = [];
$sql = "
SELECT u.id, u.name, u.role,
  (SELECT message FROM messages WHERE (sender_id=u.id AND receiver_id=$user_id) OR (sender_id=$user_id AND receiver_id=u.id) ORDER BY created_at DESC LIMIT 1) AS last_message,
  (SELECT MAX(created_at) FROM messages WHERE (sender_id=u.id AND receiver_id=$user_id) OR (sender_id=$user_id AND receiver_id=u.id)) AS last_time,
  (SELECT COUNT(*) FROM messages WHERE receiver_id=$user_id AND sender_id=u.id AND is_read=0) AS unread_count
FROM users u
WHERE u.id != $user_id
ORDER BY last_time DESC
";
$res = $conn->query($sql);
if($res) $users = $res->fetch_all(MYSQLI_ASSOC);

// Send message via POST
if($_SERVER['REQUEST_METHOD']=='POST' && isset($_POST['send_message'])){
    $receiver_id = intval($_POST['receiver_id']);
    $message = trim($_POST['message']);

    $file_path = null;
    if(isset($_FILES['file']) && $_FILES['file']['error']==UPLOAD_ERR_OK){
        $upload_dir = 'uploads/';
        if(!is_dir($upload_dir)) mkdir($upload_dir,0777,true);
        $file_name = time().'_'.basename($_FILES['file']['name']);
        $target = $upload_dir.$file_name;
        if(move_uploaded_file($_FILES['file']['tmp_name'],$target)) $file_path=$target;
    }

    if($receiver_id>0 && ($message!='' || $file_path!=null)){
        $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message, file) VALUES (?,?,?,?)");
        $stmt->bind_param("iiss",$user_id,$receiver_id,$message,$file_path);
        $stmt->execute();
        header("Location:?chat_with=$receiver_id");
        exit;
    }
}

// Fetch chat messages for selected user
$chat_messages = [];
$chat_user_info = [];
if($chat_with>0){
    $conn->query("UPDATE messages SET is_read=1 WHERE sender_id=$chat_with AND receiver_id=$user_id");

    $stmt = $conn->prepare("SELECT * FROM messages WHERE (sender_id=? AND receiver_id=?) OR (sender_id=? AND receiver_id=?) ORDER BY created_at ASC");
    $stmt->bind_param("iiii",$user_id,$chat_with,$chat_with,$user_id);
    $stmt->execute();
    $chat_messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $stmt = $conn->prepare("SELECT name FROM users WHERE id=?");
    $stmt->bind_param("i",$chat_with);
    $stmt->execute();
    $chat_user_info = $stmt->get_result()->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Messenger - City Jobs</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body{margin:0;font-family:Arial;background:#121212;color:#fff;}
.container{display:flex;height:100vh;}
.user-list{width:280px;background:#1f1f1f;overflow-y:auto;}
.user-list a{display:block;padding:10px;text-decoration:none;color:#fff;border-bottom:1px solid #333;}
.user-list a:hover, .user-list a.active{background:#0af;color:#000;}
.user-list small{display:block;color:#aaa;font-size:12px;}
.user-list .unread{background:#f00;color:#fff;font-size:12px;padding:2px 6px;border-radius:50%;margin-left:5px;}
.chat-window{flex:1;display:flex;flex-direction:column;}
.chat-header{padding:10px 15px;background:#1a1a1a;display:flex;justify-content:space-between;align-items:center;}
#chat-box{flex:1;overflow-y:auto;padding:10px;background:#1e1e1e;}
#chat-box .left{margin-bottom:10px;text-align:left;}
#chat-box .right{margin-bottom:10px;text-align:right;}
#chat-box .message{display:inline-block;padding:8px 12px;border-radius:12px;max-width:70%;}
#chat-box .left .message{background:#2a2a2a;color:#fff;}
#chat-box .right .message{background:#0af;color:#000;}
#message-form{display:flex;padding:10px;background:#1f1f1f;gap:5px;}
#message-form input[type=text]{flex:1;padding:10px;border-radius:8px;border:none;background:#2a2a2a;color:#fff;}
#message-form button{padding:10px 15px;border:none;border-radius:8px;background:#0af;color:#000;cursor:pointer;}
#message-form input[type=file]{color:#fff;}
.navbar, footer{background:#1a1a1a;}
</style>
</head>
<body>

<div class="container">
    <div class="user-list">
        <h4 class="p-2">Users</h4>
        <?php foreach($users as $u): ?>
            <a href="?chat_with=<?php echo $u['id']; ?>" class="<?php echo $chat_with==$u['id']?'active':''; ?>">
                <?php echo htmlspecialchars($u['name']).' ('.$u['role'].')'; ?>
                <?php if($u['unread_count']>0): ?>
                    <span class="unread"><?php echo $u['unread_count']; ?></span>
                <?php endif; ?>
                <?php if($u['last_message']): ?>
                    <small><?php echo htmlspecialchars(substr($u['last_message'],0,30)); ?>...</small>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="chat-window">
        <?php if($chat_with>0): ?>
        <div class="chat-header">
            <h5><?php echo htmlspecialchars($chat_user_info['name']); ?></h5>
            <li class="nav-item"><a href="payment_dashboard.php" class="nav-link">💳 Payment</a></li>
            <a href="<?php echo $dashboard_link; ?>" class="btn btn-sm btn-secondary">⬅ Dashboard</a>
        </div>
        <div id="chat-box">
            <?php foreach($chat_messages as $m): ?>
                <div class="<?php echo $m['sender_id']==$user_id?'right':'left'; ?>">
                    <div class="message">
                        <?php echo htmlspecialchars($m['message']); ?>
                        <?php if($m['file']): ?>
                            <br><a href="<?php echo $m['file']; ?>" target="_blank" style="color:#0af;">Download</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <form id="message-form" method="POST" enctype="multipart/form-data">
            <input type="text" name="message" placeholder="Type a message..." autocomplete="off">
            <input type="file" name="file">
            <input type="hidden" name="receiver_id" value="<?php echo $chat_with; ?>">
            <button type="submit" name="send_message">Send</button>
        </form>
        <?php else: ?>
            <div class="d-flex flex-grow-1 justify-content-center align-items-center text-muted">Select a user to start chat</div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
// Auto-scroll chat to bottom
function scrollChat() {
    var chatBox = document.getElementById('chat-box');
    if(chatBox) chatBox.scrollTop = chatBox.scrollHeight;
}
scrollChat();

// Notification integration
var lastMessageCount = <?php echo count($chat_messages); ?>;

setInterval(function(){
    var chat_with = <?php echo intval($chat_with); ?>;
    if(chat_with>0){
        $.get("fetch-messages.php?chat_with="+chat_with, function(data){
            var chatBox = $("#chat-box");
            var newMessageCount = $(data).filter('.right, .left').length;

            if(newMessageCount > lastMessageCount){
                var sound = new Audio('assets/sounds/notification.mp3'); // notification sound
                sound.play();
                document.title = "💬 New Message!";
            } else {
                document.title = "Messenger - City Jobs";
            }

            chatBox.html(data);
            chatBox.scrollTop(chatBox[0].scrollHeight);
            lastMessageCount = newMessageCount;
        });
    }
}, 3000);
</script>

</body>
</html>
