<?php
function loginAdmin($conn, $email, $password) {
    $stmt = $conn->prepare("SELECT Admin.password FROM User JOIN Admin ON User.user_id = Admin.user_id WHERE User.email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($passwordHash);
        $stmt->fetch();
        return password_verify($password, $passwordHash);
    }
    return false;
}

function addRoom($conn, $room_name, $bedroom, $rate_per_night, $capacity, $city, $country, $description, $image) {
    $stmt = $conn->prepare("INSERT INTO Room (room_name, bedroom, rate_per_night, capacity, city, country, description, image_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("siiissss", $room_name, $bedroom, $rate_per_night, $capacity, $city, $country, $description, $image);
    return $stmt->execute();
}

function getRooms($conn) {
    $result = $conn->query("SELECT * FROM Room");
    return $result->fetch_all(MYSQLI_ASSOC);
}

function getUsers($conn) {
    $result = $conn->query("SELECT * FROM User");
    return $result->fetch_all(MYSQLI_ASSOC);
}

function getReservations($conn) {
    $result = $conn->query("SELECT * FROM Reservation");
    return $result->fetch_all(MYSQLI_ASSOC);
}

function generateConfirmationCode() {
    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $code = '';
    for ($i = 0; $i < 5; $i++) {
        $code .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $code;
}

function bookRoom($conn, $user_id, $room_id, $check_in_date, $check_out_date, $payment_id, $food, $occupants, $total_price) {
    // Server-side date validation
    $current_date = date('Y-m-d');
    if ($check_in_date < $current_date || $check_out_date < $current_date) {
        throw new Exception("Check-in and Check-out dates cannot be in the past.");
    }

    // Generate confirmation code
    $confirmation_code = generateConfirmationCode();

    // Check if the room is available
    if (!isRoomAvailable($conn, $room_id, $check_in_date, $check_out_date)) {
        throw new Exception("The selected room is not available for the given dates.");
    }

    // Set the payment date with time
    $payment_date = date('Y-m-d h:i A');
    $update_payment_stmt = $conn->prepare("UPDATE Payments SET payment_date = ? WHERE payment_id = ?");
    if (!$update_payment_stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    $update_payment_stmt->bind_param("si", $payment_date, $payment_id);
    if (!$update_payment_stmt->execute()) {
        throw new Exception("Execute failed: " . $update_payment_stmt->error);
    }
    $update_payment_stmt->close();

    // Prepare and execute the SQL statement for reservation
    $stmt = $conn->prepare("
        INSERT INTO Reservation (user_id, room_id, check_in_date, check_out_date, payment_id, food, occupants, total_price, confirmation_code)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("iisssiiis", $user_id, $room_id, $check_in_date, $check_out_date, $payment_id, $food, $occupants, $total_price, $confirmation_code);
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }

    return $confirmation_code;
}

function getBookingByCode($conn, $confirmation_code) {
    $stmt = $conn->prepare("SELECT * FROM Reservation WHERE confirmation_code = ?");
    $stmt->bind_param("s", $confirmation_code);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

function getUserByEmail($conn, $email) {
    $stmt = $conn->prepare("SELECT * FROM User WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

function getUserById($conn, $user_id) {
    $stmt = $conn->prepare("SELECT first_name, last_name FROM User WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

function getPaymentMethodById($conn, $payment_id) {
    $stmt = $conn->prepare("SELECT payment_method FROM Payments WHERE payment_id = ?");
    $stmt->bind_param("i", $payment_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

function getRoomById($conn, $room_id) {
    $stmt = $conn->prepare("SELECT room_name, rate_per_night FROM Room WHERE room_id = ?");
    $stmt->bind_param("i", $room_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

function registerUser($conn, $first_name, $last_name, $address, $city, $phone, $email, $country, $zip_code) {
    $stmt = $conn->prepare("INSERT INTO User (first_name, last_name, address, city, phone, email, country, zip_code) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssssi", $first_name, $last_name, $address, $city, $phone, $email, $country, $zip_code);
    if ($stmt->execute()) {
        return $conn->insert_id;
    }
    return false;
}

function getAvailableCountries($conn) {
    $result = $conn->query("SELECT DISTINCT country FROM Room");
    return $result->fetch_all(MYSQLI_ASSOC);
}

function getAvailableCities($conn, $country) {
    $stmt = $conn->prepare("SELECT DISTINCT city FROM Room WHERE country = ?");
    $stmt->bind_param("s", $country);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

function getRoomsByLocationAndOccupants($conn, $country, $city, $occupants) {
    $stmt = $conn->prepare("SELECT * FROM Room WHERE country = ? AND city = ? AND capacity >= ?");
    $stmt->bind_param("ssi", $country, $city, $occupants);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

function isRoomAvailable($conn, $room_id, $check_in, $check_out) {
    $query = "SELECT * FROM Reservation WHERE room_id = ? AND (check_in_date < ? AND check_out_date > ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iss", $room_id, $check_out, $check_in);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows === 0;
}

function getAvailableRooms($conn, $country, $city, $guests, $check_in, $check_out) {
    $stmt = $conn->prepare("
        SELECT * FROM Room
        WHERE country = ? AND city = ? AND capacity >= ?
        AND room_id NOT IN (
            SELECT room_id FROM Reservation
            WHERE (check_in_date <= ? AND check_out_date >= ?)
        )
    ");
    $stmt->bind_param("ssiss", $country, $city, $guests, $check_out, $check_in);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}
?>
