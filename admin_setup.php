<?php
session_start();
require_once "db.php";

$countResult = $conn->query(
    "SELECT COUNT(*) AS total FROM users WHERE role='admin'"
);
$adminCount = (int)$countResult->fetch_assoc()['total'];

$error = "";
$success = "";

if ($adminCount > 0) {

    $error = "An administrator account already exists. Setup is disabled.";

} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (
        $name === '' ||
        !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        strlen($password) < 12
    ) {
        $error = "Enter a name, valid email and password of at least 12 characters.";

    } else {

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $role = "admin";

        $stmt = $conn->prepare(
            "INSERT INTO users (full_name, email, password, role)
             VALUES (?, ?, ?, ?)"
        );

        $stmt->bind_param("ssss", $name, $email, $hash, $role);

        if ($stmt->execute()) {
            $success = "Administrator account created successfully. Delete admin_setup.php now.";
        } else {
            $error = "Could not create administrator. The email may already exist.";
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
    <title>Administrator Setup</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<main class="content-section">
    <div class="container">

        <form class="form-card" method="POST">
            <h2>Create Administrator Account</h2>

            <p class="small">
                This setup page is intended for initial local installation only.
            </p>

            <?php if ($error): ?>
                <p class="message error">
                    <?php echo htmlspecialchars($error); ?>
                </p>
            <?php endif; ?>

            <?php if ($success): ?>
                <p class="message">
                    <?php echo htmlspecialchars($success); ?>
                </p>
            <?php endif; ?>

            <?php if ($adminCount === 0 && !$success): ?>

                <div class="form-group">
                    <label>Administrator Name</label>
                    <input type="text" name="full_name" required>
                </div>

                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" required>
                </div>

                <div class="form-group">
                    <label>Password (minimum 12 characters)</label>
                    <input type="password" name="password"
                           minlength="12" required>
                </div>

                <button type="submit" class="btn">
                    Create Administrator
                </button>

            <?php endif; ?>

            <p><a href="index.php">Return to Homepage</a></p>

        </form>

    </div>
</main>

</body>
</html>