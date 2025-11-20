<?php
require_once "config.php";

if(!isset($_GET['reference'])){
    die("No reference supplied");
}

$reference = $_GET['reference'];
$chapa_secret = "YOUR_CHAPA_SECRET_KEY"; // same secret key

// Verify from Chapa API
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.chapa.co/v1/transaction/verify/".$reference);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer ".$chapa_secret
]);
$response = curl_exec($ch);
curl_close($ch);

$res = json_decode($response, true);

if(isset($res['data']['status']) && $res['data']['status'] === "success"){
    // Mark payment as paid
    $stmt = $conn->prepare("UPDATE payments SET status='paid', updated_at=NOW() WHERE reference=?");
    $stmt->bind_param("s", $reference);
    $stmt->execute();
    echo "✅ Payment successful!";
} else {
    // Mark as failed
    $stmt = $conn->prepare("UPDATE payments SET status='failed', updated_at=NOW() WHERE reference=?");
    $stmt->bind_param("s", $reference);
    $stmt->execute();
    echo "❌ Payment failed!";
}
?>
