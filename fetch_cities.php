<?php
include 'config/config.php';
include 'functions/functions.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $country = $_POST['country'] ?? '';

    if ($country) {
        $cities = getAvailableCities($conn, $country);
        echo json_encode($cities);
    }
}
?>
