<?php
session_start();
require_once "config.php"; // must set $conn (mysqli) there

// ----------------- DEBUG SETTINGS -----------------
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Make mysqli throw exceptions for easier transaction handling
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// ----------------- BASIC ACCESS CHECK -----------------
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    die("Access denied. You must be an admin.");
}

// ----------------- HELPER: check and create columns if missing -----------------
function ensure_column_exists(mysqli $conn, string $table, string $column, string $definition) {
    $res = $conn->query("SHOW COLUMNS FROM `$table` LIKE '{$column}'");
    if ($res === false) {
        throw new Exception("Table `$table` does not exist or SHOW COLUMNS failed: " . $conn->error);
    }
    if ($res->num_rows === 0) {
        // add column
        $sql = "ALTER TABLE `$table` ADD COLUMN {$column} {$definition}";
        if (!$conn->query($sql)) {
            throw new Exception("Failed to add column `$column` to `$table`: " . $conn->error);
        }
    }
}

// ----------------- ENSURE SCHEMA NEEDED FOR OPTION 2 -----------------
try {
    // Ensure payments table exists (basic minimal structure)
    $paymentsExists = $conn->query("SHOW TABLES LIKE 'payments'")->num_rows > 0;
    if (!$paymentsExists) {
        // Create minimal payments table that matches Option 2 usage
        $createPayments = "
            CREATE TABLE `payments` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `user_id` INT NOT NULL,
                `amount` DECIMAL(10,2) NOT NULL,
                `status` ENUM('pending','approved','failed') DEFAULT 'pending',
                `payment_method` VARCHAR(100),
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX (`user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ";
        if (!$conn->query($createPayments)) {
            throw new Exception("Failed to create `payments` table: " . $conn->error);
        }
    }

    // Ensure users table has balance column
    $usersExists = $conn->query("SHOW TABLES LIKE 'users'")->num_rows > 0;
    if (!$usersExists) {
        throw new Exception("`users` table does not exist. Create it first.");
    }
    ensure_column_exists($conn, 'users', 'balance', "DECIMAL(10,2) NOT NULL DEFAULT 0");

} catch (Exception $e) {
    // Stop here with a clear message
    die("Schema check error: " . htmlspecialchars($e->getMessage()));
}

// ----------------- HANDLE FORM SUBMISSION -----------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Basic validation
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;

    if ($user_id <= 0 || $amount <= 0) {
        echo "<script>alert('Please select a valid user and an amount greater than 0.');</script>";
    } else {
        try {
            // Start transaction
            $conn->begin_transaction();

            // 1) Insert into payments
            $stmtIns = $conn->prepare("INSERT INTO payments (user_id, amount, status, payment_method, created_at) VALUES (?, ?, ?, ?, NOW())");
            $status = 'approved';
            $payment_method = 'Admin Credit';
            $stmtIns->bind_param("idss", $user_id, $amount, $status, $payment_method);
            $ok1 = $stmtIns->execute();
            $stmtIns->close();

            if (!$ok1) {
                throw new Exception("Failed to insert payment record.");
            }

            // 2) Update user's balance
            $stmtUp = $conn->prepare("UPDATE users SET balance = COALESCE(balance,0) + ? WHERE id = ?");
            $stmtUp->bind_param("di", $amount, $user_id);
            $ok2 = $stmtUp->execute();
            $affected = $stmtUp->affected_rows;
            $stmtUp->close();

            if (!$ok2 || $affected === 0) {
                // If affected_rows is 0, either the user id doesn't exist or balance remained same.
                throw new Exception("Failed to update user balance (user not found?). affected_rows={$affected}");
            }

            // Commit
            $conn->commit();

            echo "<script>alert('💰 Money added and balance updated successfully!');window.location='payment_dashboard.php';</script>";
            exit;

        } catch (Exception $e) {
            // Rollback and show clear error
            if ($conn->in_transaction) {
                $conn->rollback();
            }
            $msg = "Transaction failed: " . $e->getMessage() . " (MySQL error: " . $conn->error . ")";
            echo "<div style='background:#fee;padding:15px;color:#900;border:1px solid #900;margin:12px;'>$msg</div>";
        }
    }
} // end POST

// ----------------- FETCH USERS -----------------
$users = $conn->query("SELECT id, name FROM users WHERE role != 'admin' OR role IS NULL ORDER BY name ASC");
if ($users === false) {
    die("Failed to fetch users: " . htmlspecialchars($conn->error));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Add Money | Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { background:#121212; color:#fff; font-family: Arial, sans-serif; }
.container { max-width:520px; margin:60px auto; background:#1e1e1e; padding:24px; border-radius:12px; }
h2 { color:#0af; text-align:center; margin-bottom:20px; }
select,input { width:100%; padding:10px; margin-bottom:12px; border-radius:6px; border:none; background:#2a2a2a; color:#fff; }
button { width:100%; padding:10px; border-radius:6px; border:none; background:#0af; color:#000; font-weight:bold; }
a.btn-info { display:block; text-align:center; margin-top:12px; }
.debug { margin-top:10px; padding:10px; background:#222; border-radius:6px; color:#ddd; font-size:13px; }
</style>
</head>
<body>
<div class="container">
    <h2>💰 Add Money to User</h2>
    <form method="post">
        <select name="user_id" required>
            <option value="">-- Select User --</option>
            <?php while ($u = $users->fetch_assoc()): ?>
                <option value="<?= htmlspecialchars($u['id']) ?>"><?= htmlspecialchars($u['name']) ?> (ID: <?= htmlspecialchars($u['id']) ?>)</option>
            <?php endwhile; ?>
        </select>

        <input type="number" step="0.01" name="amount" placeholder="Enter Amount (e.g. 50.00)" required>
        <button type="submit">Add Money</button>
    </form>

    <a href="payment_dashboard.php" class="btn btn-info">← Back to Dashboard</a>

    <div class="debug">
        <strong>Debug info</strong><br>
        Server time: <?= date('Y-m-d H:i:s') ?><br>
        DB connection: <?= ($conn->ping() ? 'OK' : 'NOT OK') ?><br>
    </div>
</div>
</body>
</html>
