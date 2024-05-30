<?php
include 'C:/xampp/htdocs/Fandik/config/config.php';
include 'C:/xampp/htdocs/Fandik/functions/functions.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    if (loginAdmin($conn, $email, $password)) {
        $_SESSION['admin_logged_in'] = true;
        header('Location: admin_dashboard.php');
    } else {
        echo "Invalid login credentials.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Login</title>
    <link rel="stylesheet" type="text/css" href="../css/styles.css">
    <script src="../js/scripts.js"></script>
</head>
<body>
    <div class="logo-container">
        <img src="../images/fandiqlogo.svg" alt="Fandiq Management System Logo" class="logo">
    </div>
    <h1>Admin Login</h1>
    <form method="POST">
        <label>Email:</label>
        <input type="email" name="email" required>
        <label>Password:</label>
        <input type="password" name="password" required>
        <button type="submit">Login</button>
    </form>
    <div>
    <a href="../index.php">Back to main menu</a> 
    </div>
</body>
</html>
