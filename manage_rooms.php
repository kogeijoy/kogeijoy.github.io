<?php
session_start();
require_once "db.php";

if (
    !isset($_SESSION['user_id']) ||
    ($_SESSION['role'] ?? '') !== 'admin'
) {
    http_response_code(403);
    die("Access denied.");
}

$error = "";
$edit = null;

if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];

    $stmt = $conn->prepare("SELECT * FROM rooms WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    $edit = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? 'save';
    $id = (int)($_POST['id'] ?? 0);

    if ($action === 'delete') {

        $check = $conn->prepare(
            "SELECT COUNT(*) AS total FROM reservations WHERE room_id=?"
        );
        $check->bind_param("i", $id);
        $check->execute();

        $used = (int)$check->get_result()->fetch_assoc()['total'];
        $check->close();

        if ($used > 0) {
            $error = "This room has reservation records. Change its status to maintenance instead.";
        } else {
            $delete = $conn->prepare("DELETE FROM rooms WHERE id=?");
            $delete->bind_param("i", $id);
            $delete->execute();
            $delete->close();

            header("Location: manage_rooms.php");
            exit;
        }

    } else {

        $number = trim($_POST['room_number'] ?? '');
        $type = trim($_POST['room_type'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $capacity = (int)($_POST['capacity'] ?? 0);
        $image = trim($_POST['image'] ?? '');
        $status = $_POST['status'] ?? 'available';

        if (
            $number === '' ||
            $type === '' ||
            $price <= 0 ||
            $capacity < 1 ||
            !in_array($status, ['available', 'maintenance'], true)
        ) {
            $error = "Please enter valid room details.";

        } elseif ($id > 0) {

            $stmt = $conn->prepare(
                "UPDATE rooms SET
                room_number=?, room_type=?, description=?,
                price=?, capacity=?, image=?, status=?
                WHERE id=?"
            );

            $stmt->bind_param(
                "sssdissi",
                $number,
                $type,
                $description,
                $price,
                $capacity,
                $image,
                $status,
                $id
            );

            if ($stmt->execute()) {
                header("Location: manage_rooms.php");
                exit;
            } else {
                $error = "Could not update room. Check that the room number is unique.";
            }

            $stmt->close();

        } else {

            $stmt = $conn->prepare(
                "INSERT INTO rooms
                (room_number, room_type, description, price, capacity, image, status)
                VALUES (?, ?, ?, ?, ?, ?, ?)"
            );

            $stmt->bind_param(
                "sssdiss",
                $number,
                $type,
                $description,
                $price,
                $capacity,
                $image,
                $status
            );

            if ($stmt->execute()) {
                header("Location: manage_rooms.php");
                exit;
            } else {
                $error = "Could not add room. Check that the room number is unique.";
            }

            $stmt->close();
        }
    }
}

$rooms = $conn->query("SELECT * FROM rooms ORDER BY room_number");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Rooms</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header class="navbar">
    <div class="container">
        <a href="admin_dashboard.php" class="logo">ROYAL HAVEN ADMIN</a>
        <nav>
            <a href="admin_dashboard.php">Dashboard</a>
            <a href="manage_bookings.php">Bookings</a>
            <a href="logout.php">Logout</a>
        </nav>
    </div>
</header>

<main class="content-section">
    <div class="container">

        <h1>Manage Hotel Rooms</h1>

        <?php if ($error): ?>
            <p class="message error">
                <?php echo htmlspecialchars($error); ?>
            </p>
        <?php endif; ?>

        <form class="form-card" method="POST">

            <h2><?php echo $edit ? "Edit Room" : "Add New Room"; ?></h2>

            <input type="hidden" name="id"
                   value="<?php echo (int)($edit['id'] ?? 0); ?>">

            <div class="form-group">
                <label>Room Number</label>
                <input type="text" name="room_number"
                       value="<?php echo htmlspecialchars($edit['room_number'] ?? ''); ?>"
                       required>
            </div>

            <div class="form-group">
                <label>Room Type</label>
                <input type="text" name="room_type"
                       value="<?php echo htmlspecialchars($edit['room_type'] ?? ''); ?>"
                       required>
            </div>

            <div class="form-group">
                <label>Description</label>
                <textarea name="description" rows="3"><?php
                    echo htmlspecialchars($edit['description'] ?? '');
                ?></textarea>
            </div>

            <div class="form-group">
                <label>Price per Night (KSh)</label>
                <input type="number" name="price" min="1" step="0.01"
                       value="<?php echo htmlspecialchars($edit['price'] ?? ''); ?>"
                       required>
            </div>

            <div class="form-group">
                <label>Guest Capacity</label>
                <input type="number" name="capacity" min="1"
                       value="<?php echo htmlspecialchars($edit['capacity'] ?? 1); ?>"
                       required>
            </div>

            <div class="form-group">
                <label>Room Image URL</label>
                <input type="text" name="image"
                       value="<?php echo htmlspecialchars($edit['image'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label>Room Status</label>
                <select name="status">
                    <option value="available"
                        <?php echo (($edit['status'] ?? 'available') === 'available') ? 'selected' : ''; ?>>
                        Available
                    </option>

                    <option value="maintenance"
                        <?php echo (($edit['status'] ?? '') === 'maintenance') ? 'selected' : ''; ?>>
                        Maintenance
                    </option>
                </select>
            </div>

            <button type="submit" name="action" value="save">
                <?php echo $edit ? "Update Room" : "Add Room"; ?>
            </button>

            <?php if ($edit): ?>
                <a href="manage_rooms.php" class="btn secondary">Cancel</a>
            <?php endif; ?>

        </form>

        <div class="panel table-wrap">

            <h2>Registered Rooms</h2>

            <table>
                <tr>
                    <th>Room Number</th>
                    <th>Type</th>
                    <th>Price</th>
                    <th>Capacity</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>

                <?php while ($room = $rooms->fetch_assoc()): ?>

                    <tr>
                        <td><?php echo htmlspecialchars($room['room_number']); ?></td>
                        <td><?php echo htmlspecialchars($room['room_type']); ?></td>
                        <td>KSh <?php echo number_format((float)$room['price'], 2); ?></td>
                        <td><?php echo (int)$room['capacity']; ?></td>
                        <td><?php echo htmlspecialchars($room['status']); ?></td>

                        <td>
                            <a href="manage_rooms.php?edit=<?php echo (int)$room['id']; ?>">
                                Edit
                            </a>

                            <form method="POST" class="inline-form"
                                  onsubmit="return confirm('Delete this room?');">

                                <input type="hidden" name="id"
                                       value="<?php echo (int)$room['id']; ?>">

                                <button type="submit" name="action"
                                        value="delete" class="danger">
                                    Delete
                                </button>
                            </form>
                        </td>
                    </tr>

                <?php endwhile; ?>

            </table>

        </div>

    </div>
</main>

</body>
</html>