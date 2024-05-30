<?php
include '../config/config.php';
include '../functions/functions.php';
session_start();

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: admin_login.php');
    exit();
}

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_room'])) {
    $room_id = $_POST['room_id'];
    try {
        deleteRoom($conn, $room_id);
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_room'])) {
    $room_name = $_POST['room_name'];
    $bedroom = $_POST['bedroom'];
    $rate_per_night = $_POST['rate_per_night'];
    $capacity = $_POST['capacity'];
    $city = $_POST['city'];
    $country = $_POST['country'];
    $description = $_POST['description'];
    $image = $_POST['image'];

    if (addRoom($conn, $room_name, $bedroom, $rate_per_night, $capacity, $city, $country, $description, $image)) {
        $success_message = "Room added successfully.";
    } else {
        $error_message = "Failed to add room.";
    }
}

$rooms = getRooms($conn);
$users = getUsers($conn);
$reservations = getReservations($conn);

// Fetch payment dates for reservations
foreach ($reservations as &$reservation) {
    $payment_stmt = $conn->prepare("SELECT payment_date FROM Payments WHERE payment_id = ?");
    $payment_stmt->bind_param("i", $reservation['payment_id']);
    $payment_stmt->execute();
    $payment_stmt->bind_result($payment_date);
    $payment_stmt->fetch();
    $reservation['payment_date'] = $payment_date;
    $payment_stmt->close();
}

function deleteRoom($conn, $room_id) {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM Reservation WHERE room_id = ?");
    $stmt->bind_param("i", $room_id);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    if ($count > 0) {
        throw new Exception("Cannot delete room: there are existing reservations associated with this room.");
    }

    $stmt = $conn->prepare("DELETE FROM Room WHERE room_id = ?");
    $stmt->bind_param("i", $room_id);
    $stmt->execute();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <link rel="stylesheet" type="text/css" href="../css/styles.css">
    <script src="../js/scripts.js"></script>
</head>
<body>
    <div class="logo-container">
        <img src="../images/fandiqlogo.svg" alt="Fandiq Management System Logo" class="logo">
    </div>
    <h1>Admin's Dashboard</h1>
    <nav>
        <ul>
            <li><a href="#" onclick="showSection('view_users')">View Users</a></li>
            <li><a href="#" onclick="showSection('view_rooms')">View Rooms</a></li>
            <li><a href="#" onclick="showSection('add_room')">Add Room</a></li>
            <li><a href="#" onclick="showSection('view_reservations')">View Reservations</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </nav>

    <div id="view_users" class="section">
        <h2>View Users</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>First Name</th>
                    <th>Last Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>City</th>
                    <th>Country</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['user_id']); ?></td>
                        <td><?php echo htmlspecialchars($user['first_name']); ?></td>
                        <td><?php echo htmlspecialchars($user['last_name']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo htmlspecialchars($user['phone']); ?></td>
                        <td><?php echo htmlspecialchars($user['city']); ?></td>
                        <td><?php echo htmlspecialchars($user['country']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div id="view_rooms" class="section" style="display: none;">
        <h2>View Rooms</h2>
        <table>
            <thead>
                <tr>
                    <th>Room ID</th>
                    <th>Room Name</th>
                    <th>Bedroom</th>
                    <th>Rate per Night</th>
                    <th>Capacity</th>
                    <th>City</th>
                    <th>Country</th>
                    <th>Description</th>
                    <th>Images</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rooms as $room): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($room['room_id']); ?></td>
                        <td><?php echo htmlspecialchars($room['room_name']); ?></td>
                        <td><?php echo htmlspecialchars($room['bedroom']); ?></td>
                        <td><?php echo htmlspecialchars($room['rate_per_night']); ?></td>
                        <td><?php echo htmlspecialchars($room['capacity']); ?></td>
                        <td><?php echo htmlspecialchars($room['city']); ?></td>
                        <td><?php echo htmlspecialchars($room['country']); ?></td>
                        <td><?php echo htmlspecialchars($room['description']); ?></td>
                        <td>
                            <?php $image_url = htmlspecialchars($room['image_url']); ?>
                             <button type="button" onclick="showImages('<?php echo $image_url; ?>')">View Images</button>
                        </td>
                        <td>
                            <form method="POST" onsubmit="return confirm('Are you sure you want to delete this room?');">
                                <input type="hidden" name="room_id" value="<?php echo $room['room_id']; ?>">
                                <button type="submit" name="delete_room">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div id="add_room" class="section" style="display: none;">
        <h2>Add Room</h2>
        <form method="POST">
            <label for="room_name">Room Name:</label>
            <input type="text" id="room_name" name="room_name" required>

            <label for="bedroom">Number of Bedrooms:</label>
            <input type="number" id="bedroom" name="bedroom" required>

            <label for="rate_per_night">Rate per Night:</label>
            <input type="number" id="rate_per_night" name="rate_per_night" required>

            <label for="capacity">Capacity:</label>
            <input type="number" id="capacity" name="capacity" required>

            <label for="city">City:</label>
            <input type="text" id="city" name="city" required>

            <label for="country">Country:</label>
            <input type="text" id="country" name="country" required>

            <label for="description">Description:</label>
            <textarea id="description" name="description" required></textarea>

            <label for="image">Image URL:</label>
            <input type="text" id="image" name="image" required>

            <button type="submit" name="add_room">Add Room</button>
        </form>
    </div>

    <div id="view_reservations" class="section" style="display: none;">
        <h2>View Reservations</h2>
        <table>
            <thead>
                <tr>
                    <th>Booking Date</th>
                    <th>Confirmation Code</th>
                    <th>User ID</th>
                    <th>User Name</th>
                    <th>Room ID</th>
                    <th>Check-in Date</th>
                    <th>Check-out Date</th>
                    <th>Payment ID</th>
                    <th>Food</th>
                    <th>Guests</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reservations as $reservation): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($reservation['payment_date']); ?></td>
                        <td><?php echo htmlspecialchars($reservation['confirmation_code']); ?></td>
                        <td><?php echo htmlspecialchars($reservation['user_id']); ?></td>
                        <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                        <td><?php echo htmlspecialchars($reservation['room_id']); ?></td>
                        <td><?php echo htmlspecialchars($reservation['check_in_date']); ?></td>
                        <td><?php echo htmlspecialchars($reservation['check_out_date']); ?></td>
                        <td><?php echo htmlspecialchars($reservation['payment_id']); ?></td>
                        <td><?php echo htmlspecialchars($reservation['food'] === 'Yes' ? 'Yes' : 'No'); ?></td>
                        <td><?php echo htmlspecialchars($reservation['occupants']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div id="imageModal" class="modal">
        <span class="close" onclick="closeModal()">&times;</span>
        <div class="modal-content" id="modalContent"></div>
    </div>
</body>
</html>
