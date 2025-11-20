<?php
session_start();
require_once "config.php";

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit;
}

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $user_id = $_SESSION['user_id'];
    $method_id = intval($_POST['method_id']);
    $amount = floatval($_POST['amount']);

    // Get method details
    $stmt = $conn->prepare("SELECT * FROM payment_methods WHERE id=? AND status='active' LIMIT 1");
    $stmt->bind_param("i", $method_id);
    $stmt->execute();
    $method = $stmt->get_result()->fetch_assoc();

    if(!$method){
        die("Invalid payment method");
    }

    // Generate unique reference
    $reference = uniqid("pay_");

    // Save payment as pending
    $stmt = $conn->prepare("INSERT INTO payments (user_id, amount, payment_method, status, reference) VALUES (?, ?, ?, 'pending', ?)");
    $stmt->bind_param("idss", $user_id, $amount, $method['name'], $reference);
    $stmt->execute();

    // If provider is Chapa
    if($method['provider'] === 'chapa'){
        $chapa_secret = "YOUR_CHAPA_SECRET_KEY"; // 🔑 replace with your chapa test key

        $callback_url = "http://yourdomain.com/verify_payment.php?reference=".$reference;

        $data = [
            "amount" => $amount,
            "currency" => "ETB",
            "email" => "user".$user_id."@cityjobs.com",
            "tx_ref" => $reference,
            "callback_url" => $callback_url,
            "return_url" => "http://yourdomain.com/payment_dashboard.php",
            "customization" => ["title"=>"City Jobs Payment","description"=>"Job platform payment"]
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.chapa.co/v1/transaction/initialize");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer ".$chapa_secret,
            "Content-Type: application/json"
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        $res = json_decode($response, true);

        if(isset($res['data']['checkout_url'])){
            header("Location: ".$res['data']['checkout_url']);
            exit;
        } else {
            echo "Chapa Error: ".json_encode($res);
        }
    }

    // Later: Add Telebirr API
    else {
        echo "Telebirr integration coming soon.";
    }
}
?>
