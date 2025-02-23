<?php
include '../db/db_conn.php';

if (isset($_GET['id'])) {
    $notificationId = $_GET['id'];
    $sql = "SELECT message, created_at FROM notifications WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $notificationId);
    $stmt->execute();
    $result = $stmt->get_result();
    $notification = $result->fetch_assoc();
    $stmt->close();
    $conn->close();

    echo json_encode($notification);
} else {
    echo json_encode(['error' => 'Invalid request']);
}
?>
