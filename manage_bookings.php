<?php
session_start();
require_once "db.php";

if (
    !isset($_SESSION['user_id']) ||
    ($_SESSION['role'] ?? '') !== 'admin'
) {
    http_response_code(403);
    die("Access denied. Administrator login required.");
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $reservation_id = (int)($_POST['reservation_id'] ?? 0);
    $status = $_POST['status'] ?? '';

    $allowed = ['pending', 'confirmed', 'cancelled', 'completed'];

    if (
        $reservation_id > 0 &&
        in_array($status, $allowed, true)
    ) {

        $stmt = $conn->prepare(
            "UPDATE reservations SET status=? WHERE id=?"
        );

        $stmt->bind_param("si", $status, $reservation_id);

        if ($stmt->execute()) {
            header("Location: manage_bookings.php?updated=1");
            exit;
        } else {
            $error = "Could not update the reservation.";
        }

        $stmt->close();

    } else {
        $error = "Invalid reservation details.";
    }
}

$sql = "
    SELECT b.*, u.full_name, u.email, u.phone,
    r.room_number, r.room_type,
    COALESCE(
        (SELECT SUM(p.amount)
         FROM payments p
         WHERE p.reservation_id = b.id
         AND p.payment_status = 'paid'), 0
    ) AS amount_paid
    FROM reservations b
    JOIN users u ON u.id = b.user_id
    JOIN rooms r ON r.id = b.room_id
    ORDER BY b.created_at DESC
";

$bookings = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Reservations</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header class="navbar">
    <div class="container">
        <a href="admin_dashboard.php" class="logo">ROYAL HAVEN ADMIN</a>

        <nav>
            <a href="admin_dashboard.php">Dashboard</a>
            <a href="manage_rooms.php">Rooms</a>
            <a href="logout.php">Logout</a>
        </nav>
    </div>
</header>

<main class="content-section">
    <div class="container">

        <h1>Manage Customer Reservations</h1>

        <?php if (isset($_GET['updated'])): ?>
            <p class="message">Reservation status updated successfully.</p>
        <?php endif; ?>

        <?php if ($error): ?>
            <p class="message error">
                <?php echo htmlspecialchars($error); ?>
            </p>
        <?php endif; ?>

        <div class="panel table-wrap">

            <table>
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Room</th>
                        <th>Dates</th>
                        <th>Guests</th>
                        <th>Total</th>
                        <th>Paid</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if ($bookings->num_rows > 0): ?>

                        <?php while ($booking = $bookings->fetch_assoc()): ?>

                            <tr>
                                <td>
                                    <?php echo htmlspecialchars($booking['full_name']); ?>
                                    <br>
                                    <span class="small">
                                        <?php echo htmlspecialchars($booking['email']); ?>
                                        <br>
                                        <?php echo htmlspecialchars($booking['phone'] ?? ''); ?>
                                    </span>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($booking['room_type']); ?>
                                    (<?php echo htmlspecialchars($booking['room_number']); ?>)
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($booking['check_in']); ?>
                                    to
                                    <?php echo htmlspecialchars($booking['check_out']); ?>
                                </td>

                                <td><?php echo (int)$booking['guests']; ?></td>

                                <td>
                                    KSh <?php echo number_format((float)$booking['total_amount'], 2); ?>
                                </td>

                                <td>
                                    KSh <?php echo number_format((float)$booking['amount_paid'], 2); ?>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($booking['status']); ?>
                                </td>

                                <td>
                                    <form method="POST">

                                        <input type="hidden"
                                               name="reservation_id"
                                               value="<?php echo (int)$booking['id']; ?>">

                                        <select name="status">

                                            <option value="pending"
                                                <?php echo $booking['status'] === 'pending' ? 'selected' : ''; ?>>
                                                Pending
                                            </option>

                                            <option value="confirmed"
                                                <?php echo $booking['status'] === 'confirmed' ? 'selected' : ''; ?>>
                                                Confirmed
                                            </option>

                                            <option value="cancelled"
                                                <?php echo $booking['status'] === 'cancelled' ? 'selected' : ''; ?>>
                                                Cancelled
                                            </option>

                                            <option value="completed"
                                                <?php echo $booking['status'] === 'completed' ? 'selected' : ''; ?>>
                                                Completed
                                            </option>

                                        </select>

                                        <button type="submit">Update</button>

                                    </form>

                                    <p>
                                        <a href="payment.php?reservation_id=<?php echo (int)$booking['id']; ?>">
                                            Record Payment
                                        </a>
                                    </p>

                                </td>
                            </tr>

                        <?php endwhile; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="8">No reservations have been made.</td>
                        </tr>

                    <?php endif; ?>

                </tbody>
            </table>

        </div>

    </div>
</main>

</body>
</html>