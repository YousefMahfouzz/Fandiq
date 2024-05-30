<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'config/config.php';
include 'functions/functions.php';

$rooms = [];
$country = '';
$city = '';
$guests = '';
$check_in = '';
$check_out = '';
$error_message = '';

$countries = getAvailableCountries($conn);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $country = $_POST['country'] ?? '';
    $city = $_POST['city'] ?? '';
    $guests = $_POST['guests'] ?? '';
    $check_in = $_POST['check_in'] ?? '';
    $check_out = $_POST['check_out'] ?? '';

    if ($guests <= 0) {
        $error_message = "Number of guests must be a positive number.";
    } elseif (empty($check_in) || empty($check_out)) {
        $error_message = "Please select both check-in and check-out dates.";
    } else {
        if ($country && $city && $guests && $check_in && $check_out) {
            $rooms = getAvailableRooms($conn, $country, $city, $guests, $check_in, $check_out);
            if (empty($rooms)) {
                $error_message = "No rooms available for the specified dates.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Fandiq</title>
    <link rel="stylesheet" type="text/css" href="css/styles.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/jquery/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="js/scripts.js"></script>
</head>
<body class="index-page">
    <div class="top-right">
        <a href="admin/admin_login.php" class="button">Admin Login</a>
    </div>
    <div class="logo-container">
        <img src="images/fandiqlogo.svg" alt="Fandiq Management System Logo" class="logo">
    </div>
    <form method="POST" class="booking-form fade-in">
        <input type="hidden" name="check_in" id="check_in">
        <input type="hidden" name="check_out" id="check_out">

        <div class="form-group">
            <label for="country">Country:</label>
            <select name="country" id="country" required>
                <option value="" disabled selected>Select Country</option>
                <?php foreach ($countries as $row): ?>
                    <option value="<?php echo htmlspecialchars($row['country']); ?>" <?php echo ($country == $row['country']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($row['country']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="city">City:</label>
            <select name="city" id="city" required>
                <option value="" disabled selected>Select City</option>
                <?php if ($country): ?>
                    <?php 
                    $cities = getAvailableCities($conn, $country);
                    foreach ($cities as $row): ?>
                        <option value="<?php echo htmlspecialchars($row['city']); ?>" <?php echo ($city == $row['city']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($row['city']); ?></option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="guests">Number of Guests:</label>
            <input type="number" name="guests" id="guests" value="<?php echo htmlspecialchars($guests); ?>" required>
        </div>

        <label for="booking_dates">Booking Dates:</label>
        <input type="text" id="booking_dates" required>

        <button type="submit" class="button">Search Rooms</button>
    </form>

    <?php if ($error_message): ?>
        <p class="error-message"><?php echo $error_message; ?></p>
    <?php endif; ?>

    <?php if ($rooms): ?>
        <h2>Available Rooms in <?php echo htmlspecialchars($city); ?>, <?php echo htmlspecialchars($country); ?></h2>
        <table>
            <tr>
                <th>Room Name</th>
                <th>Description</th>
                <th>Beds</th>
                <th>City</th>
                <th>Price per night</th>
                <th>Capacity</th>
                <th>Images</th>
                <th>Book</th>
            </tr>
            <?php foreach ($rooms as $room): ?>
                <tr>
                    <td><?php echo htmlspecialchars($room['room_name']); ?></td>
                    <td><?php echo htmlspecialchars($room['description']); ?></td>
                    <td><?php echo htmlspecialchars($room['bedroom']); ?></td>
                    <td><?php echo htmlspecialchars($room['city']); ?></td>
                    <td><?php echo htmlspecialchars($room['rate_per_night']); ?></td>
                    <td><?php echo htmlspecialchars($room['capacity']); ?></td>
                    <td><button type="button" onclick="showImages('<?php echo htmlspecialchars($room['image_url']); ?>')">View Images</button></td>
                    <td><a href="customer/book_room.php?room_id=<?php echo htmlspecialchars($room['room_id']); ?>&country=<?php echo htmlspecialchars($country); ?>&city=<?php echo htmlspecialchars($city); ?>&guests=<?php echo htmlspecialchars($guests); ?>&check_in=<?php echo htmlspecialchars($check_in); ?>&check_out=<?php echo htmlspecialchars($check_out); ?>">Book</a></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>

    <a href="customer/confirm_booking.php" class="button fade-in">Manage Booking</a>

    <div id="imageModal" class="modal">
        <span class="close" onclick="closeModal()">&times;</span>
        <div class="modal-content" id="modalContent"></div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const countrySelect = document.getElementById('country');
            const citySelect = document.getElementById('city');

            countrySelect.addEventListener('change', function () {
                const selectedCountry = countrySelect.value;
                if (selectedCountry) {
                    fetchCities(selectedCountry);
                }
            });

            function fetchCities(country) {
                $.ajax({
                    url: 'fetch_cities.php',
                    type: 'POST',
                    data: {country: country},
                    success: function(data) {
                        const cities = JSON.parse(data);
                        citySelect.innerHTML = '<option value="" disabled selected>Select City</option>';
                        cities.forEach(function(city) {
                            const option = document.createElement('option');
                            option.value = city.city;
                            option.textContent = city.city;
                            citySelect.appendChild(option);
                        });
                    }
                });
            }

            flatpickr("#booking_dates", {
                mode: "range",
                dateFormat: "Y-m-d",
                minDate: "today",
                onChange: function(selectedDates, dateStr, instance) {
                    if (selectedDates.length === 2) {
                        document.getElementById('check_in').value = selectedDates[0].toISOString().slice(0, 10);
                        document.getElementById('check_out').value = selectedDates[1].toISOString().slice(0, 10);
                    }
                }
            });
        });
    </script>
</body>
</html>
