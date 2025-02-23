<?php
require_once 'db_connection.php'; // Ensure this file includes the database connection

$data = json_decode(file_get_contents('php://input'), true);
$message = $data['message'] ?? '';

if ($message) {
    $stmt = $conn->prepare("INSERT INTO notifications (message, created_at) VALUES (?, NOW())");
    $stmt->bind_param("s", $message);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to insert notification.']);
    }

    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'No message provided.']);
}

$conn->close();
?>
