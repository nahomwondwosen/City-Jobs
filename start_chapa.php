<?php
session_start();
require_once "config.php";

// Chapa API Keys (replace with your real ones from dashboard.chapa.co)
$CHAPA_SECRET_KEY = "CHAPA-SECRET-KEY";

$user_id = $_SESSION['user_id'];
$amount  = $_POST['amount'] ?? 100;
$currency = $_POST['currency'] ?? "ETB";

// Unique transaction reference
$tx_ref = "CITYJOBS-" . uniqid();

// Chapa payment data
$data = [
    'amount' => $amount,
    'currency' => $currency,
    'email' => "customer@example.com", // get from user table if available
    'first_name' => "CityJobs",
    'last_name' => "User",
    'tx_ref' => $tx_ref,
    'callback_url' => "http://yourdomain.com/chapa_callback.php",
    'return_url' => "http://yourdomain.com/payment_dashboard.php",
    'customization' => [
        'title' => 'City Jobs Payment',
        'description' => 'Payment for services on City Jobs platform'
    ]
];

// Send request to Chapa
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.chapa.co/v1/transaction/initialize");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $CHAPA_SECRET_KEY",
    "Content-Type: application/json"
]);
$response = curl_exec($ch);
curl_close($ch);

$res = json_decode($response, true);

if (isset($res['status']) && $res['status'] === "success") {
    // Redirect to Chapa Checkout
    header("Location: " . $res['data']['checkout_url']);
    exit;
} else {
    echo "Error initializing Chapa payment.";
}
