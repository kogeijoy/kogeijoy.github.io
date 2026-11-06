<?php
session_start();
require_once "db.php";

$check_in = $_GET['check_in'] ?? '';
$check_out = $_GET['check_out'] ?? '';
$guests = isset($_GET['guests']) ? (int)$_GET['guests'] : 1;

$errors = [];
$rooms = [];

if ($guests < 1) {
    $guests = 1;
}

if ($check_in !== '' || $check_out !== '') {

    $today = date('Y-m-d');

    if (!$check_in || !$check_out) {
        $errors[] = "Please enter both check-in and check-out dates.";
    } elseif ($check_in < $today) {
        $errors[] = "Check-in date cannot be in the past.";
    } elseif ($check_out <= $check_in) {
        $errors[] = "Check-out date must be after check-in date.";
    }
}

if (empty($errors)) {

    if ($check_in && $check_out) {

        $sql = "SELECT * FROM rooms r
                WHERE r.status = 'available'
                AND r.capacity >= ?
                AND NOT EXISTS (
                    SELECT 1 FROM reservations b
                    WHERE b.room_id = r.id
                    AND b.status IN ('pending', 'confirmed')
                    AND b.check_in < ?
                    AND b.check_out > ?
                )
                ORDER BY r.price ASC";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iss", $guests, $check_out, $check_in);

    } else {

        $sql = "SELECT * FROM rooms
                WHERE status = 'available'
                AND capacity >= ?
                ORDER BY price ASC";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $guests);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $rooms[] = $row;
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Rooms | Royal Haven Hotel</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

<header class="navbar">
    <div class="container">
        <a href="index.php" class="logo">ROYAL HAVEN</a>

        <nav>
            <a href="index.php">Home</a>
            <a href="rooms.php">Our Rooms</a>

            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="my_bookings.php">My Bookings</a>
                <a href="logout.php">Logout</a>
            <?php else: ?>
                <a href="login.php">Login</a>
                <a href="register.php">Register</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<section class="page-header">
    <div class="container">
        <h1>Explore Our Rooms</h1>
        <p>Find comfortable accommodation for your stay.</p>
    </div>
</section>

<section class="search-section">
    <div class="container">

        <h2>Search Available Rooms</h2>

        <?php foreach ($errors as $error): ?>
            <p class="error-message">
                <?php echo htmlspecialchars($error); ?>
            </p>
        <?php endforeach; ?>

        <form action="rooms.php" method="GET" class="search-form">

            <div class="form-group">
                <label>Check-in Date</label>
                <input type="date" name="check_in"
                       min="<?php echo date('Y-m-d'); ?>"
                       value="<?php echo htmlspecialchars($check_in); ?>">
            </div>

            <div class="form-group">
                <label>Check-out Date</label>
                <input type="date" name="check_out"
                       min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>"
                       value="<?php echo htmlspecialchars($check_out); ?>">
            </div>

            <div class="form-group">
                <label>Guests</label>
                <input type="number" name="guests"
                       min="1" max="10"
                       value="<?php echo $guests; ?>">
            </div>

            <button type="submit" class="btn">Search</button>

        </form>
    </div>
</section>

<section class="rooms-section">
    <div class="container">

        <h2>Available Rooms</h2>

        <div class="room-grid">

            <?php if (count($rooms) > 0): ?>

                <?php foreach ($rooms as $room): ?>

                    <div class="room-card">

                        <?php if (!empty($room['image'])): ?>
                            <img src="<?php echo htmlspecialchars($room['image']); ?>"
                                 alt="Hotel room">
                        <?php else: ?>
                            <img src="https://images.unsplash.com/photo-1611892440504-42a792e24d32"
                                 alt="Hotel room">
                        <?php endif; ?>

                        <div class="room-details">

                            <h3>
                                <?php echo htmlspecialchars($room['room_type']); ?>
                            </h3>

                            <p>
                                <?php echo htmlspecialchars($room['description']); ?>
                            </p>

                            <p>Room Number:
                                <?php echo htmlspecialchars($room['room_number']); ?>
                            </p>

                            <p>Capacity:
                                <?php echo (int)$room['capacity']; ?> guests
                            </p>

                            <h4>
                                KSh <?php echo number_format((float)$room['price'], 2); ?>
                                <small>per night</small>
                            </h4>

                            <a class="btn"
                               href="book.php?room_id=<?php echo (int)$room['id']; ?>">
                                Book This Room
                            </a>

                        </div>
                    </div>

                <?php endforeach; ?>

            <?php else: ?>

                <p>No available rooms were found for your search.</p>

            <?php endif; ?>

        </div>

    </div>
</section>

<footer class="footer">
    <div class="container">
        <p>&copy; <?php echo date('Y'); ?> Royal Haven Hotel.</p>
    </div>
</footer>

</body>
</html>