<?php
session_start();

if (!isset($_SESSION['a_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

include '../db/db_conn.php';

$data = json_decode(file_get_contents('php://input'), true);
$notificationId = $data['id'] ?? null;

if ($notificationId) {
    // Update notifications table
    $sql = "UPDATE notifications SET is_read = 1 WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $notificationId);
    $stmt->execute();
    $notificationsUpdated = $stmt->affected_rows;
    $stmt->close();

    // Update leave_notifications table
    $sql = "UPDATE leave_notifications SET is_read = 1 WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $notificationId);
    $stmt->execute();
    $leaveNotificationsUpdated = $stmt->affected_rows;
    $stmt->close();

    if ($notificationsUpdated > 0 || $leaveNotificationsUpdated > 0) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update notification']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid notification ID']);
}

$conn->close();
?>
