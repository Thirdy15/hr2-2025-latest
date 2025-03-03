<?php

// Database configuration
$servername = "localhost"; // Your database server
$username = "root";         // Your database username
$password = "";             // Your database password
$dbname = "hr2";            // Your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set character set to utf8 for proper encoding (optional)
$conn->set_charset("utf8");

// Create notifications table
$sql = "CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    message VARCHAR(255) NOT NULL,
    status ENUM('unread', 'read') DEFAULT 'unread',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employee_register(e_id)
)";

if ($conn->query($sql) === TRUE) {
    echo "Table notifications created successfully";
} else {
    echo "Error creating table: " . $conn->error;
}

// Create leave_notifications table
$sql = "CREATE TABLE IF NOT EXISTS leave_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    message VARCHAR(255) NOT NULL,
    status ENUM('unread', 'read') DEFAULT 'unread',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employee_register(e_id)
)";

if ($conn->query($sql) === TRUE) {
    echo "Table leave_notifications created successfully";
} else {
    echo "Error creating table: " . $conn->error;
}
?>
