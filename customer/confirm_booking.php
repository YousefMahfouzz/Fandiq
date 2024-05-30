<?php
include '../config/config.php';
include '../functions/functions.php';

$booking = null;
$user = null;
$payment_method = null;
$room = null;
$confirmation_code = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $confirmation_code = $_POST['confirmation_code'];
} else {
    $confirmation_code = $_GET['confirmation_code'] ?? '';
}

if ($confirmation_code) {
    $booking = getBookingByCode($conn, $confirmation_code);

    if ($booking) {
        $user = getUserById($conn, $booking['user_id']);
        $payment_method = getPaymentMethodById($conn, $booking['payment_id']);
        $room = getRoomById($conn, $booking['room_id']);
        $total_price = $booking['total_price']; // Retrieve total price from booking

        // Fetch payment date from Payments table
        $payment_stmt = $conn->prepare("SELECT payment_date FROM Payments WHERE payment_id = ?");
        $payment_stmt->bind_param("i", $booking['payment_id']);
        $payment_stmt->execute();
        $payment_stmt->bind_result($payment_date);
        $payment_stmt->fetch();
        $payment_stmt->close();

        // Ensure dates are properly set
        $check_in_date = DateTime::createFromFormat('Y-m-d', $booking['check_in_date']);
        $check_out_date = DateTime::createFromFormat('Y-m-d', $booking['check_out_date']);

        // Calculate total nights
        if ($check_in_date && $check_out_date) {
            $nights = $check_out_date->diff($check_in_date)->days;
        } else {
            $nights = 0;
        }
    } else {
        echo "<p>Invalid confirmation code.</p>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Confirm Booking</title>
    <link rel="stylesheet" type="text/css" href="../css/styles.css">
</head>
<body class="index-page">
    <h1>Confirm Booking</h1>
    <form method="POST" class="confirmation-form">
        <label for="confirmation_code">Confirmation Code:</label>
        <input type="text" id="confirmation_code" name="confirmation_code" value="<?php echo htmlspecialchars($confirmation_code); ?>" required>
        <button type="submit">Check Booking</button>
    </form>

    <?php if ($booking && $user && $payment_method && $room): ?>
        <h2>Booking Details</h2>
        <table>
            <tr>
                <th>Booking Date:</th>
                <td><?php echo htmlspecialchars($payment_date); ?></td>
            </tr>
            <tr>
                <th>Guest Name:</th>
                <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
            </tr>
            <tr>
                <th>Room Name:</th>
                <td><?php echo htmlspecialchars($room['room_name']); ?></td>
            </tr>
            <tr>
                <th>Check-In Date:</th>
                <td><?php echo htmlspecialchars($booking['check_in_date']); ?></td>
            </tr>
            <tr>
                <th>Check-Out Date:</th>
                <td><?php echo htmlspecialchars($booking['check_out_date']); ?></td>
            </tr>
            <tr>
                <th>Total Nights:</th>
                <td><?php echo htmlspecialchars($nights); ?></td>
            </tr>
            <tr>
                <th>Payment Method:</th>
                <td><?php echo htmlspecialchars($payment_method['payment_method']); ?></td>
            </tr>
            <tr>
                <th>Breakfast Included:</th>
                <td><?php echo htmlspecialchars($booking['food'] === 'Yes' ? 'Yes' : 'No'); ?></td>
            </tr>
            <tr>
                <th>Number Of Guests:</th>
                <td><?php echo htmlspecialchars($booking['occupants']); ?></td>
            </tr>
            <tr>
                <th>Total Price:</th>
                <td><?php echo htmlspecialchars($total_price); ?> JD</td>
            </tr>
        </table>
    <?php endif; ?>

    <a href="../index.php" class="button wide-button">Back to Main Menu</a>
</body>
</html>
