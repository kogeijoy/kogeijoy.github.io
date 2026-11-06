<?php
session_start();
require_once "db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$room_id = (int)($_POST['room_id'] ?? $_GET['room_id'] ?? 0);
$check_in = $_POST['check_in'] ?? $_GET['check_in'] ?? '';
$check_out = $_POST['check_out'] ?? $_GET['check_out'] ?? '';
$guests = (int)($_POST['guests'] ?? $_GET['guests'] ?? 1);

$error = '';
$room = null;

$stmt = $conn->prepare(
    "SELECT * FROM rooms WHERE id = ? AND status = 'available'"
);
$stmt->bind_param("i", $room_id);
$stmt->execute();
$room = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$room) {
    $error = "The selected room does not exist or is not available.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $room) {

    if (
        !$check_in ||
        !$check_out ||
        !strtotime($check_in) ||
        !strtotime($check_out) ||
        $check_in < date('Y-m-d') ||
        $check_out <= $check_in
    ) {
        $error = "Please enter valid check-in and check-out dates.";

    } elseif ($guests < 1 || $guests > (int)$room['capacity']) {
        $error = "The number of guests exceeds the room capacity.";

    } else {

        $availability = $conn->prepare(
            "SELECT id FROM reservations
             WHERE room_id = ?
             AND status IN ('pending', 'confirmed')
             AND check_in < ?
             AND check_out > ?"
        );

        $availability->bind_param(
            "iss",
            $room_id,
            $check_out,
            $check_in
        );

        $availability->execute();

        if ($availability->get_result()->num_rows > 0) {

            $error = "This room is already reserved for those dates.";

        } else {

            $nights = (int)(
                (strtotime($check_out) - strtotime($check_in)) / 86400
            );

            $total_amount = $nights * (float)$room['price'];
            $user_id = (int)$_SESSION['user_id'];

            $insert = $conn->prepare(
                "INSERT INTO reservations
                (user_id, room_id, check_in, check_out, guests, total_amount)
                VALUES (?, ?, ?, ?, ?, ?)"
            );

            $insert->bind_param(
                "iissid",
                $user_id,
                $room_id,
                $check_in,
                $check_out,
                $guests,
                $total_amount
            );

            if ($insert->execute()) {
                header("Location: my_bookings.php?created=1");
                exit;
            } else {
                $error = "Your reservation could not be saved.";
            }

            $insert->close();
        }

        $availability->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book a Room | Royal Haven Hotel</title>
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

        <form class="form-card" method="POST" action="book.php">
            <h2>Room Reservation</h2>

            <?php if ($error): ?>
                <p class="message error">
                    <?php echo htmlspecialchars($error); ?>
                </p>
            <?php endif; ?>

            <?php if ($room): ?>

                <input type="hidden" name="room_id"
                       value="<?php echo (int)$room['id']; ?>">

                <h3>
                    <?php echo htmlspecialchars($room['room_type']); ?>
                </h3>

                <p>Room Number:
                    <?php echo htmlspecialchars($room['room_number']); ?>
                </p>

                <p>Price per night:
                    KSh <?php echo number_format((float)$room['price'], 2); ?>
                </p>

                <div class="form-group">
                    <label>Check-in Date</label>
                    <input type="date" name="check_in"
                           min="<?php echo date('Y-m-d'); ?>"
                           value="<?php echo htmlspecialchars($check_in); ?>"
                           required>
                </div>

                <div class="form-group">
                    <label>Check-out Date</label>
                    <input type="date" name="check_out"
                           min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>"
                           value="<?php echo htmlspecialchars($check_out); ?>"
                           required>
                </div>

                <div class="form-group">
                    <label>Number of Guests</label>
                    <input type="number" name="guests"
                           min="1"
                           max="<?php echo (int)$room['capacity']; ?>"
                           value="<?php echo max(1, $guests); ?>"
                           required>
                </div>

                <button type="submit" class="btn">
                    Confirm Reservation
                </button>

            <?php endif; ?>

        </form>

    </div>
</main>

</body>
</html>