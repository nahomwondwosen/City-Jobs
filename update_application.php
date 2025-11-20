<?php
session_start();
require_once "config.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employer') {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['application_id'], $_POST['action'])) {
    $application_id = intval($_POST['application_id']);
    $action = $_POST['action'];

    if ($action === 'accept') {
        $status = 'accepted';
    } elseif ($action === 'reject') {
        $status = 'rejected';
    } else {
        $status = 'pending';
    }

    $stmt = $conn->prepare("UPDATE applications SET status=? WHERE id=?");
    $stmt->bind_param("si", $status, $application_id);
    $stmt->execute();
}

header("Location: employer_dashboard.php");
exit;
