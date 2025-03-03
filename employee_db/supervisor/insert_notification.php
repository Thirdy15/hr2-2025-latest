<?php
include '../../db/db_conn.php';

$employeeId = $_POST['employeeId'];
$message = $_POST['message'];

// Insert notification into the database
$sql = "INSERT INTO notifications (employee_id, message, created_at) VALUES (?, ?, NOW())";
$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $employeeId, $message);
$stmt->execute();

$stmt->close();
$conn->close();
?>
