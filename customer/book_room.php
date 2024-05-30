<?php
include '../config/config.php';
include '../functions/functions.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Initialize variables
$room_id = '';
$check_in = '';
$check_out = '';
$guests = '';
$first_name = '';
$last_name = '';
$address = '';
$city = '';
$phone = '';
$email = '';
$country = '';
$zip_code = '';
$food = '';
$payment_method = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Handle form submission
    $first_name = $_POST['first_name'] ?? '';
    $last_name = $_POST['last_name'] ?? '';
    $address = $_POST['address'] ?? '';
    $city = $_POST['city'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $email = $_POST['email'] ?? '';
    $country = $_POST['country'] ?? '';
    $zip_code = $_POST['zip_code'] ?? '';
    $food = $_POST['food'] ?? '';
    $payment_method = $_POST['payment_method'] ?? '';

    $user = getUserByEmail($conn, $email);
    if (!$user) {
        $user_id = registerUser($conn, $first_name, $last_name, $address, $city, $phone, $email, $country, $zip_code);
    } else {
        $user_id = $user['user_id'];
    }

    $room_id = $_POST['room_id'];
    $check_in = $_POST['check_in'];
    $check_out = $_POST['check_out'];
    $guests = $_POST['guests'];

    // Set payment_id based on the payment method
    $stmt = $conn->prepare("SELECT payment_id FROM Payments WHERE payment_method = ?");
    $stmt->bind_param("s", $payment_method);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($payment_id);
        $stmt->fetch();
    } else {
        echo "Payment method not found.";
        exit();
    }

    // Calculate total price
    $check_in_date = DateTime::createFromFormat('Y-m-d', $check_in);
    $check_out_date = DateTime::createFromFormat('Y-m-d', $check_out);
    $nights = $check_out_date->diff($check_in_date)->days;
    $room_rate = $_POST['room_rate'] ?? 0;
    $breakfast_cost = $food === 'Yes' ? 3 * $nights : 0;
    $total_price = $nights * $room_rate + $breakfast_cost;

    try {
        $confirmation_code = bookRoom($conn, $user_id, $room_id, $check_in, $check_out, $payment_id, $food, $guests, $total_price);
        if ($confirmation_code) {
            header("Location: confirm_booking.php?confirmation_code=$confirmation_code");
            exit();
        } else {
            echo "Booking failed.";
        }
    } catch (Exception $e) {
        echo "Booking failed: " . $e->getMessage();
    }
} else {
    // Handle form display with pre-filled data
    if (!isset($_GET['room_id'], $_GET['check_in'], $_GET['check_out'], $_GET['guests'])) {
        header("Location: ../index.php");
        exit();
    }

    $room_id = $_GET['room_id'];
    $check_in = $_GET['check_in'];
    $check_out = $_GET['check_out'];
    $guests = $_GET['guests'];
    $rooms = getRoomsByLocationAndOccupants($conn, $_GET['country'], $_GET['city'], $guests);

    $selected_room = array_filter($rooms, function($room) use ($room_id) {
        return $room['room_id'] == $room_id;
    });

    $selected_room = reset($selected_room);

    // Format dates
    $check_in_date = DateTime::createFromFormat('Y-m-d', $check_in);
    $check_out_date = DateTime::createFromFormat('Y-m-d', $check_out);
    $formatted_check_in = $check_in_date->format('d/m/Y l');
    $formatted_check_out = $check_out_date->format('d/m/Y l');
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Book a Room</title>
    <link rel="stylesheet" type="text/css" href="../css/styles.css">
    <script src="../js/scripts.js" defer></script>
</head>
<body class="index-page">
    <a href="confirm_booking.php">Manage Booking</a>
    <h1>Book a Room</h1>
    <form method="POST" class="booking-form">
        <input type="hidden" name="room_id" value="<?php echo htmlspecialchars($room_id); ?>">
        <input type="hidden" name="check_in" value="<?php echo htmlspecialchars($check_in); ?>">
        <input type="hidden" name="check_out" value="<?php echo htmlspecialchars($check_out); ?>">
        <input type="hidden" name="guests" value="<?php echo htmlspecialchars($guests); ?>">
        <input type="hidden" name="room_rate" value="<?php echo htmlspecialchars($selected_room['rate_per_night']); ?>">

        <label>Room:</label>
        <input type="text" value="<?php echo htmlspecialchars($selected_room['room_name'] . ' - ' . $selected_room['rate_per_night'] . ' JD'); ?>" readonly>

        <div class="form-group">
            <label>First Name:</label>
            <input type="text" name="first_name" value="<?php echo htmlspecialchars($first_name); ?>" required>
        </div>
        <div class="form-group">
            <label>Last Name:</label>
            <input type="text" name="last_name" value="<?php echo htmlspecialchars($last_name); ?>" required>
        </div>
        <div class="form-group">
            <label>Phone:</label>
            <input type="text" name="phone" value="<?php echo htmlspecialchars($phone); ?>" required>
        </div>
        <div class="form-group">
            <label>Email:</label>
            <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
        </div>
        <div class="form-group">
            <label>Address:</label>
            <input type="text" name="address" value="<?php echo htmlspecialchars($address); ?>" required>
        </div>
        <div class="form-group">
            <label>City:</label>
            <input type="text" name="city" value="<?php echo htmlspecialchars($city); ?>" required>
        </div>
        <div class="form-group">
            <label>Country:</label>
            <input type="text" name="country" value="<?php echo htmlspecialchars($country); ?>" required>
        </div>
        <div class="form-group">
            <label>Zip Code:</label>
            <input type="text" name="zip_code" value="<?php echo htmlspecialchars($zip_code); ?>" required>
        </div>
        <div class="form-group">
            <label>Breakfast (3 JD per night):</label>
            <input type="radio" name="food" value="Yes" required> Yes
            <input type="radio" name="food" value="No" required> No
        </div>
        <div class="form-group">
            <label>Payment Method:</label>
            <input type="radio" name="payment_method" value="Cash" required> Pay By Cash (On Arrival)
            <input type="radio" name="payment_method" value="CliQ" required> Pay By CliQ (youseftm)
        </div>
        <div class="form-group">
            <label>Stay Duration:</label>
            <input type="text" value="<?php echo $formatted_check_in . ' to ' . $formatted_check_out; ?>" readonly>
        </div>
        <div class="form-group">
            <label>Total Price: <span id="total-price">0.00</span></label>
            <label>Total Guests: <span id="total-guests"><?php echo htmlspecialchars($guests); ?></span></label>
        </div>
        
        <button type="submit">Book</button>
    </form>
</body>
</html>
