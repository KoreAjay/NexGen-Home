<?php
// db_connect.php

$servername = "localhost"; // Usually 'localhost' for development
$username = "root"; // Replace with your MySQL username
$password = ""; // Replace with your MySQL password
$dbname = "nexgen_data";      // The database name you created

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Optional: Set character set to UTF-8
$conn->set_charset("utf8mb4");

// Note: In a real application, you'd handle errors more gracefully,
// and potentially use PDO for more flexible database interaction.
?>