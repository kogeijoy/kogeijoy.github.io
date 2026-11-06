<?php
session_start();
require_once "db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];

$stmt = $conn->prepare(
    "SELECT r.*, rooms.room_number, rooms.room_type,
    COALESCE(
        (SELECT SUM(p.amount)
         FROM payments p
         WHERE p.reservation_id = r.id
         AND p.payment_status = 'paid'), 0
    ) AS amount_paid
    FROM reservations r
    JOIN rooms ON rooms.id = r.room_id
    WHERE r.user_id = ?
    ORDER BY r.created_at DESC"
);

$stmt->bind_param("i", $user_id);
$stmt->execute();
$bookings = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings | Royal Haven Hotel</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header class="navbar">
    <div class="container">
        <a href="index.php" class="logo">ROYAL HAVEN</a>
        <nav>
            <a href="index.php">Home</a>
            <a href="rooms.php">Rooms</a>
            <a href="my_bookings.php">My Bookings</a>
            <a href="logout.php">Logout</a>
        </nav>
    </div>
</header>

<main class="content-section">
    <div class="container">

        <h1>My Reservations</h1>

        <p>
            Welcome,
            <?php echo htmlspecialchars($_SESSION['full_name']); ?>
        </p>

        <?php if (isset($_GET['created'])): ?>
            <p class="message">
                Your reservation was submitted successfully.
                It is awaiting administrator confirmation.
            </p>
        <?php endif; ?>

        <div class="panel table-wrap">

            <table>
                <thead>
                    <tr>
                        <th>Room</th>
                        <th>Check-in</th>
                        <th>Check-out</th>
                        <th>Guests</th>
                        <th>Total Amount</th>
                        <th>Paid</th>
                        <th>Status</th>
                        <th>Payment</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if ($bookings->num_rows > 0): ?>

                        <?php while ($booking = $bookings->fetch_assoc()): ?>

                            <tr>
                                <td>
                                    <?php echo htmlspecialchars($booking['room_type']); ?>
                                    (<?php echo htmlspecialchars($booking['room_number']); ?>)
                                </td>

                                <td><?php echo htmlspecialchars($booking['check_in']); ?></td>
                                <td><?php echo htmlspecialchars($booking['check_out']); ?></td>
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
                                    <a class="btn"
                                       href="payment.php?reservation_id=<?php echo (int)$booking['id']; ?>">
                                        Payment
                                    </a>
                                </td>
                            </tr>

                        <?php endwhile; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="8">
                                You have not made any reservations yet.
                            </td>
                        </tr>

                    <?php endif; ?>

                </tbody>
            </table>

        </div>

        <div class="center-button">
            <a href="rooms.php" class="btn">Book a Room</a>
        </div>

    </div>
</main>

</body>
</html>