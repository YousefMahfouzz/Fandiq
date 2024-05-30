<?php
$servername = "localhost";
$username = "root";
$password = "";

// Create connection
$conn = new mysqli($servername, $username, $password);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database
$sql = "CREATE DATABASE IF NOT EXISTS hotel_management";
if ($conn->query($sql) === TRUE) {
    echo "Database created successfully\n";
} else {
    echo "Error creating database: " . $conn->error;
}

// Select the database
$conn->select_db("hotel_management");

// Create tables
$tables = [
    "CREATE TABLE IF NOT EXISTS User (
        user_id INT AUTO_INCREMENT PRIMARY KEY,
        first_name VARCHAR(255) NOT NULL,
        last_name VARCHAR(255) NOT NULL,
        address VARCHAR(255),
        city VARCHAR(255),
        phone VARCHAR(15),
        email VARCHAR(255) NOT NULL UNIQUE,
        country VARCHAR(255),
        zip_code INT
    )",
    "CREATE TABLE IF NOT EXISTS Admin (
        user_id INT PRIMARY KEY,
        password VARCHAR(255) NOT NULL,
        user_role VARCHAR(50) NOT NULL,
        FOREIGN KEY (user_id) REFERENCES User(user_id)
    )",
    "CREATE TABLE IF NOT EXISTS Room (
        room_id INT AUTO_INCREMENT PRIMARY KEY,
        room_name VARCHAR(255) NOT NULL,
        bedroom INT,
        rate_per_night INT,
        capacity INT,
        city VARCHAR(255),
        country VARCHAR(255),
        description TEXT,
        image_url VARCHAR(255)
    )",
    "CREATE TABLE IF NOT EXISTS Payments (
        payment_id INT AUTO_INCREMENT PRIMARY KEY,
        payment_method VARCHAR(50) NOT NULL,
        user_id INT,
        payment_date DATETIME,
        FOREIGN KEY (user_id) REFERENCES User(user_id)
    )",
    "CREATE TABLE IF NOT EXISTS Reservation (
        confirmation_code VARCHAR(5) PRIMARY KEY,
        user_id INT,
        room_id INT,
        total_price INT,
        check_in_date DATE,
        check_out_date DATE,
        payment_id INT,
        food VARCHAR(50),
        occupants INT,
        FOREIGN KEY (user_id) REFERENCES User(user_id),
        FOREIGN KEY (room_id) REFERENCES Room(room_id),
        FOREIGN KEY (payment_id) REFERENCES Payments(payment_id)
    )"
];

foreach ($tables as $sql) {
    if ($conn->query($sql) === TRUE) {
        echo "Table created successfully\n";
    } else {
        echo "Error creating table: " . $conn->error . "\n";
    }
}

$payment_methods = ["Cash", "CliQ"];
foreach ($payment_methods as $method) {
    $stmt = $conn->prepare("INSERT INTO Payments (payment_method) VALUES (?)");
    $stmt->bind_param("s", $method);
    if ($stmt->execute()) {
        echo "Inserted payment method: $method\n";
    } else {
        echo "Error inserting payment method: " . $conn->error . "\n";
    }
}

// Create default admin user
$admin_email = "admin@fandik.com";
$admin_password = password_hash("admin", PASSWORD_DEFAULT);
$admin_role = "admin";

// Check if admin user already exists
$result = $conn->query("SELECT * FROM User WHERE email = '$admin_email'");
if ($result->num_rows == 0) {
    $conn->query("INSERT INTO User (first_name, last_name, email) VALUES ('Admin', 'User', '$admin_email')");
    $user_id = $conn->insert_id;
    $conn->query("INSERT INTO Admin (user_id, password, user_role) VALUES ($user_id, '$admin_password', '$admin_role')");
    echo "Admin user created successfully with email: $admin_email and password: admin\n";
} else {
    echo "Admin user already exists.\n";
}

$conn->close();
?>
