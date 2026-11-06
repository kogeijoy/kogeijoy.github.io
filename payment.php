<?php
session_start();
require_once "db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$isAdmin = (($_SESSION['role'] ?? '') === 'admin');

$reservation_id = (int)(
    $_POST['reservation_id'] ??
    $_GET['reservation_id'] ??
    0
);

$error = "";
$success = "";

// Load the reservation.
// Customers can only access their own reservations.
if ($isAdmin) {

    $stmt = $conn->prepare(
        "SELECT b.*, u.full_name, r.room_type, r.room_number
         FROM reservations b
         JOIN users u ON u.id = b.user_id
         JOIN rooms r ON r.id = b.room_id
         WHERE b.id = ?"
    );

    $stmt->bind_param("i", $reservation_id);

} else {

    $stmt = $conn->prepare(
        "SELECT b.*, u.full_name, r.room_type, r.room_number
         FROM reservations b
         JOIN users u ON u.id = b.user_id
         JOIN rooms r ON r.id = b.room_id
         WHERE b.id = ? AND b.user_id = ?"
    );

    $stmt->bind_param("ii", $reservation_id, $user_id);
}

$stmt->execute();
$reservation = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$reservation) {
    http_response_code(404);
    die("Reservation not found or you do not have permission to access it.");
}

// Calculate the amount already paid.
$paidStmt = $conn->prepare(
    "SELECT COALESCE(SUM(amount), 0) AS paid
     FROM payments
     WHERE reservation_id = ?
     AND payment_status = 'paid'"
);

$paidStmt->bind_param("i", $reservation_id);
$paidStmt->execute();

$alreadyPaid = (float)$paidStmt->get_result()->fetch_assoc()['paid'];
$paidStmt->close();

$balance = max(0, (float)$reservation['total_amount'] - $alreadyPaid);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $method = $_POST['payment_method'] ?? '';
    $reference = trim($_POST['transaction_reference'] ?? '');

    if ($balance <= 0) {

        $error = "This reservation has already been fully paid.";

    } elseif (!in_array($method, ['cash', 'mpesa', 'card', 'bank'], true)) {

        $error = "Select a valid payment method.";

    } elseif ($isAdmin) {

        $amount = (float)($_POST['amount'] ?? 0);
        $paymentStatus = $_POST['payment_status'] ?? 'pending';

        if ($amount <= 0 || $amount > $balance) {
            $error = "Enter an amount greater than zero and not exceeding the outstanding balance.";
        } elseif (!in_array($paymentStatus, ['pending', 'paid', 'failed'], true)) {
            $error = "Select a valid payment status.";
        } else {

            $paymentDate = $paymentStatus === 'paid' ? date('Y-m-d H:i:s') : null;

            $insert = $conn->prepare(
                "INSERT INTO payments
                (reservation_id, amount, payment_method,
                 transaction_reference, payment_status, payment_date)
                VALUES (?, ?, ?, ?, ?, ?)"
            );

            $insert->bind_param(
                "idssss",
                $reservation_id,
                $amount,
                $method,
                $reference,
                $paymentStatus,
                $paymentDate
            );

            if ($insert->execute()) {
                header("Location: payment.php?reservation_id=" . $reservation_id . "&saved=1");
                exit;
            } else {
                $error = "Could not save the payment.";
            }

            $insert->close();
        }

    } else {

        // Customer payment submission is recorded as pending.
        // This does not confirm an actual M-Pesa or card transaction.
        $amount = (float)($_POST['amount'] ?? 0);

        if ($amount <= 0 || $amount > $balance) {
            $error = "Enter an amount greater than zero and not exceeding the outstanding balance.";
        } else {

            $paymentStatus = 'pending';
            $paymentDate = null;

            $insert = $conn->prepare(
                "INSERT INTO payments
                (reservation_id, amount, payment_method,
                 transaction_reference, payment_status, payment_date)
                VALUES (?, ?, ?, ?, ?, ?)"
            );

            $insert->bind_param(
                "idssss",
                $reservation_id,
                $amount,
                $method,
                $reference,
                $paymentStatus,
                $paymentDate
            );

            if ($insert->execute()) {
                header("Location: payment.php?reservation_id=" . $reservation_id . "&submitted=1");
                exit;
            } else {
                $error = "Could not submit the payment details.";
            }

            $insert->close();
        }
    }
}

// Refresh payment totals after saving or loading the page.
$paidStmt = $conn->prepare(
    "SELECT COALESCE(SUM(amount), 0) AS paid
     FROM payments
     WHERE reservation_id = ?
     AND payment_status = 'paid'"
);

$paidStmt->bind_param("i", $reservation_id);
$paidStmt->execute();
$alreadyPaid = (float)$paidStmt->get_result()->fetch_assoc()['paid'];
$paidStmt->close();

$balance = max(0, (float)$reservation['total_amount'] - $alreadyPaid);

// Payment history
$historyStmt = $conn->prepare(
    "SELECT * FROM payments
     WHERE reservation_id = ?
     ORDER BY created_at DESC"
);

$historyStmt->bind_param("i", $reservation_id);
$historyStmt->execute();
$payments = $historyStmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservation Payment</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header class="navbar">
    <div class="container">

        <a href="index.php" class="logo">ROYAL HAVEN</a>

        <nav>
            <?php if ($isAdmin): ?>
                <a href="admin_dashboard.php">Dashboard</a>
                <a href="manage_bookings.php">Bookings</a>
            <?php else: ?>
                <a href="index.php">Home</a>
                <a href="my_bookings.php">My Bookings</a>
            <?php endif; ?>

            <a href="logout.php">Logout</a>
        </nav>

    </div>
</header>

<main class="content-section">
    <div class="container">

        <h1>Reservation Payment</h1>

        <?php if (isset($_GET['saved'])): ?>
            <p class="message">Payment record saved successfully.</p>
        <?php endif; ?>

        <?php if (isset($_GET['submitted'])): ?>
            <p class="message">
                Your payment details have been submitted for verification.
            </p>
        <?php endif; ?>

        <?php if ($error): ?>
            <p class="message error">
                <?php echo htmlspecialchars($error); ?>
            </p>
        <?php endif; ?>

        <div class="panel">

            <h2>Reservation Details</h2>

            <p>
                Customer:
                <?php echo htmlspecialchars($reservation['full_name']); ?>
            </p>

            <p>
                Room:
                <?php echo htmlspecialchars($reservation['room_type']); ?>
                (<?php echo htmlspecialchars($reservation['room_number']); ?>)
            </p>

            <p>
                Reservation Total:
                KSh <?php echo number_format((float)$reservation['total_amount'], 2); ?>
            </p>

            <p>
                Amount Paid:
                KSh <?php echo number_format($alreadyPaid, 2); ?>
            </p>

            <h3>
                Outstanding Balance:
                KSh <?php echo number_format($balance, 2); ?>
            </h3>

        </div>

        <?php if ($balance > 0): ?>

            <form class="form-card" method="POST">

                <h2>
                    <?php echo $isAdmin ? "Record Payment" : "Submit Payment Details"; ?>
                </h2>

                <input type="hidden" name="reservation_id"
                       value="<?php echo $reservation_id; ?>">

                <div class="form-group">
                    <label>Amount (KSh)</label>

                    <input type="number"
                           name="amount"
                           min="0.01"
                           max="<?php echo htmlspecialchars((string)$balance); ?>"
                           step="0.01"
                           required>
                </div>

                <div class="form-group">
                    <label>Payment Method</label>

                    <select name="payment_method" required>
                        <option value="">Select Payment Method</option>
                        <option value="cash">Cash</option>
                        <option value="mpesa">M-Pesa</option>
                        <option value="card">Card</option>
                        <option value="bank">Bank Transfer</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Transaction Reference</label>

                    <input type="text"
                           name="transaction_reference"
                           placeholder="Enter M-Pesa code or receipt reference">
                </div>

                <?php if ($isAdmin): ?>

                    <div class="form-group">
                        <label>Payment Status</label>

                        <select name="payment_status" required>
                            <option value="pending">Pending</option>
                            <option value="paid">Paid</option>
                            <option value="failed">Failed</option>
                        </select>
                    </div>

                <?php endif; ?>

                <button type="submit" class="btn">
                    <?php echo $isAdmin ? "Save Payment" : "Submit Payment Details"; ?>
                </button>

            </form>

        <?php else: ?>

            <p class="message">This reservation has been fully paid.</p>

        <?php endif; ?>

        <div class="panel table-wrap">

            <h2>Payment History</h2>

            <table>
                <tr>
                    <th>Amount</th>
                    <th>Method</th>
                    <th>Reference</th>
                    <th>Status</th>
                    <th>Date</th>
                </tr>

                <?php if ($payments->num_rows > 0): ?>

                    <?php while ($payment = $payments->fetch_assoc()): ?>

                        <tr>
                            <td>
                                KSh <?php echo number_format((float)$payment['amount'], 2); ?>
                            </td>

                            <td><?php echo htmlspecialchars($payment['payment_method']); ?></td>

                            <td><?php echo htmlspecialchars($payment['transaction_reference'] ?? ''); ?></td>

                            <td><?php echo htmlspecialchars($payment['payment_status']); ?></td>

                            <td><?php echo htmlspecialchars($payment['created_at']); ?></td>
                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="5">No payment records found.</td>
                    </tr>

                <?php endif; ?>

            </table>

        </div>

    </div>
</main>

</body>
</html>