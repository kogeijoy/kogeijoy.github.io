<?php
session_start();
require_once "db.php";

$errors = [];
$full_name = "";
$email = "";
$phone = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($full_name === '') {
        $errors[] = "Full name is required.";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Enter a valid email address.";
    }

    if ($phone === '') {
        $errors[] = "Phone number is required.";
    }

    if (strlen($password) < 8) {
        $errors[] = "Password must contain at least 8 characters.";
    }

    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }

    if (empty($errors)) {

        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $errors[] = "An account with this email already exists.";
        }

        $check->close();
    }

    if (empty($errors)) {

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $role = "customer";

        $stmt = $conn->prepare(
            "INSERT INTO users (full_name, email, phone, password, role)
             VALUES (?, ?, ?, ?, ?)"
        );

        $stmt->bind_param(
            "sssss",
            $full_name,
            $email,
            $phone,
            $hashed_password,
            $role
        );

        if ($stmt->execute()) {

            $stmt->close();

            header("Location: login.php?registered=1");
            exit();

        } else {
            $errors[] = "Registration failed. Please try again.";
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account | Royal Haven Hotel</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

<header class="navbar">
    <div class="container">
        <a href="index.php" class="logo">ROYAL HAVEN</a>

        <nav>
            <a href="index.php">Home</a>
            <a href="rooms.php">Our Rooms</a>
            <a href="login.php">Login</a>
        </nav>
    </div>
</header>

<section class="form-section">

    <div class="form-container">

        <h2>Create Your Account</h2>

        <p>Register to start making hotel reservations.</p>

        <?php foreach ($errors as $error): ?>
            <p class="error-message">
                <?php echo htmlspecialchars($error); ?>
            </p>
        <?php endforeach; ?>

        <form method="POST" action="register.php">

            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="full_name"
                       value="<?php echo htmlspecialchars($full_name); ?>"
                       required>
            </div>

            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email"
                       value="<?php echo htmlspecialchars($email); ?>"
                       required>
            </div>

            <div class="form-group">
                <label>Phone Number</label>
                <input type="tel" name="phone"
                       value="<?php echo htmlspecialchars($phone); ?>"
                       required>
            </div>

            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password"
                       minlength="8" required>
            </div>

            <div class="form-group">
                <label>Confirm Password</label>
                <input type="password" name="confirm_password"
                       minlength="8" required>
            </div>

            <button type="submit" class="btn">Register</button>

        </form>

        <p>
            Already have an account?
            <a href="login.php">Login here</a>
        </p>

    </div>

</section>

<footer class="footer">
    <div class="container">
        <p>&copy; <?php echo date('Y'); ?> Royal Haven Hotel.</p>
    </div>
</footer>

</body>
</html>