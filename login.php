<?php
session_start();
require_once "db.php";

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {

        $error = "Please enter your email and password.";

    } else {

        $stmt = $conn->prepare(
            "SELECT id, full_name, email, password, role
             FROM users WHERE email = ? LIMIT 1"
        );

        $stmt->bind_param("s", $email);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows === 1) {

            $user = $result->fetch_assoc();

            if (password_verify($password, $user['password'])) {

                session_regenerate_id(true);

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];

                $stmt->close();

                if ($user['role'] === 'admin') {
                    header("Location: admin_dashboard.php");
                } else {
                    header("Location: my_bookings.php");
                }

                exit();

            } else {
                $error = "Incorrect email or password.";
            }

        } else {
            $error = "Incorrect email or password.";
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
    <title>Login | Royal Haven Hotel</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

<header class="navbar">
    <div class="container">
        <a href="index.php" class="logo">ROYAL HAVEN</a>

        <nav>
            <a href="index.php">Home</a>
            <a href="rooms.php">Our Rooms</a>
            <a href="register.php">Register</a>
        </nav>
    </div>
</header>

<section class="form-section">

    <div class="form-container">

        <h2>Welcome Back</h2>

        <p>Login to access your hotel account.</p>

        <?php if (isset($_GET['registered'])): ?>
            <p class="success-message">
                Registration successful. You can now log in.
            </p>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
            <p class="error-message">
                <?php echo htmlspecialchars($error); ?>
            </p>
        <?php endif; ?>

        <form method="POST" action="login.php">

            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" required>
            </div>

            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>

            <button type="submit" class="btn">Login</button>

        </form>

        <p>
            Don't have an account?
            <a href="register.php">Register here</a>
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