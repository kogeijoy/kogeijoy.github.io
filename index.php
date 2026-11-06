<?php
session_start();
require_once "db.php";

// Get a few rooms to display on the homepage
$sql = "SELECT * FROM rooms WHERE status = 'available' ORDER BY id DESC LIMIT 3";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome | Royal Haven Hotel</title>
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

<section class="hero">
    <div class="hero-content">
        <p class="hero-subtitle">WELCOME TO ROYAL HAVEN HOTEL</p>

        <h1>Experience Comfort, Luxury and Exceptional Hospitality</h1>

        <p>
            Enjoy beautiful rooms, excellent services and unforgettable
            hospitality at Royal Haven Hotel.
        </p>

        <a href="rooms.php" class="btn">Explore Our Rooms</a>
    </div>
</section>

<section class="search-section">
    <div class="container">
        <h2>Find Your Perfect Stay</h2>
        <p>Choose your dates and the number of guests.</p>

        <form action="rooms.php" method="GET" class="search-form">

            <div class="form-group">
                <label for="check_in">Check-in Date</label>
                <input type="date" id="check_in" name="check_in"
                       min="<?php echo date('Y-m-d'); ?>" required>
            </div>

            <div class="form-group">
                <label for="check_out">Check-out Date</label>
                <input type="date" id="check_out" name="check_out"
                       min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" required>
            </div>

            <div class="form-group">
                <label for="guests">Number of Guests</label>
                <input type="number" id="guests" name="guests"
                       min="1" max="10" value="1" required>
            </div>

            <button type="submit" class="btn">Search Rooms</button>

        </form>
    </div>
</section>

<section class="rooms-section">
    <div class="container">

        <h2>Our Featured Rooms</h2>

        <p class="section-description">
            Discover comfortable accommodation designed for your relaxation.
        </p>

        <div class="room-grid">

            <?php if ($result && $result->num_rows > 0): ?>

                <?php while ($room = $result->fetch_assoc()): ?>

                    <div class="room-card">

                        <?php if (!empty($room['image'])): ?>
                            <img src="<?php echo htmlspecialchars($room['image']); ?>"
                                 alt="<?php echo htmlspecialchars($room['room_type']); ?>">
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

                            <p>
                                Capacity:
                                <?php echo (int)$room['capacity']; ?> guests
                            </p>

                            <h4>
                                KSh <?php echo number_format((float)$room['price'], 2); ?>
                                <small>per night</small>
                            </h4>

                            <a href="rooms.php" class="btn">View Room</a>

                        </div>
                    </div>

                <?php endwhile; ?>

            <?php else: ?>

                <p>
                    Our rooms will be displayed here once they have been
                    added to the system.
                </p>

            <?php endif; ?>

        </div>

        <div class="center-button">
            <a href="rooms.php" class="btn">View All Rooms</a>
        </div>

    </div>
</section>

<section class="about-section">
    <div class="container">
        <h2>Why Choose Royal Haven Hotel?</h2>

        <div class="features">

            <div class="feature">
                <h3>Comfortable Rooms</h3>
                <p>Relax in clean, spacious and well-furnished rooms.</p>
            </div>

            <div class="feature">
                <h3>Easy Reservations</h3>
                <p>Book your preferred room conveniently through our website.</p>
            </div>

            <div class="feature">
                <h3>Excellent Hospitality</h3>
                <p>Enjoy friendly service and a welcoming environment.</p>
            </div>

        </div>
    </div>
</section>

<footer class="footer">
    <div class="container">
        <p>&copy; <?php echo date('Y'); ?> Royal Haven Hotel. All Rights Reserved.</p>
    </div>
</footer>

</body>
</html>